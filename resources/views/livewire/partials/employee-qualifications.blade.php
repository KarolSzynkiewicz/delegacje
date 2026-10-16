@if($employee->roles->isNotEmpty())
    <div class="emp-kv">
        @foreach($employee->roles as $role)
            <x-role-seniority-badge :role="$role" row />
        @endforeach
    </div>
@else
    <span class="text-muted">—</span>
@endif
