@php
    $keep = $keep ?? [];
    $currentId = $current->id;
    $baseUrl = route($routeName, $routeParams ?? []);
@endphp
<select
    class="form-select w-100 ms-md-auto"
    style="max-width: min(320px, 100%);"
    onchange="(function() {
        const baseUrl = @js($baseUrl);
        const params = new URLSearchParams();
        params.set('warehouse_id', this.value);
        @foreach($keep as $key => $value)
            @if(filled($value))
                params.set(@js($key), @js($value));
            @endif
        @endforeach
        window.location.href = baseUrl + '?' + params.toString();
    }).call(this)"
>
    @foreach($warehouses as $option)
        <option value="{{ $option->id }}" @selected($option->id === $currentId)>
            {{ $option->display_name }}{{ $option->is_default ? ' — siedziba' : '' }}
        </option>
    @endforeach
</select>
