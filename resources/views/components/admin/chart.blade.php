@props(['id', 'title', 'type' => 'line', 'series' => [], 'xKey' => 'date'])

{{-- Renders nothing itself — resources/js/admin.js scans every
     `canvas[data-chart-series]` on load and hands it to Chart.js. The series
     is embedded as a data attribute rather than a page `<script>` block so
     this component stays a single self-contained partial: no matching inline
     `<script>` to keep in sync a few hundred lines away in the parent view.
     `$series` is already server-aggregated (DashboardMetrics) — this never
     ships raw rows, per docs/06-admin-panel-guide.md §8. --}}
<div class="card p-5">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-ink-800">{{ $title }}</h3>
        <span class="h-2 w-2 rounded-full bg-brand-gradient"></span>
    </div>
    <div class="mt-4" style="height:230px">
        <canvas
            id="{{ $id }}"
            data-chart-type="{{ $type }}"
            data-chart-x-key="{{ $xKey }}"
            data-chart-series="{{ json_encode(collect($series)->values()) }}"
        ></canvas>
    </div>
</div>
