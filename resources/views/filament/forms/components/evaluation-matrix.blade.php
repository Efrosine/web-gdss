@php
    $eventId = $eventId ?? null;
    $alternatives = $alternatives ?? collect();
    $criteria = $criteria ?? collect();
@endphp

<div class="space-y-4">
    @if ($alternatives->isEmpty() || $criteria->isEmpty())
        <div class="rounded-lg bg-warning-50 dark:bg-warning-400/10 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
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
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 border-r border-gray-300 dark:border-gray-700">
                                    Alternative / Criterion
                                </th>
                                @foreach ($criteria as $criterion)
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
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
                                        class="sticky left-0 z-10 bg-white dark:bg-gray-900 whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                        <div class="space-y-1">
                                            <div>{{ $alternative->code }}</div>
                                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                {{ $alternative->name }}
                                            </div>
                                        </div>
                                    </td>
                                    @foreach ($criteria as $criterion)
                                        <td
                                            class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            <x-filament::input.wrapper>
                                                <x-filament::input type="number" step="0.01" min="1.00"
                                                    max="5.00"
                                                    wire:model="scores.{{ $alternative->id }}.{{ $criterion->id }}"
                                                    placeholder="1.00-5.00" class="text-center" />
                                            </x-filament::input.wrapper>
                                            @error("scores.{$alternative->id}.{$criterion->id}")
                                                <div class="text-xs text-danger-600 dark:text-danger-400 mt-1">
                                                    {{ $message }}
                                                </div>
                                            @enderror
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
                <div class="flex-shrink-0">
                    <x-filament::icon icon="heroicon-o-information-circle"
                        class="h-5 w-5 text-info-600 dark:text-info-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-info-800 dark:text-info-300">
                        Evaluation Guide
                    </h3>
                    <div class="mt-2 text-sm text-info-700 dark:text-info-400">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Enter scores between 1.00 (lowest) and 5.00 (highest) for each cell</li>
                            <li>All cells must be filled before saving</li>
                            <li>Saving will replace any previous evaluations for this event</li>
                            <li>Green badges indicate benefit criteria (higher is better)</li>
                            <li>Red badges indicate cost criteria (lower is better)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
