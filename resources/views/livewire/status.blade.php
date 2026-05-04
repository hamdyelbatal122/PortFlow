<div class="rounded-lg border p-3 text-sm">
    <div class="font-semibold">PortFlow Status</div>
    <div class="mt-2">State: {{ $connected ? 'Connected' : 'Disconnected' }}</div>
    <div>Driver: {{ $driver }}</div>
    <div>Frames: {{ $frames }}</div>
</div>
