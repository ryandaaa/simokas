<x-layouts.app :title="__('home.help.title')" :navItems="$navItems">
    <div class="ds-sans space-y-12 pb-12">
        <div class="text-center max-w-2xl mx-auto space-y-3">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __("home.help_eyebrow") }}</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-ds-ink tracking-tight">{{ __('home.help.heading') }}</h1>
            <p class="text-sm sm:text-base text-ds-muted leading-relaxed">{{ __('home.help.intro') }}</p>
        </div>

        <section aria-labelledby="help-faq-heading">
            <div class="text-center max-w-2xl mx-auto mb-8 space-y-2">
                <h2 id="help-faq-heading" class="text-2xl font-extrabold text-ds-ink tracking-tight">
                    {{ __('home.help.faq_title') }}
                </h2>
            </div>
            <div class="max-w-3xl mx-auto space-y-4" x-data="{ open: 1 }">
                @foreach ([
                    ['q' => __('home.help.faq_q1'), 'a' => __('home.help.faq_a1')],
                    ['q' => __('home.help.faq_q4'), 'a' => __('home.help.faq_a4')],
                    ['q' => __('home.help.faq_q5'), 'a' => __('home.help.faq_a5')],
                    ['q' => __('home.help.faq_q6'), 'a' => __('home.help.faq_a6')],
                ] as $i => $faq)
                    <div class="border border-ds-line/80 rounded-2xl overflow-hidden bg-white transition-all shadow-xs">
                        <button @click="open = (open === {{ $i + 1 }} ? 0 : {{ $i + 1 }})" :aria-expanded="(open === {{ $i + 1 }}).toString()" aria-controls="help-faq-{{ $i + 1 }}" class="w-full px-6 py-4 min-h-[44px] text-left flex items-center justify-between font-bold text-sm text-ds-ink hover:text-ds-blue transition-colors">
                            <span>{{ $faq['q'] }}</span>
                            <i aria-hidden="true" class="fa-solid text-xs text-ds-faint transition-transform duration-200" :class="open === {{ $i + 1 }} ? 'fa-minus rotate-180' : 'fa-plus'"></i>
                        </button>
                        <div id="help-faq-{{ $i + 1 }}" role="region" x-show="open === {{ $i + 1 }}" x-cloak
                             x-transition:enter="transition-all ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition-all ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="px-6 pb-4 text-xs text-ds-muted leading-relaxed border-t border-ds-line/60 pt-3">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="help-cta-heading">
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 sm:p-8 max-w-3xl mx-auto text-center">
                <h2 id="help-cta-heading" class="text-xl font-extrabold text-ds-ink">
                    {{ __('home.help.cta_title') }}
                </h2>
                <p class="mt-2 text-sm text-ds-muted">
                    {{ __('home.help.cta_message') }}
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row justify-center">
                    <a href="{{ route('catalog.how-it-works') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                        <i aria-hidden="true" class="fa-solid fa-file-lines text-xs"></i>
                        {{ __('home.help.cta_how') }}
                    </a>
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                        <i aria-hidden="true" class="fa-solid fa-magnifying-glass text-xs"></i>
                        {{ __('home.help.cta_browse') }}
                    </a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
