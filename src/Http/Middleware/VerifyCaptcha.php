<?php

namespace Mason\Captcha\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mason\Captcha\CaptchaManager;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    /**
     * Usage: ->middleware('captcha'), ->middleware('captcha:turnstile'),
     * ->middleware('captcha:recaptcha_v3,login') for an explicit action, or
     * ->metadata(['captcha_action' => 'login']) on the route (Laravel 13+).
     *
     * Action resolution order:
     *   1. Explicit middleware param
     *   2. Route metadata key "captcha_action" (Laravel 13+)
     *   3. Last segment of the route name  (e.g. "auth.login" → "login")
     *   4. Driver config default
     */
    public function handle(Request $request, Closure $next, ?string $driver = null, ?string $action = null): Response
    {
        $manager = app('captcha');

        if ($manager instanceof CaptchaManager && $manager->shouldSkip()) {
            return $next($request);
        }

        $captcha = $manager->driver($driver);

        $resolved = $this->resolveAction($request, $action);

        if ($resolved !== null && method_exists($captcha, 'expectAction')) {
            $captcha->expectAction($resolved);
        }

        $field = $captcha->responseFieldName();
        $result = $captcha->verify((string) $request->input($field, ''), $request->ip());

        if ($result->failed()) {
            throw ValidationException::withMessages([
                $field => __('captcha::validation.failed'),
            ]);
        }

        return $next($request);
    }

    private function resolveAction(Request $request, ?string $explicit): ?string
    {
        if ($explicit !== null) {
            return $explicit;
        }

        $route = $request->route();

        if ($route && method_exists($route, 'getMetadata')) {
            $meta = $route->getMetadata('captcha_action');
            if ($meta !== null) {
                return (string) $meta;
            }
        }

        if ($route && ($name = $route->getName())) {
            return Str::afterLast($name, '.');
        }

        return null;
    }
}
