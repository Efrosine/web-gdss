@php
    $alternatives = $alternatives ?? collect();
    $criteria = $criteria ?? collect();
    $matrixData = $matrixData ?? [];
    $totalSVector = $totalSVector ?? 0.0;
@endphp

<div class="space-y-4">
    @if ($alternatives->isEmpty() || $criteria->isEmpty())
        <div class="rounded-lg bg-warning-50 dark:bg-warning-400/10 p-4">
            <div class="flex">
                <div class="shrink-0">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle"
                        class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-warning-800 dark:text-warning-300">
                        No Data Available
                    </h3>
                    <div class="mt-2 text-sm text-warning-700 dark:text-warning-400">
                        <p>This event doesn't have any alternatives or criteria yet. Please add them before viewing WP
                            results.</p>
                    </div>
                </div>
            </div>
        </div>
    @elseif (empty($matrixData))
        <div class="rounded-lg bg-warning-50 dark:bg-warning-400/10 p-4">
            <div class="flex">
                <div class="shrink-0">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle"
                        class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-warning-800 dark:text-warning-300">
                        No Evaluations Found
                    </h3>
                    <div class="mt-2 text-sm text-warning-700 dark:text-warning-400">
                        <p>This decision maker hasn't submitted evaluations for this event yet.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div
                    class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 dark:ring-white dark:ring-opacity-10 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 border-r border-gray-300 dark:border-gray-700">
                                    Alternatives
                                </th>
                                @foreach ($criteria as $criterion)
                                    <th scope="col"
                                        class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-100 min-w-[120px]">
                                        <div class="space-y-1">
                                            <div>{{ $criterion->name }}</div>
                                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                W: {{ number_format($criterion->weight, 2) }}
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                                <th scope="col"
                                    class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-100 min-w-[120px] bg-primary-50 dark:bg-primary-900/20">
                                    S-Vector
                                </th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-100 min-w-[120px] bg-success-50 dark:bg-success-900/20">
                                    V-Vector
                                </th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-100 min-w-[100px] bg-gray-50 dark:bg-gray-800">
                                    Rank
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            @foreach ($alternatives as $alternative)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td
                                        class="sticky left-0 z-10 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                        <div class="space-y-1">
                                            <div class="font-semibold">{{ $alternative->code }}</div>
                                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                {{ $alternative->name }}
                                            </div>
                                        </div>
                                    </td>
                                    @php
                                        $altData = $matrixData[$alternative->id] ?? null;
                                    @endphp
                                    @foreach ($criteria as $criterion)
                                        <td class="px-3 py-4 text-sm text-center">
                                            @php
                                                $value = $altData['criteria'][$criterion->id] ?? null;
                                            @endphp

                                            @if ($value === null)
                                                <div class="text-center text-gray-400 dark:text-gray-600">
                                                    —
                                                </div>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-mono font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 ring-1 ring-inset ring-gray-500/10 dark:ring-gray-400/20">
                                                    {{ number_format($value, 4) }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-4 text-sm text-center bg-primary-50 dark:bg-primary-900/20">
                                        @if ($altData)
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1.5 text-sm font-mono font-semibold text-primary-700 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/40 ring-1 ring-inset ring-primary-600/20 dark:ring-primary-400/30">
                                                {{ number_format($altData['s_vector'], 4) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-sm text-center bg-success-50 dark:bg-success-900/20">
                                        @if ($altData)
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1.5 text-sm font-mono font-semibold text-success-700 dark:text-success-400 bg-success-100 dark:bg-success-900/40 ring-1 ring-inset ring-success-600/20 dark:ring-success-400/30">
                                                {{ number_format($altData['v_vector'], 4) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-sm text-center bg-gray-50 dark:bg-gray-800">
                                        @if ($altData && isset($altData['rank']))
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded-full
                                                {{ $altData['rank'] == 1 ? 'bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100' : '' }}
                                                {{ $altData['rank'] > 1 && $altData['rank'] <= 3 ? 'bg-warning-100 text-warning-800 dark:bg-warning-800 dark:text-warning-100' : '' }}
                                                {{ $altData['rank'] > 3 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}">
                                                {{ $altData['rank'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            {{-- TOTAL Row --}}
                            <tr class="bg-gray-100 dark:bg-gray-800 font-semibold">
                                <td
                                    class="sticky left-0 z-10 bg-gray-100 dark:bg-gray-800 px-3 py-4 text-sm text-gray-900 dark:text-gray-100 border-r border-gray-300 dark:border-gray-700">
                                    TOTAL
                                </td>
                                @foreach ($criteria as $criterion)
                                    <td class="px-3 py-4 text-sm text-center">
                                        {{-- Empty cells for criteria columns --}}
                                    </td>
                                @endforeach
                                <td class="px-3 py-4 text-sm text-center bg-primary-100 dark:bg-primary-900/30">
                                    <span
                                        class="inline-flex items-center rounded-md px-3 py-2 text-base font-mono font-bold text-primary-800 dark:text-primary-300 bg-primary-200 dark:bg-primary-900/50 ring-2 ring-inset ring-primary-600/30 dark:ring-primary-400/40">
                                        {{ number_format($totalSVector, 4) }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-sm text-center bg-gray-100 dark:bg-gray-800">
                                    {{-- Empty cell for Rank column in TOTAL row --}}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-info-50 dark:bg-info-400/10 p-4">
            <div class="flex">
                <div class="shrink-0">
                    <x-filament::icon icon="heroicon-o-information-circle"
                        class="h-5 w-5 text-info-600 dark:text-info-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-info-800 dark:text-info-300">
                        WP Results Matrix Guide
                    </h3>
                    <div class="mt-2 text-sm text-info-700 dark:text-info-400">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Table Preference vector S:</strong> Shows the calculated power-by-weight values
                                for each alternative-criterion combination</li>
                            <li><strong>Cell Values (C1, C2, ...):</strong> Each cell displays score<sup>power</sup>,
                                where power = ±(weight/total_weight) based on benefit/cost attribute</li>
                            <li><strong>S-Vector Column:</strong> The sum of all power-by-weight values for each
                                alternative</li>
                            <li><strong>V-Vector Column:</strong> Normalized preference (S-Vector / Total S-Vector), sum
                                equals 1.0</li>
                            <li><strong>TOTAL Row:</strong> Sum of all S-Vectors and V-Vectors across all alternatives
                            </li>
                            <li><strong>Formula:</strong> For benefit criteria, power is positive; for cost criteria,
                                power is negative</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
