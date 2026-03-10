@php
    $prettyJson = $payload ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
@endphp

<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;">
        <div>
            <div style="font-size:15px;font-weight:700;">{{ $title }}</div>
            @if(!empty($subtitle))
                <div style="font-size:12px;color:var(--muted);margin-top:4px;">{{ $subtitle }}</div>
            @endif
        </div>
        @if(!empty($badge))
            <span class="badge badge-novo">{{ $badge }}</span>
        @endif
    </div>

    @if($prettyJson)
        <pre class="json-block">{{ $prettyJson }}</pre>
    @else
        <div class="empty-state">Nenhum payload disponível.</div>
    @endif
</div>
