<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Customer\Services\CustomerService;

/**
 * Address Controller
 */
class AddressController extends ApiController
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * List customer addresses
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();
        return $this->successResponse($addresses);
    }

    /**
     * Create new address
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:shipping,billing',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'sometimes|boolean',
        ]);

        $address = $this->customerService->addAddress(
            $request->user()->id,
            $validated
        );

        return $this->createdResponse($address, 'Address created');
    }

    /**
     * Update address
     */
    public function update(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:shipping,billing',
            'address_line_1' => 'sometimes|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:100',
        ]);

        $address = $this->customerService->updateAddress($id, $validated);

        return $this->successResponse($address, 'Address updated');
    }

    /**
     * Delete address
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        // Verify ownership
        $address = $request->user()->addresses()->findOrFail($id);
        
        $this->customerService->deleteAddress($id);

        return $this->successResponse(null, 'Address deleted');
    }

    /**
     * Set address as default
     */
    public function setDefault(string $id, Request $request): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);
        
        // Unset other defaults of same type
        $request->user()->addresses()
            ->where('type', $address->type)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return $this->successResponse($address, 'Default address updated');
    }
}
