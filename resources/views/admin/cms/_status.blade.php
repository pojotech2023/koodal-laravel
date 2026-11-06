{{-- The publish/active pill, shared by all four content tables so "live" looks
     the same whichever screen an editor is on. --}}
<span @class([
    'rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-success/10 text-success' => $on,
    'bg-warning/10 text-warning' => ! $on,
])>{{ $on ? 'Live' : 'Draft' }}</span>
