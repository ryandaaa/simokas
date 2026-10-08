<x-layouts.app :title="__('legal.terms.title')">
    <div class="ds-sans max-w-4xl mx-auto space-y-8 pb-12">
        <div class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 flex items-start gap-3">
            <i aria-hidden="true" class="fa-solid fa-triangle-exclamation text-ds-amber-ink mt-0.5"></i>
            <div>
                <p class="text-xs font-extrabold text-ds-ink">{{ __('legal.draft_label') }}</p>
                <p class="mt-1 text-xs text-ds-muted leading-relaxed">{{ __('legal.draft_notice') }}</p>
            </div>
        </div>

        <div class="text-center max-w-2xl mx-auto space-y-3">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">Legal</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-ds-ink tracking-tight">{{ __('legal.terms.title') }}</h1>
            <p class="text-sm sm:text-base text-ds-muted leading-relaxed">{{ __('legal.terms.intro') }}</p>
        </div>

        <div class="space-y-6">
            @foreach (__('legal.terms.sections') as $section)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-lg font-extrabold text-ds-ink mb-3">{{ $section['heading'] }}</h2>
                    @if (!empty($section['paragraphs']))
                        <div class="space-y-2 text-sm text-ds-ink leading-relaxed">
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    @endif
                    @if (!empty($section['items']))
                        <ul class="mt-3 list-disc list-inside space-y-1 text-sm text-ds-ink">
                            @foreach ($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('legal.privacy') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                <i aria-hidden="true" class="fa-solid fa-file-lines text-xs"></i>
                {{ __('legal.footer_privacy') }}
            </a>
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                <i aria-hidden="true" class="fa-solid fa-house text-xs"></i>
                {{ __('ui.layout.home') }}
            </a>
        </div>
    </div>
</x-layouts.app>
