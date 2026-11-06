{{-- One feature value rendered the way its type means it, shared by both
     columns of the snapshot comparison so "bought" and "today" can never be
     formatted differently and read as a difference that isn't one.
     -1 is the codebase-wide convention for unlimited (FeatureGate,
     PlanResource); a missing key is not "off" — it means the member falls back
     to the default plan, which is worth saying out loud rather than showing as
     a blank cell. --}}
@if ($value === null)
    <span style="color:var(--text-muted)">not set — falls back to the default plan</span>
@elseif (($meta['type'] ?? null) === 'quota')
    {{ $value === -1 ? 'Unlimited' : number_format((int) $value) }}
@elseif (($meta['type'] ?? null) === 'capability')
    {{ $value ? 'Yes' : 'No' }}
@else
    {{ ucfirst((string) $value) }}
@endif
