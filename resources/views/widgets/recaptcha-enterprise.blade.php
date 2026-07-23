@if ($mode === 'checkbox')
    <div
        class="g-recaptcha"
        data-sitekey="{{ $siteKey }}"
        data-theme="{{ $theme }}"
        data-size="{{ $size }}"
    ></div>

    @once
        <script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>
    @endonce
@else
    @include('captcha::widgets.partials.score-form', ['api' => 'grecaptcha.enterprise'])

    @once
        <script src="https://www.google.com/recaptcha/enterprise.js?render={{ $siteKey }}" async defer></script>
    @endonce
@endif
