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

            {{-- Individual WP Results Matrix --}}
            @if ($userId)
                <x-filament::section>
                    <x-slot name="heading">
                        Individual WP Results Matrix
                    </x-slot>

                    <x-slot name="description">
                        Viewing detailed WP calculations from:
                        <strong>{{ \App\Models\User::find($userId)?->name }}</strong>
                    </x-slot>

                    @include('filament.resources.wp-results.components.view-wp-matrix', [
                        'alternatives' => $this->getAlternatives(),
                        'criteria' => $this->getCriteria(),
                        'matrixData' => $this->getMatrixData(),
                        'totalSVector' => $this->getTotalSVector(),
                    ])
                </x-filament::section>
            @endif

            {{-- Results Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    Final Borda Ranking (Aggregated Results)
                </x-slot>

                <x-slot name="description">
                    Shows aggregated rankings from all decision makers using the Borda method
                </x-slot>

                @if ($bordaMatrix && count($bordaMatrix['data']) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th
                                        class="px-4 py-3 text-left font-semibold border border-gray-300 dark:border-gray-600">
                                        Alternatives
                                    </th>
                                    <th colspan="{{ $bordaMatrix['max_rank'] }}"
                                        class="px-4 py-3 text-center font-semibold border border-gray-300 dark:border-gray-600">
                                        Ranking
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center font-semibold border border-gray-300 dark:border-gray-600">
                                        Borda Point
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center font-semibold border border-gray-300 dark:border-gray-600">
                                        Borda Value
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center font-semibold border border-gray-300 dark:border-gray-600">
                                        Rank
                                    </th>
                                </tr>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600"></th>
                                    @for ($i = 1; $i <= $bordaMatrix['max_rank']; $i++)
                                        <th
                                            class="px-4 py-2 text-center font-semibold border border-gray-300 dark:border-gray-600">
                                            {{ $i }}
                                        </th>
                                    @endfor
                                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600"></th>
                                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600"></th>
                                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bordaMatrix['data'] as $altId => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-3 font-medium border border-gray-300 dark:border-gray-600">
                                            <div class="space-y-1">
                                                <div class="font-semibold">{{ $row['alternative_code'] }}</div>
                                                <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    {{ $row['alternative_name'] }}</div>
                                            </div>
                                        </td>
                                        @for ($rank = 1; $rank <= $bordaMatrix['max_rank']; $rank++)
                                            <td
                                                class="px-4 py-3 text-center border border-gray-300 dark:border-gray-600 {{ $row['ranks'][$rank] > 0 ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                                @if ($row['ranks'][$rank] > 0)
                                                    {{ number_format($row['ranks'][$rank], 5) }}
                                                @endif
                                            </td>
                                        @endfor
                                        <td
                                            class="px-4 py-3 text-center font-semibold border border-gray-300 dark:border-gray-600">
                                            {{ number_format($row['borda_points'], 7) }}
                                        </td>
                                        <td class="px-4 py-3 text-center border border-gray-300 dark:border-gray-600">
                                            {{ number_format($row['borda_value'], 7) }}
                                        </td>
                                        <td class="px-4 py-3 text-center border border-gray-300 dark:border-gray-600">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded-full
                                                {{ $row['final_rank'] == 1 ? 'bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100' : '' }}
                                                {{ $row['final_rank'] > 1 && $row['final_rank'] <= 3 ? 'bg-warning-100 text-warning-800 dark:bg-warning-800 dark:text-warning-100' : '' }}
                                                {{ $row['final_rank'] > 3 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}">
                                                {{ $row['final_rank'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                    <td colspan="{{ $bordaMatrix['max_rank'] + 1 }}"
                                        class="px-4 py-3 text-right border border-gray-300 dark:border-gray-600">
                                        TOTAL
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center bg-yellow-100 dark:bg-yellow-900/30 border border-gray-300 dark:border-gray-600">
                                        {{ number_format($bordaMatrix['total_borda_points'], 5) }}
                                    </td>
                                    <td colspan="2" class="border border-gray-300 dark:border-gray-600"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        <p>No results available. Please calculate results first.</p>
                    </div>
                @endif
            </x-filament::section>

            {{-- Simple Summary Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    Final Rankings Summary
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
