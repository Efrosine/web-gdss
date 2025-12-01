<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Event Selection Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Event
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        @if ($selectedEventId)
            {{-- Evaluation Completeness Status --}}
            @if ($canManage && $completenessData)
                <x-filament::section>
                    <x-slot name="heading">
                        Evaluation Status
                    </x-slot>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="text-sm">
                                <span class="font-semibold">Completed:</span>
                                {{ $completenessData['completed_dms'] }} / {{ $completenessData['total_dms'] }} Decision
                                Makers
                            </div>

                            @if ($completenessData['is_complete'])
                                <x-filament::badge color="success">
                                    All Complete
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="warning">
                                    Incomplete
                                </x-filament::badge>
                            @endif
                        </div>

                        @if (!$completenessData['is_complete'])
                            <div class="text-sm space-y-2">
                                <p class="font-semibold text-warning-600 dark:text-warning-400">Missing Evaluations:</p>
                                <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                                    @foreach ($completenessData['dm_status'] as $dm)
                                        @if (!$dm['is_complete'])
                                            <li>
                                                {{ $dm['name'] }}
                                                @if ($dm['is_leader'])
                                                    <x-filament::badge color="info"
                                                        size="xs">Leader</x-filament::badge>
                                                @endif
                                                - Missing {{ $dm['missing_count'] }} evaluation(s)
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endif

            {{-- Results Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    Final Rankings (Borda Aggregation)
                </x-slot>

                {{ $this->table }}
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center text-gray-500 dark:text-gray-400 py-12">
                    <p>Please select an event to view results</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
