{{-- Shared by score-based widgets (reCAPTCHA v3 / Enterprise). Expects the
     common widget variables plus $api: the JS namespace exposing execute(). --}}
<input
    type="hidden"
    name="g-recaptcha-response"
    id="{{ $widgetId }}"
    @if ($form) form="{{ $form }}" @endif
>
<script @cspNonce>
    (function () {
        var input = document.getElementById(@json($widgetId));
        var form = @if ($form) document.getElementById(@json($form)) @else input.closest('form') @endif;
        if (! form) return;

        form.addEventListener('submit', function (event) {
            if (form.dataset.captchaDone === '1') return;
            event.preventDefault();
            var api = {!! $api !!};
            api.ready(function () {
                api.execute(@json($siteKey), { action: @json($action) }).then(function (token) {
                    input.value = token;
                    form.dataset.captchaDone = '1';
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                });
            });
        });
    })();
</script>
