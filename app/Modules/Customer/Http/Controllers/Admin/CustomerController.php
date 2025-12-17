<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Customer\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // On utilise le scopeSearch créé juste avant
        $customers = Customer::query()
            ->search($request->search)
            ->when($request->status && $request->status !== 'all', function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest() // Toujours trier par date de création par défaut
            ->paginate($request->per_page ?? 10);

        return $this->successResponse($customers);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email'],
            // On peut générer un mdp aléatoire ou demander à l'admin
            // Pour l'exemple, on met un défaut ou on le rend nullable
        ]);

        // Création du client
        $customer = Customer::create([
            ...$validated,
            'password' => Hash::make(Str::random(16)), // Mot de passe temporaire
            'status' => 'active',
            'preferences' => [], // Valeur par défaut pour le JSON
        ]);

        return $this->createdResponse($customer, 'Customer created successfully.');
    }

    /**
     * Delete a single customer.
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return $this->deletedResponse('Customer deleted successfully.');
    }

    /**
     * Delete multiple customers (Bulk Action).
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:customers,id']
        ]);

        Customer::whereIn('id', $request->ids)->delete();

        return $this->deletedResponse('Selected customers deleted successfully.');
    }
}
