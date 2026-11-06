@php
    $selectedClientId = old('client_id', $selectedClientId ?? ($project->client_id ?? null));
    $selectedContactId = old('client_contact_id', $project->client_contact_id ?? null);
    $contactsByClient = $contactsByClient ?? [];
@endphp

<div
    class="mb-3"
    x-data="{
        clientId: @js($selectedClientId ? (string) $selectedClientId : ''),
        contactId: @js($selectedContactId ? (string) $selectedContactId : ''),
        contactsByClient: @js($contactsByClient),
        get contacts() {
            if (! this.clientId) return [];
            return this.contactsByClient[this.clientId] || [];
        },
        onClientChange() {
            const ids = this.contacts.map(c => String(c.id));
            if (! ids.includes(String(this.contactId))) {
                this.contactId = '';
            }
        }
    }"
>
    <div class="mb-3">
        <label class="form-label" for="client_id">Klient (kontrahent)</label>
        <select
            id="client_id"
            name="client_id"
            class="form-select @error('client_id') is-invalid @enderror"
            x-model="clientId"
            @change="onClientChange()"
        >
            <option value="">— bez klienta —</option>
            @foreach($clients as $clientOption)
                <option value="{{ $clientOption->id }}">{{ $clientOption->name }}</option>
            @endforeach
        </select>
        @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">
            Kartoteka: <a href="{{ route('clients.index') }}">Zasoby → Kontrahenci</a> (tylko typ Klient)
        </div>
    </div>

    <div>
        <label class="form-label" for="client_contact_id">Osoba kontaktowa</label>
        <select
            id="client_contact_id"
            name="client_contact_id"
            class="form-select @error('client_contact_id') is-invalid @enderror"
            x-model="contactId"
            :disabled="! clientId"
        >
            <option value="">— brak / nieustalone —</option>
            <template x-for="contact in contacts" :key="contact.id">
                <option :value="String(contact.id)" x-text="contact.label"></option>
            </template>
        </select>
        @error('client_contact_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-text" x-show="clientId && contacts.length === 0" x-cloak>
            Ten klient nie ma jeszcze aktywnych osób kontaktowych — dodaj je w kartotece kontrahenta.
        </div>
        <div class="form-text" x-show="! clientId" x-cloak>
            Najpierw wybierz klienta, żeby wskazać osobę kontaktową.
        </div>
    </div>
</div>
