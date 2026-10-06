<x-layouts.app :title="__('legal.privacy.title')">
    <div class="max-w-4xl mx-auto space-y-6">
        <x-ui.alert variant="warning" :title="__('legal.draft_label')" :message="__('legal.draft_notice')" />

        <div class="space-y-2">
            <h1 class="text-2xl sm:text-3xl font-bold text-text">{{ __('legal.privacy.title') }}</h1>
            <p class="text-sm sm:text-base text-muted leading-relaxed">{{ __('legal.privacy.intro') }}</p>
        </div>

        <div class="space-y-6">
            @foreach (__('legal.privacy.sections') as $section)
                <x-ui.card>
                    <h2 class="text-lg font-bold text-text mb-3">{{ $section['heading'] }}</h2>
                    @if (!empty($section['paragraphs']))
                        <div class="space-y-2 text-sm text-text leading-relaxed">
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    @endif
                    @if (!empty($section['items']))
                        <ul class="mt-3 list-disc list-inside space-y-1 text-sm text-text">
                            @foreach ($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endforeach
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <x-ui.button :href="route('legal.terms')" variant="secondary" icon="dokumen">
                {{ __('legal.footer_terms') }}
            </x-ui.button>
            <x-ui.button :href="url('/')" variant="ghost" icon="rumah">
                {{ __('ui.layout.home') }}
            </x-ui.button>
        </div>
    </div>
</x-layouts.app>
