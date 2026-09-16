@if($canManageSiteLead && $pickEmployees->isNotEmpty())
    <form
        method="POST"
        action="{{ route('projects.site-leads.store', $project) }}"
        class="wo-lead-pick"
    >
        @csrf
        <input type="hidden" name="start_date" value="{{ $weekStart->format('Y-m-d') }}">
        <input type="hidden" name="week_start" value="{{ $weekStart->format('Y-m-d') }}">
        <div class="wo-lead-pick__field">
            <x-ui.input
                type="select"
                name="employee_id"
                :id="'wo-lead-emp-'.$project->id"
                label="Wyznacz z ekipy"
                required="true"
            >
                <option value="">Wybierz osobę</option>
                @foreach($pickEmployees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                @endforeach
            </x-ui.input>
        </div>
        <x-ui.button variant="primary" type="submit" class="btn-sm wo-lead-pick__btn">
            Przypisz
        </x-ui.button>
    </form>
@elseif($canManageSiteLead)
    <p class="small text-muted mb-0">{{ $emptyMessage }}</p>
@endif
