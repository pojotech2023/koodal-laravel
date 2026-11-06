@extends('admin.layouts.admin')

@section('title', ($isEdit ? 'Edit Question' : 'Add Question') . ' — Koodal Sync')

@section('admin-body')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-ink-900">{{ $isEdit ? 'Edit Compatibility Question' : 'Create New Compatibility Question' }}</h1>
            <p class="text-sm text-ink-500">Configure bilingual question text and 2–4 choices.</p>
        </div>
        <a href="{{ route('admin.games.questions.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-ink-600 hover:bg-gray-50">
            ← Back to List
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.games.questions.update', $question->id) : route('admin.games.questions.store') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Category --}}
        <div>
            <label class="block text-sm font-bold text-ink-800">Category *</label>
            <select name="category" required class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="">Select Category</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ old('category', $question->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Question English --}}
        <div>
            <label class="block text-sm font-bold text-ink-800">Question Text (English) *</label>
            <input type="text" name="question_text_en" value="{{ old('question_text_en', $question->question_text_en) }}" required placeholder="e.g. Tea or Coffee?" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>

        {{-- Question Tamil --}}
        <div>
            <label class="block text-sm font-bold text-ink-800">Question Text (Tamil)</label>
            <input type="text" name="question_text_ta" value="{{ old('question_text_ta', $question->question_text_ta) }}" placeholder="உதாரணம்: தேநீரா அல்லது காபியா?" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>

        {{-- Options section --}}
        <div class="space-y-4 rounded-xl bg-gray-50 p-4 border border-gray-200">
            <h3 class="text-sm font-bold text-ink-900">Question Choices / Options (2 to 4)</h3>

            @php
                $existingOptions = old('options', $question->options ?? [
                    ['key' => 'A', 'label_en' => '', 'label_ta' => ''],
                    ['key' => 'B', 'label_en' => '', 'label_ta' => ''],
                    ['key' => 'C', 'label_en' => '', 'label_ta' => ''],
                    ['key' => 'D', 'label_en' => '', 'label_ta' => ''],
                ]);
            @endphp

            @foreach([0, 1, 2, 3] as $i)
                @php
                    $keys = ['A', 'B', 'C', 'D'];
                    $optKey = $keys[$i];
                    $optVal = $existingOptions[$i] ?? ['key' => $optKey, 'label_en' => '', 'label_ta' => ''];
                @endphp
                <div class="grid grid-cols-12 gap-3 items-center rounded-lg bg-white p-3 border border-gray-200">
                    <div class="col-span-2 font-bold text-center text-primary-700 bg-primary-50 py-2 rounded-lg border border-primary-200">
                        Option {{ $optKey }}
                        <input type="hidden" name="options[{{ $i }}][key]" value="{{ $optKey }}">
                    </div>
                    <div class="col-span-5">
                        <input type="text" name="options[{{ $i }}][label_en]" value="{{ $optVal['label_en'] ?? '' }}" placeholder="English Option {{ $optKey }}" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                    </div>
                    <div class="col-span-5">
                        <input type="text" name="options[{{ $i }}][label_ta]" value="{{ $optVal['label_ta'] ?? '' }}" placeholder="தமிழ் விடை {{ $optKey }}" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Active Checkbox --}}
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $question->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-primary-600">
            <label for="is_active" class="text-sm font-semibold text-ink-800">Active (Visible in compatibility games)</label>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
            <a href="{{ route('admin.games.questions.index') }}" class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-ink-600 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Update Question' : 'Save Question' }}
            </button>
        </div>
    </form>
</div>
@endsection
