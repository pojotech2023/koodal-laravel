@extends('admin.layouts.admin')

@section('title', ($isEdit ? 'Edit Memory Challenge' : 'Add New Memory Challenge') . ' — Admin')

@section('admin-body')
<div class="mx-auto max-w-4xl space-y-6 font-sans" x-data="memorySnapForm()">

    {{-- Top Header Bar --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900" x-text="isEdit ? 'Edit Memory Challenge' : 'Add New Memory Challenge'"></h1>
            <p class="text-sm text-slate-500 mt-1">Add sets of (Photo + Question + 4 Options) for 1v1 Memory Snap recall game.</p>
        </div>
        <a href="{{ route('admin.games.memory_snap.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-800 hover:bg-slate-50 transition-colors shadow-sm">
            ← Back to List
        </a>
    </div>

    {{-- Validation Errors Banner --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm">
            <p class="font-extrabold text-rose-900 mb-1">Please check the form:</p>
            <ul class="list-disc pl-5 space-y-0.5 text-xs font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Container --}}
    <form method="POST" action="{{ $isEdit ? route('admin.games.memory_snap.update', $challenge->id) : route('admin.games.memory_snap.store') }}" enctype="multipart/form-data" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- 1. Memory Challenge Title --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Challenge Title *</label>
            <input type="text" name="title" value="{{ old('title', $challenge->title) }}" required placeholder="e.g. Wedding & Festival Memory Pack"
                   class="w-full rounded-xl border border-slate-300 p-3 text-sm focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600 font-semibold text-slate-900">
            @error('title')
                <p class="text-xs font-bold text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- 2. Sets of Image + Question + Options --}}
        <div class="space-y-8 pt-4 border-t border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Image & Question Sets (<span x-text="questions.length"></span>)</h3>
                    <p class="text-xs text-slate-500">Each set displays 1 photo for 10s, followed by its recall question.</p>
                </div>
                <button type="button" @click="addQuestion()" class="rounded-xl bg-purple-700 px-4 py-2.5 text-xs font-extrabold text-white shadow-md hover:bg-purple-800 transition-all flex items-center gap-1.5" style="background-color: #7C3AED !important; color: #FFFFFF !important;">
                    <span class="text-base font-bold">+</span> Add Another Set
                </button>
            </div>

            <template x-for="(q, idx) in questions" :key="q.id || idx">
                <div class="rounded-2xl border-2 border-purple-200 bg-gradient-to-br from-purple-50/40 to-slate-50 p-6 space-y-5 relative shadow-sm transition-all hover:border-purple-300">

                    {{-- Set Header --}}
                    <div class="flex items-center justify-between border-b border-purple-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center rounded-full bg-purple-700 px-3.5 py-1 text-xs font-black text-white shadow-sm" style="background-color: #7C3AED !important; color: #FFFFFF !important;" x-text="`Set #${idx + 1}`"></span>
                            <span class="text-xs font-bold text-purple-900">Photo & Question</span>
                        </div>

                        <button type="button" @click="removeQuestion(idx)" x-show="questions.length > 1" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1">
                            <span>🗑️</span> Delete Set
                        </button>
                    </div>

                    {{-- A. Set Photo Upload (Single file input per set to prevent duplicate name collision) --}}
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                <span x-text="`Set #${idx + 1} Memory Photo *`"></span>
                            </label>
                            <span x-show="q.selectedFileName"
                                  class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-extrabold"
                                  style="background-color: #D1FAE5 !important; color: #065F46 !important; border: 1px solid #34D399 !important;">
                                ✓ New Photo Ready: <span x-text="q.selectedFileName"></span>
                            </span>
                        </div>

                        <input type="hidden" :name="`questions[${idx}][image_url]`" :value="q.image_url || ''">

                        {{-- Dropzone when no preview --}}
                        <label x-show="!q.imagePreview && !q.image_url"
                               :for="`q_file_input_${idx}`"
                               @dragover.prevent="q.dragover = true"
                               @dragleave.prevent="q.dragover = false"
                               @drop.prevent="handleQuestionDrop($event, idx)"
                               :class="q.dragover ? 'border-purple-600 bg-purple-100' : 'border-purple-300 bg-white'"
                               class="block border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all hover:bg-purple-50/50 relative">
                            <div class="text-3xl text-purple-600 mb-1">📸</div>
                            <p class="text-xs font-bold text-slate-800">Drag & drop photo for this question, or click to browse</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">JPG, PNG, WEBP up to 20 MB</p>
                        </label>

                        {{-- Photo Preview when uploaded/existing --}}
                        <div x-show="q.imagePreview || q.image_url"
                             class="relative rounded-xl overflow-hidden border-2 border-purple-200 bg-slate-900/5 p-3 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <label :for="`q_file_input_${idx}`" class="cursor-pointer flex-1 flex items-center justify-center" title="Click image to change photo">
                                <img :src="q.imagePreview || q.image_url" class="max-h-52 rounded-lg object-contain shadow-sm border border-slate-200 bg-white p-1">
                            </label>

                            <div class="flex sm:flex-col items-center justify-center gap-2.5 shrink-0">
                                <label :for="`q_file_input_${idx}`"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-extrabold shadow-md cursor-pointer transition-transform hover:scale-105"
                                       style="background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%) !important; color: #FFFFFF !important; border: 1.5px solid #F5CB66 !important;">
                                    <span>📸</span>
                                    <span>Change / Edit Photo</span>
                                </label>

                                <button type="button"
                                        @click="removeQuestionImage(idx)"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2 text-xs font-extrabold shadow-md cursor-pointer transition-transform hover:scale-105"
                                        style="background-color: #E11D48 !important; color: #FFFFFF !important; border: 1px solid #BE123C !important;">
                                    <span>🗑️</span>
                                    <span>Remove Photo</span>
                                </button>
                            </div>
                        </div>

                        {{-- Direct Visible File Input Bar (Always accessible for quick local upload/edit) --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-xl border border-purple-200 bg-white px-3.5 py-2.5">
                            <span class="text-xs font-bold text-slate-700">Upload / Replace from Computer:</span>
                            <input type="file"
                                   :id="`q_file_input_${idx}`"
                                   :name="`questions[${idx}][image_file]`"
                                   @change="handleQuestionFileSelect($event, idx)"
                                   accept="image/*"
                                   class="block w-full sm:w-auto text-xs text-slate-700 font-semibold cursor-pointer">
                        </div>
                    </div>

                    {{-- B. Question Text Input --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1" x-text="`Question Text *`"></label>
                        <input type="text" :name="`questions[${idx}][question_text]`" x-model="q.question_text" required placeholder="e.g. What color was the umbrella on the right side of the photo?"
                               class="w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-purple-600 focus:outline-none bg-white font-medium">
                    </div>

                    {{-- C. 4 Options A B C D --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Answer Choices (Select the correct radio button) *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="optKey in ['A', 'B', 'C', 'D']" :key="optKey">
                                <div class="p-3 rounded-xl border transition-all"
                                     :class="q.correct_option === optKey ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500' : 'border-slate-200 bg-white'">
                                    <div class="flex items-center gap-2 mb-1">
                                        <input type="radio" :name="`questions[${idx}][correct_option]`" :value="optKey" x-model="q.correct_option" :id="`q_${idx}_opt_${optKey}`" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                        <label :for="`q_${idx}_opt_${optKey}`" class="font-extrabold text-xs text-purple-900 cursor-pointer" x-text="`Option ${optKey}`"></label>
                                        <span x-show="q.correct_option === optKey" class="ml-auto text-[11px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">
                                            ✓ Correct Answer
                                        </span>
                                    </div>
                                    <input type="text" :name="`questions[${idx}][option_${optKey.toLowerCase()}]`" x-model="q[`option_${optKey.toLowerCase()}`]" required :placeholder="`Enter choice ${optKey}...`"
                                           class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs focus:border-purple-600 focus:outline-none">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Bottom Add Set Button --}}
            <div class="text-center pt-2">
                <button type="button" @click="addQuestion()" class="inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-purple-300 px-6 py-3 text-sm font-bold text-purple-700 hover:bg-purple-50 transition-all">
                    <span>➕</span> Add Another Image & Question Set
                </button>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
            <a href="{{ route('admin.games.memory_snap.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Cancel
            </a>

            <button type="submit" class="rounded-xl bg-purple-700 px-6 py-2.5 text-sm font-extrabold text-white shadow-md hover:bg-purple-800 transition-all" style="background-color: #7C3AED !important; color: #FFFFFF !important;">
                Save Memory Challenge
            </button>
        </div>
    </form>
</div>

<script>
function memorySnapForm() {
    return {
        isEdit: @json($isEdit),
        imageUrl: @json($challenge->image_url ?? null),
        questions: @json($initialQuestions).map(q => ({
            ...q,
            dragover: false,
            selectedFileName: '',
            imagePreview: q.image_url || null
        })),

        addQuestion() {
            this.questions.push({
                id: Date.now(),
                image_url: '',
                imagePreview: null,
                selectedFileName: '',
                dragover: false,
                question_text: '',
                option_a: '',
                option_b: '',
                option_c: '',
                option_d: '',
                correct_option: 'A'
            });
        },

        removeQuestion(index) {
            if (this.questions.length > 1) {
                this.questions.splice(index, 1);
            }
        },

        triggerFileInput(idx) {
            const fileInput = document.getElementById(`q_file_input_${idx}`);
            if (fileInput) {
                fileInput.click();
            }
        },

        handleQuestionFileSelect(e, idx) {
            const file = e.target.files[0];
            if (file && this.questions[idx]) {
                this.questions[idx].imagePreview = URL.createObjectURL(file);
                this.questions[idx].selectedFileName = file.name;
                this.questions[idx].image_url = '';
            }
        },

        handleQuestionDrop(e, idx) {
            if (!this.questions[idx]) return;
            this.questions[idx].dragover = false;
            const file = e.dataTransfer.files[0];
            if (file) {
                this.questions[idx].imagePreview = URL.createObjectURL(file);
                this.questions[idx].selectedFileName = file.name;
                this.questions[idx].image_url = '';
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    const fileInput = document.getElementById(`q_file_input_${idx}`);
                    if (fileInput) fileInput.files = dt.files;
                } catch (err) {}
            }
        },

        removeQuestionImage(idx) {
            if (this.questions[idx]) {
                this.questions[idx].imagePreview = null;
                this.questions[idx].selectedFileName = '';
                this.questions[idx].image_url = '';
                const fileInput = document.getElementById(`q_file_input_${idx}`);
                if (fileInput) fileInput.value = '';
            }
        }
    }
}
</script>
@endsection
