<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Users by route"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="grouped by path, past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.cursor-arrow-rays />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($matrixUsers->isEmpty() || $matrixRows->isEmpty())
            <x-pulse::no-results />
        @else
            <div class="overflow-x-auto">
                <x-pulse::table class="min-w-full">
                    <x-pulse::thead>
                        <tr>
                            <x-pulse::th class="sticky left-0 z-20 bg-white dark:bg-gray-900 whitespace-nowrap">Route</x-pulse::th>
                            @foreach ($matrixUsers as $user)
                                <x-pulse::th class="text-right whitespace-nowrap" title="{{ $user->name }}{{ $user->extra ? ' · '.$user->extra : '' }}">
                                    {{ \Illuminate\Support\Str::limit($user->name, 16) }}
                                </x-pulse::th>
                            @endforeach
                            <x-pulse::th class="text-right whitespace-nowrap">Total</x-pulse::th>
                        </tr>
                    </x-pulse::thead>
                    <tbody>
                        @foreach ($matrixRows as $row)
                            <tr wire:key="{{ $row->key }}-spacer" class="h-2 first:h-0"></tr>
                            <tr wire:key="{{ $row->key }}-row">
                                <x-pulse::td class="sticky left-0 z-10 max-w-[24rem] bg-gray-50 dark:bg-gray-800 {{ $row->has_children ? 'font-medium' : '' }}">
                                    <div class="flex items-center gap-1.5 min-w-0" style="padding-left: {{ $row->depth * 1.05 }}rem">
                                        @if ($row->has_children)
                                            <button
                                                type="button"
                                                wire:click="toggleGroup({{ \Illuminate\Support\Js::from($row->key) }})"
                                                class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="{{ $row->expanded ? 'Collapse '.$row->path : 'Expand '.$row->path }}"
                                                aria-expanded="{{ $row->expanded ? 'true' : 'false' }}"
                                            >
                                                <span class="text-sm leading-none">{{ $row->expanded ? '▾' : '▸' }}</span>
                                            </button>
                                        @else
                                            <span class="shrink-0 w-6"></span>
                                        @endif
                                        <span class="min-w-0 truncate text-xs" title="{{ $row->path }}">
                                            @if ($row->is_self)
                                                <span class="italic text-gray-600 dark:text-gray-300">{{ $row->label }}</span>
                                            @elseif ($row->depth === 0)
                                                <code class="text-gray-900 dark:text-gray-100">/{{ $row->label }}</code>
                                            @else
                                                <code>
                                                    <span class="text-gray-400 dark:text-gray-500">{{ $row->parent_path }}/</span><span class="text-gray-900 dark:text-gray-100">{{ $row->label }}</span>
                                                </code>
                                            @endif
                                        </span>
                                    </div>
                                </x-pulse::td>
                                @foreach ($matrixUsers as $user)
                                    @php $value = $row->cells[$user->id] ?? 0; @endphp
                                    <x-pulse::td numeric class="{{ $value > 0 ? 'text-gray-900 dark:text-gray-100 font-semibold' : 'text-gray-300 dark:text-gray-600' }}">
                                        {{ number_format($value) }}
                                    </x-pulse::td>
                                @endforeach
                                <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                    {{ number_format($row->total) }}
                                </x-pulse::td>
                            </tr>
                        @endforeach
                        <tr wire:key="matrix-total-spacer" class="h-2"></tr>
                        <tr wire:key="matrix-total-row">
                            <x-pulse::td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 font-semibold">
                                Total
                            </x-pulse::td>
                            @foreach ($matrixUsers as $user)
                                <x-pulse::td numeric class="font-bold text-gray-900 dark:text-gray-100">
                                    {{ number_format($columnTotals[$user->id] ?? 0) }}
                                </x-pulse::td>
                            @endforeach
                            <x-pulse::td numeric class="font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format(array_sum($columnTotals)) }}
                            </x-pulse::td>
                        </tr>
                    </tbody>
                </x-pulse::table>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
