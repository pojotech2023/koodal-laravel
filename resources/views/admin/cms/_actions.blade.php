{{-- Edit + delete, identical across the four types. `$identifier` is what the
     confirm dialog asks to be typed back: a page's slug (which is its URL, and
     the thing an editor recognises), an id for the rest — none of these tables
     is soft-deleted, so the whole row is in the audit trail and nowhere
     else. --}}
@can('cms.manage')
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('admin.cms.'.$type.'.edit', $item->id) }}" class="text-sm font-medium text-primary-600 hover:underline">Edit</a>

        <x-admin.confirm-dialog
            id="delete-cms-{{ $type }}-{{ $item->id }}" trigger="Delete" variant="danger"
            :title="'Delete this '.strtolower($label).'?'"
            :action="route('admin.cms.'.$type.'.destroy', $item->id)" method="DELETE"
            :identifier="$identifier" confirm-label="Delete"
        >
            <p style="color:var(--text-muted)">
                Deleted for good — these tables are not soft-deleted. The full row is written to the audit trail first, which is the only way back.
            </p>
        </x-admin.confirm-dialog>
    </div>
@endcan
