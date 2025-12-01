@php
    $alternatives = $alternatives ?? collect();
    $criteria = $criteria ?? collect();
    $matrixData = $matrixData ?? [];
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
                        <p>This event doesn't have any alternatives or criteria yet. Please add them before evaluating.
                        </p>
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
                                    Alternative / Criterion
                                </th>
                                @foreach ($criteria as $criterion)
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 min-w-[200px]">
                                        <div class="space-y-1">
                                            <div>{{ $criterion->name }}</div>
                                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                Weight: {{ $criterion->weight }}
                                                <span
                                                    class="ml-1 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                                    {{ $criterion->attribute_type === 'benefit'
                                                        ? 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30'
                                                        : 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30' }}">
                                                    {{ ucfirst($criterion->attribute_type) }}
                                                </span>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            @foreach ($alternatives as $alternative)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td
                                        class="sticky left-0 z-10 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                        <div class="space-y-1">
                                            <div>{{ $alternative->code }}</div>
                                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                {{ $alternative->name }}
                                            </div>
                                        </div>
                                    </td>
                                    @foreach ($criteria as $criterion)
                                        <td class="px-3 py-4 text-sm">
                                            @php
                                                $scores = $matrixData[$alternative->id][$criterion->id] ?? [];
                                            @endphp

                                            @if (empty($scores))
                                                <div class="text-center text-gray-400 dark:text-gray-600">
                                                    —
                                                </div>
                                            @else
                                                @php
                                                    $scoreData = $scores[0]; // Only one user now
                                                    $score = (float) $scoreData['score'];
                                                    $colorClass = match (true) {
                                                        $score >= 4.0
                                                            => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
                                                        $score >= 3.0
                                                            => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
                                                        default
                                                            => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
                                                    };
                                                @endphp
                                                <div class="text-center">
                                                    <span
                                                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-semibold ring-1 ring-inset {{ $colorClass }}">
                                                        {{ number_format($score, 2) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
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
                        Matrix Guide
                    </h3>
                    <div class="mt-2 text-sm text-info-700 dark:text-info-400">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Each cell displays the score for that alternative-criterion pair</li>
                            <li>Green badges indicate high scores (4.0-5.0)</li>
                            <li>Yellow badges indicate medium scores (3.0-3.99)</li>
                            <li>Red badges indicate low scores (1.0-2.99)</li>
                            <li>Empty cells (—) indicate no evaluation has been submitted yet</li>
                            <li>Use "Evaluate Alternatives (Matrix)" button to add or edit evaluations</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
