@if ($mode === 'invisible')
    <div
        id="{{ $widgetId }}"
        class="g-recaptcha"
        data-sitekey="{{ $siteKey }}"
        data-size="invisible"
        data-callback="{{ $widgetId }}Done"
    ></div>
    <script>
        (function () {
            var widget = document.getElementById(@json($widgetId));
            var form = @if ($form) document.getElementById(@json($form)) @else widget.closest('form') @endif;
            if (! form) return;

            window[@json($widgetId.'Done')] = function () {
                form.dataset.captchaDone = '1';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            };

            form.addEventListener('submit', function (event) {
                if (form.dataset.captchaDone === '1') return;
                event.preventDefault();
                grecaptcha.execute();
            });
        })();
    </script>
@else
    <div
        class="g-recaptcha"
        data-sitekey="{{ $siteKey }}"
        data-theme="{{ $theme }}"
        data-size="{{ $size }}"
    ></div>
@endif

@once
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endonce
