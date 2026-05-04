@props([
    'message'          => 'Web Serial API is not supported in this browser. Please use Chrome 89+ or Edge 89+.',
    'showDownloadLink' => true,
])

<div data-portflow-browser-check>
    {{-- Warning banner — shown by inline JS when Web Serial is unavailable. --}}
    <div
        data-portflow-unsupported
        role="alert"
        style="display: none; padding: 0.75rem 1rem; background: #fff3cd; border: 1px solid #ffc107; border-radius: 0.375rem; color: #856404; margin-bottom: 1rem;"
    >
        {{ $message }}
        @if ($showDownloadLink)
            <a
                href="https://www.google.com/chrome/"
                target="_blank"
                rel="noopener noreferrer"
                style="margin-left: 0.5rem; font-weight: 600;"
            >Download Chrome →</a>
        @endif
    </div>

    {{ $slot }}
</div>

<script>
    (function () {
        if (typeof navigator === 'undefined' || !('serial' in navigator)) {
            document.querySelectorAll('[data-portflow-unsupported]').forEach(function (el) {
                el.style.display = 'block';
            });
        }
    }());
</script>
