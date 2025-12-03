<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Customer\Services\CustomerService;

/**
 * Customer Controller
 */
class CustomerController extends ApiController
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * Update customer profile
     */
    public function updateProfile(\Modules\Customer\Http\Requests\UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = $this->customerService->updateProfile(
            $request->user()->id,
            $validated
        );

        return $this->successResponse($customer, 'Profile updated');
    }

    /**
     * Change password
     */
    public function changePassword(\Modules\Customer\Http\Requests\ChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->customerService->changePassword(
            $request->user()->id,
            $validated['current_password'],
            $validated['password']
        );

        return $this->successResponse(null, 'Password changed successfully');
    }
}
