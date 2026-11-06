@extends('admin.layouts.admin')

@php($isNew = ! $item->exists)

@section('title', ($isNew ? 'New '.strtolower($label) : 'Edit '.strtolower($label)).' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.cms.'.$type.'.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; {{ $label }}s</a>

    <h1 class="mt-3 font-serif text-3xl font-bold text-ink-900">{{ $isNew ? 'New '.strtolower($label) : 'Edit '.strtolower($label) }}</h1>

    <form method="POST" action="{{ $isNew ? route('admin.cms.'.$type.'.store') : route('admin.cms.'.$type.'.update', $item->id) }}"
          class="mt-6 max-w-3xl space-y-4">
        @csrf
        @unless ($isNew)
            @method('PUT')
        @endunless

        @switch($type)
            @case('pages')
                <x-form.input name="title" label="Title" :value="$item->title" required maxlength="255" />
                <x-form.input name="slug" label="Slug" :value="$item->slug" required maxlength="100"
                    hint="This is the URL. Two pages cannot share one — the public route looks a page up by it." />

                <x-form.textarea name="content" label="Content" :value="$item->content" maxlength="200000" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="meta_title" label="Meta title" :value="$item->meta_title" maxlength="255" />
                    <x-form.input name="meta_description" label="Meta description" :value="$item->meta_description" maxlength="320" />
                </div>

                <x-admin.image-field name="og_image_path" label="Social (OG) image" :value="$item->og_image_path"
                    maxlength="255" collection="social"
                    hint="Shown when the page is shared. 1200×630 renders correctly everywhere." />

                @include('admin.cms._toggle', ['name' => 'is_published', 'label' => 'Published', 'checked' => $item->is_published])

                {{-- `published_at` is stamped the first time a page goes live
                     and never moved: it is the date printed on a terms page,
                     and bumping it for a typo makes a correction look like a
                     change of terms. --}}
                @if ($item->published_at)
                    <p class="text-xs" style="color:var(--text-muted)">
                        Live since {{ $item->published_at->format('d M Y') }} — editing does not move that date.
                    </p>
                @endif
                @break

            @case('faqs')
                <x-form.input name="question" label="Question" :value="$item->question" required maxlength="255" />
                <x-form.textarea name="answer" label="Answer" :value="$item->answer" maxlength="20000" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="category" label="Category" :value="$item->category ?? 'general'" maxlength="60" />
                    <x-form.input name="sort_order" label="Sort order" type="number" min="0" max="65535" :value="$item->sort_order ?? 0" />
                </div>

                @include('admin.cms._toggle', ['name' => 'is_published', 'label' => 'Published', 'checked' => $item->is_published ?? true])
                @break

            @case('banners')
                <x-form.input name="title" label="Title" :value="$item->title" maxlength="255" />
                <x-admin.image-field name="image_path" label="Image" :value="$item->image_path" required maxlength="255"
                    collection="banners" hint="The desktop image. Wide — the hero slot renders it full width." />
                <x-admin.image-field name="mobile_image_path" label="Mobile image" :value="$item->mobile_image_path" maxlength="255"
                    collection="banners" hint="Optional. Falls back to the desktop image when empty — see Banner::imageUrl()." />
                <x-form.input name="link_url" label="Link URL" type="url" :value="$item->link_url" maxlength="255" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-form.select name="position" label="Position" :value="$item->position" required :options="[
                        ['id' => 'home_hero', 'name' => 'Home hero'],
                        ['id' => 'home_mid', 'name' => 'Home mid'],
                        ['id' => 'app_home', 'name' => 'App home'],
                    ]" />
                    <x-form.input name="starts_at" label="Starts" type="date" :value="$item->starts_at?->format('Y-m-d')" />
                    <x-form.input name="ends_at" label="Ends" type="date" :value="$item->ends_at?->format('Y-m-d')" />
                </div>

                <x-form.input name="sort_order" label="Sort order" type="number" min="0" max="65535" :value="$item->sort_order ?? 0" />

                @include('admin.cms._toggle', ['name' => 'is_active', 'label' => 'Active', 'checked' => $item->is_active ?? true])
                @break

            @case('success-stories')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="groom_name" label="Groom" :value="$item->groom_name" required maxlength="120" />
                    <x-form.input name="bride_name" label="Bride" :value="$item->bride_name" required maxlength="120" />
                    <x-form.input name="groom_user_id" label="Groom member id" type="number" min="1" :value="$item->groom_user_id"
                        hint="Optional. Links the story to the account, which is what lets it be taken down with them." />
                    <x-form.input name="bride_user_id" label="Bride member id" type="number" min="1" :value="$item->bride_user_id" />
                    <x-form.input name="marriage_date" label="Marriage date" type="date" :value="$item->marriage_date?->format('Y-m-d')" />
                    <x-form.input name="location" label="Location" :value="$item->location" maxlength="120" />
                    <x-form.input name="sort_order" label="Sort order" type="number" min="0" max="65535" :value="$item->sort_order ?? 0" />
                </div>

                <x-admin.image-field name="photo_path" label="Couple photo" :value="$item->photo_path" maxlength="255"
                    collection="success-stories" />

                <x-form.textarea name="story" label="Story" :value="$item->story" maxlength="20000" />

                <div class="flex flex-wrap gap-6">
                    @include('admin.cms._toggle', ['name' => 'is_published', 'label' => 'Published', 'checked' => $item->is_published])
                    @include('admin.cms._toggle', ['name' => 'is_featured', 'label' => 'Featured on the home page', 'checked' => $item->is_featured])
                </div>
                @break
        @endswitch

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                {{ $isNew ? 'Create' : 'Save changes' }}
            </button>
            <a href="{{ route('admin.cms.'.$type.'.index') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">Cancel</a>
        </div>
    </form>
@endsection
