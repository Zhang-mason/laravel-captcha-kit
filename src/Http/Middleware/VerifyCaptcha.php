<?php

namespace Mason\Captcha\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mason\Captcha\CaptchaManager;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    /**
     * Usage: ->middleware('captcha'), ->middleware('captcha:turnstile'),
     * or ->middleware('captcha:recaptcha_v3,login') for a v3 action.
     */
    public function handle(Request $request, Closure $next, ?string $driver = null, ?string $action = null): Response
    {
        $manager = app('captcha');

        if ($manager instanceof CaptchaManager && $manager->shouldSkip()) {
            return $next($request);
        }

        $captcha = $manager->driver($driver);

        if ($action !== null && method_exists($captcha, 'expectAction')) {
            $captcha->expectAction($action);
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
}
