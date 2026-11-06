<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index');
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Kontrahent został dodany.');
    }

    public function show(Client $client): View
    {
        $client->loadCount('projects');

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        // Trzymaj denormalizowaną nazwę na projektach w sync z kartoteką.
        $client->projects()->update(['client_name' => $client->name]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Kontrahent został zaktualizowany.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->projects()->exists()) {
            return redirect()->route('clients.show', $client)
                ->with('error', 'Nie można usunąć kontrahenta z przypisanymi projektami. Najpierw odłącz projekty.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Kontrahent został usunięty.');
    }
}
