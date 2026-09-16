@php
    $dateRange = $employeeData['date_range'] ?? 'cały tydzień';
    $isFullWeek = ($dateRange === 'cały tydzień' || $dateRange === 'pon-nie');
@endphp
<tr @class(['wo-assigned-row', 'is-site-lead' => $employeeData['is_site_lead'] ?? false])>
    <td data-label="Pracownik" data-sort-value="{{ mb_strtolower($employeeData['employee']->last_name.' '.$employeeData['employee']->first_name) }}">
        <div class="wo-emp">
            @if($employeeData['is_site_lead'] ?? false)
                <span class="wo-lead-tag">Kierownik</span>
            @endif
            <x-employee-cell :employee="$employeeData['employee']" />
            <div class="wo-emp-meta">
                <x-ui.rating
                    :score="$employeeData['latest_evaluation_score'] ?? null"
                    :evaluation="$employeeData['latest_evaluation'] ?? null"
                    :show-empty="true"
                />
                <span class="wo-emp-meta__rule" aria-hidden="true"></span>
                <x-planner-document-icons
                    :documents="$employeeData['planner_documents'] ?? []"
                    :show-empty="true"
                    stacked
                />
            </div>
        </div>
    </td>
    <td data-label="Rola w projekcie" data-sort-value="{{ mb_strtolower($employeeData['role']->name ?? '') }}">
        @if(isset($employeeData['role_stable']) && !$employeeData['role_stable'])
            <x-ui.badge variant="warning" title="Rola zmienia się w trakcie tygodnia">
                <i class="bi bi-arrow-left-right"></i> Zmienna
            </x-ui.badge>
        @elseif(($employeeData['role'] ?? null) && ($employeeData['assignment'] ?? null))
            @php
                $assignment = $employeeData['assignment'];
                $assignmentId = is_object($assignment) ? $assignment->id : $assignment;
            @endphp
            <x-role-seniority-badge
                :role="$employeeData['role']"
                :seniority="$employeeData['seniority'] ?? null"
                :href="route('project-assignments.edit', $assignmentId)"
            />
        @elseif($employeeData['role'] ?? null)
            <x-role-seniority-badge
                :role="$employeeData['role']"
                :seniority="$employeeData['seniority'] ?? null"
            />
        @endif
    </td>
    <td class="text-center {{ !$isFullWeek ? 'bg-danger bg-opacity-25' : '' }}" data-label="Pokrycie" data-sort-value="{{ $isFullWeek ? 1 : 0 }}">
        <span class="fw-semibold small">{{ $dateRange }}</span>
    </td>
    <td data-label="Auto" data-sort-value="{{ mb_strtolower($employeeData['vehicle']->registration_number ?? '') }}">
        @if(isset($employeeData['vehicle']) && $employeeData['vehicle'])
            <x-ui.clickable-badge variant="success" route="vehicle-assignments.show" :routeParams="['vehicle_assignment' => $employeeData['vehicle_assignment']]" title="{{ $employeeData['vehicle']->brand }} {{ $employeeData['vehicle']->model }}">
                <i class="bi bi-car-front"></i> {{ $employeeData['vehicle']->registration_number }}
            </x-ui.clickable-badge>
        @elseif($employeeData['has_vehicle_in_week'] ?? false)
            <x-ui.badge variant="success">
                <i class="bi bi-car-front"></i> Tak
            </x-ui.badge>
        @else
            <x-ui.clickable-badge variant="danger" route="vehicle-assignments.create" :routeParams="['employee_id' => $employeeData['employee']->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]">
                <i class="bi bi-x-circle"></i> Brak
            </x-ui.clickable-badge>
        @endif
    </td>
    <td data-label="Dom" data-sort-value="{{ mb_strtolower($employeeData['accommodation']->name ?? '') }}">
        @if(isset($employeeData['accommodation']) && $employeeData['accommodation'])
            @php
                $accommodationName = $employeeData['accommodation']->name;
                $accommodationDisplay = Str::limit($accommodationName, 32);
            @endphp
            <x-ui.clickable-badge variant="info" route="accommodation-assignments.show" :routeParams="['accommodation_assignment' => $employeeData['accommodation_assignment']]" title="{{ $accommodationName }}" class="wo-cell-truncate">
                <i class="bi bi-house"></i> {{ $accommodationDisplay }}
            </x-ui.clickable-badge>
        @else
            <x-ui.clickable-badge variant="danger" route="accommodation-assignments.create" :routeParams="['employee_id' => $employeeData['employee']->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]">
                <i class="bi bi-x-circle"></i> Brak
            </x-ui.clickable-badge>
        @endif
    </td>
    <td data-label="Do rotacji" data-sort-value="{{ $employeeData['rotation']['days_left'] ?? 999999 }}">
        @if(isset($employeeData['rotation']) && $employeeData['rotation'])
            @php
                $rotation = $employeeData['rotation']['rotation'] ?? null;
                $rotationId = $employeeData['rotation']['id'] ?? null;
                $daysLeft = $employeeData['rotation']['days_left'] ?? null;
                $employee = $employeeData['employee'];
            @endphp
            @if($rotation && $daysLeft !== null)
                @if($rotationId)
                    <x-ui.clickable-badge variant="warning" route="employees.rotations.edit" :routeParams="['employee' => $employee, 'rotation' => $rotation]">
                        <i class="bi bi-arrow-repeat"></i>
                        @if($daysLeft >= 0)
                            {{ $daysLeft }} {{ $daysLeft == 1 ? 'dzień' : 'dni' }}
                        @else
                            {{ abs($daysLeft) }} {{ abs($daysLeft) == 1 ? 'dzień' : 'dni' }} temu
                        @endif
                    </x-ui.clickable-badge>
                @else
                    <x-ui.badge variant="warning">
                        <i class="bi bi-arrow-repeat"></i>
                        @if($daysLeft >= 0)
                            {{ $daysLeft }} {{ $daysLeft == 1 ? 'dzień' : 'dni' }}
                        @else
                            {{ abs($daysLeft) }} {{ abs($daysLeft) == 1 ? 'dzień' : 'dni' }} temu
                        @endif
                    </x-ui.badge>
                @endif
            @else
                <x-ui.badge variant="warning">
                    <i class="bi bi-arrow-repeat"></i> Rotacja
                </x-ui.badge>
            @endif
        @else
            <span class="text-muted small">-</span>
        @endif
    </td>
</tr>
