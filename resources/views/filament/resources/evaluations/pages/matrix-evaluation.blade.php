<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        @if ($this->data['event_id'] ?? null)
            <div class="mt-6">
                @php
                    $eventId = $this->data['event_id'];
                    $alternatives = \App\Models\Alternative::where('event_id', $eventId)->orderBy('code')->get();
                    $criteria = \App\Models\Criterion::where('event_id', $eventId)->orderBy('name')->get();
                @endphp

                <x-filament::section>
                    <x-slot name="heading">
                        Evaluation Matrix
                    </x-slot>
                    <x-slot name="description">
                        Fill in scores for each alternative against each criterion. Scores range from 1.00 (lowest) to
                        5.00 (highest).
                    </x-slot>

                    @include('filament.forms.components.evaluation-matrix', [
                        'eventId' => $eventId,
                        'alternatives' => $alternatives,
                        'criteria' => $criteria,
                    ])
                </x-filament::section>
            </div>
        @else
            <div class="mt-6">
                <x-filament::section>
                    <div class="text-center py-12">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Please select an event to start evaluating
                            alternatives.</p>
                    </div>
                </x-filament::section>
            </div>
        @endif

        <div class="mt-6 flex items-center justify-end gap-x-3">
            {{ $this->cancelAction }}
            {{ $this->saveAction }}
        </div>
    </form>
</x-filament-panels::page>
