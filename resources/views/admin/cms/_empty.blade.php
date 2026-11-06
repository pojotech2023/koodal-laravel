{{-- One empty row for all four tables — they differ only in column count. --}}
<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
        No {{ strtolower($label) }} rows yet. `CmsSeeder` normally creates the starting set.
    </td>
</tr>
