{{-- A publish/active checkbox with its hidden 0 — an unticked box submits
     nothing, and every one of these fields is `sometimes` in
     CmsContentRequest, so an absent key means "leave it as it was". Without
     the hidden input a page could be published and never unpublished. --}}
<label class="flex items-center gap-2 text-sm font-medium">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
           class="rounded border" style="border-color:var(--border-default)">
    {{ $label }}
</label>
