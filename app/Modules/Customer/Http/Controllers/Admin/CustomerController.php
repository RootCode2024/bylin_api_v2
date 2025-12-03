<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Customer\Models\Customer;

class CustomerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($customers);
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::with(['addresses', 'orders'])->findOrFail($id);
        return $this->successResponse($customer);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->all());
        return $this->successResponse($customer, 'Customer updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return $this->successResponse(null, 'Customer deleted');
    }
}
