{{-- A label/value pair that renders nothing when the field was never
     filled in — used across "my profile" and the admin detail screens so an
     empty section collapses instead of showing a row of dashes. Rendered as a
     soft tile (.detail-tile) so a card of facts scans as a grid, not a list. --}}
@props(['label', 'value'])

@if (filled($value))
    <div class="detail-tile">
        <dt class="text-xs" style="color:var(--text-muted)">{{ $label }}</dt>
        <dd class="text-sm">{{ $value }}</dd>
    </div>
@endif
