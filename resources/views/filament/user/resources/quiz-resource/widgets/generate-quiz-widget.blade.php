<x-filament-widgets::widget>
    {{ $this->form }}

    <x-filament::modal id="field-counts" width="lg">
        <div class="flex flex-col gap-y-4">
        @foreach($counts as $label => $typeCounts)
            <div class="flex flex-col">
                <div class="text-lg mb-2 font-medium text-gray-900">{{ $label }}</div>
                @foreach($typeCounts as $key => $value)
                <div class="flex items-center justify-between">
                    <div class="text-gray-900">{{ $key }}</div>
                    <div class="ml-2 text-gray-500">{{ $value }}</div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </x-filament::modal>

</x-filament-widgets::widget>
