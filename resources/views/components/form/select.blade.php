{{-- $options is a list of ['id' => ..., 'name' => ...] — master-data rows
     (MasterDataCache::present()) and hardcoded enums are shaped the same way
     at the call site so this component never needs to know which it got.
     Enhanced with a custom dropdown panel using `.kg-left-scroll` so every
     dropdown across the application has a smooth left-side scrollbar while
     staying 100% synced with the underlying `<select>` (including dynamic
     cascade updates via MutationObserver). --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'valueKey' => 'id'])

<div class="relative" data-kg-select-wrap>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>

    {{-- Hidden native select keeps form submissions & cascade JS (getElementById) working seamlessly --}}
    <select
        id="{{ $name }}" name="{{ $name }}"
        {{ $attributes->merge(['class' => 'sr-only']) }}
        tabindex="-1"
        aria-hidden="true"
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $option)
            <option value="{{ $option[$valueKey] }}" @selected((string) old($name, $value) === (string) $option[$valueKey])>
                {{ $option['name'] }}
            </option>
        @endforeach
    </select>

    {{-- Custom dropdown trigger button --}}
    <button
        type="button"
        data-kg-select-btn
        class="mt-1 flex w-full items-center justify-between rounded-md border bg-white px-3 py-2 text-left text-sm transition disabled:cursor-not-allowed disabled:opacity-60"
        style="border-color:var(--border-default)"
    >
        <span data-kg-select-label class="truncate"></span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" style="color:var(--text-muted)">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Left-side scrollable dropdown list --}}
    <div
        data-kg-select-panel
        class="hidden absolute z-30 mt-1 w-full overflow-hidden rounded-md border bg-white shadow-lg"
        style="border-color:var(--border-default)"
    >
        <div class="kg-left-scroll p-1" style="max-height: 210px; overflow-y: auto; direction: rtl;">
            <div data-kg-select-list class="kg-left-scroll-inner space-y-0.5" style="direction: ltr;"></div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        function initKgSelect(wrap) {
            if (wrap.dataset.kgInit) return;
            wrap.dataset.kgInit = '1';

            var selectEl = wrap.querySelector('select');
            var btn = wrap.querySelector('[data-kg-select-btn]');
            var labelEl = wrap.querySelector('[data-kg-select-label]');
            var panel = wrap.querySelector('[data-kg-select-panel]');
            var listEl = wrap.querySelector('[data-kg-select-list]');
            if (!selectEl || !btn || !labelEl || !panel || !listEl) return;

            function syncFromSelect() {
                btn.disabled = selectEl.disabled;
                var selectedOpt = selectEl.options[selectEl.selectedIndex];
                var text = selectedOpt ? selectedOpt.textContent.trim() : '';
                var val = selectedOpt ? selectedOpt.value : '';
                labelEl.textContent = text || 'Select';
                labelEl.style.color = val === '' ? 'var(--text-muted)' : 'var(--text-primary)';

                listEl.innerHTML = '';
                Array.from(selectEl.options).forEach(function (opt) {
                    var itemBtn = document.createElement('button');
                    itemBtn.type = 'button';
                    var isSelected = (opt.value === selectEl.value);
                    itemBtn.className = 'flex w-full items-center justify-between rounded px-2.5 py-1.5 text-left text-sm transition ' +
                        (isSelected ? 'bg-primary-50 font-semibold text-primary-700' : 'hover:bg-pink-50/60 text-ink-900');
                    itemBtn.textContent = opt.textContent.trim();
                    itemBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        selectEl.value = opt.value;
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                        syncFromSelect();
                        panel.classList.add('hidden');
                    });
                    listEl.appendChild(itemBtn);
                });
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var isHidden = panel.classList.contains('hidden');
                document.querySelectorAll('[data-kg-select-panel]').forEach(function (p) {
                    p.classList.add('hidden');
                });
                if (isHidden) {
                    syncFromSelect();
                    panel.classList.remove('hidden');
                }
            });

            selectEl.addEventListener('change', syncFromSelect);

            var observer = new MutationObserver(function () {
                syncFromSelect();
            });
            observer.observe(selectEl, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'value'] });

            syncFromSelect();
        }

        function initAllKgSelects() {
            document.querySelectorAll('[data-kg-select-wrap]').forEach(initKgSelect);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAllKgSelects);
        } else {
            initAllKgSelects();
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-kg-select-wrap]')) {
                document.querySelectorAll('[data-kg-select-panel]').forEach(function (p) {
                    p.classList.add('hidden');
                });
            }
        });
    })();
    </script>
    @endpush
@endonce
