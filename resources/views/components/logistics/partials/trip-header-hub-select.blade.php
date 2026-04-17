{{--
  Kolumna Start/Cel w nagłówku transportu publicznego.
  Dołączaj wyłącznie przez @include z kontekstu Livewire (dostęp do $this->availablePublicTransportHubs).
  Zmienne: $label, $wireField, $peerLocationId, $missing
--}}
<label class="form-label small mb-1 {{ $missing ? 'text-danger fw-semibold' : 'text-muted' }}">
    {{ $label }} <span class="text-danger">*</span>
</label>
<select wire:model.live="{{ $wireField }}"
        class="form-select w-100 logistics-trip-header-control @if($errors->has($wireField) || $missing) is-invalid @endif">
    <option value="">— wybierz —</option>
    @foreach($this->availablePublicTransportHubs as $hub)
        <option value="{{ $hub->id }}"
            @disabled(!empty($peerLocationId) && (int)$peerLocationId === (int)$hub->id)>
            {{ $hub->name }}
        </option>
    @endforeach
</select>
@error($wireField)
    <div class="invalid-feedback d-block logistics-trip-header-hint">{{ $message }}</div>
@enderror
