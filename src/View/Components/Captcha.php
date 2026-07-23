<?php

namespace Mason\Captcha\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use InvalidArgumentException;

class Captcha extends Component
{
    public function __construct(
        public ?string $driver = null,
        public ?string $theme = null,
        public ?string $size = null,
        public ?string $action = null,
        public ?string $form = null,
        public ?string $cspNonce = null,
    ) {
    }

    public function render(): View
    {
        $name = $this->driver ?? (string) config('captcha.default', 'recaptcha_v2');
        $config = (array) config("captcha.drivers.{$name}", []);

        return view($this->widgetView($name), [
            'siteKey' => (string) ($config['site_key'] ?? ''),
            'mode' => (string) ($config['mode'] ?? 'checkbox'),
            'theme' => $this->theme ?? (string) ($config['theme'] ?? 'light'),
            'size' => $this->size ?? (string) ($config['size'] ?? 'normal'),
            'action' => $this->action ?? $this->routeAction() ?? (string) ($config['action'] ?? 'submit'),
            'form' => $this->form,
            'widgetId' => 'captcha-' . Str::random(8),
        ]);
    }

    private function routeAction(): ?string
    {
        $route = request()->route();

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

    protected function widgetView(string $driver): string
    {
        return match ($driver) {
            'recaptcha_v2' => 'captcha::widgets.recaptcha-v2',
            'recaptcha_enterprise' => 'captcha::widgets.recaptcha-enterprise',
            // Backend verification works for every driver; widgets for the
            // remaining providers land one driver at a time.
            default => throw new InvalidArgumentException(
                "No <x-captcha> widget for driver [{$driver}] yet. Render the widget manually and verify with Captcha::driver('{$driver}')->verify()."
            ),
        };
    }
}
