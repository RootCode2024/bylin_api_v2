<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Customer\Models\Customer;
use Modules\Customer\Exports\CustomersExport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends ApiController
{
    /**
     * List all customers with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->with(['addresses'])
            ->search($request->search)
            ->when($request->status && $request->status !== 'all', function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->has('with_trashed') && $request->with_trashed, function ($q) {
                $q->withTrashed();
            })
            ->when($request->has('only_trashed') && $request->only_trashed, function ($q) {
                $q->onlyTrashed();
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($customers);
    }

    /**
     * Show a single customer
     */
    public function show(string $id): JsonResponse
    {
        $customer = Customer::withTrashed()
            ->with(['addresses', 'roles'])
            ->findOrFail($id);

        return $this->successResponse($customer);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'send_credentials' => ['nullable', 'boolean'],
        ]);

        // Générer un mot de passe temporaire
        $temporaryPassword = Str::random(16);

        $customer = Customer::create([
            ...$validated,
            'password' => Hash::make($temporaryPassword),
            'status' => $validated['status'] ?? 'active',
            'preferences' => [],
        ]);

        // TODO: Envoyer email avec credentials si send_credentials = true
        // if ($request->send_credentials) {
        //     Mail::to($customer->email)->send(new CustomerCredentials($customer, $temporaryPassword));
        // }

        return $this->createdResponse($customer, 'Customer created successfully.');
    }

    /**
     * Update customer information
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'preferences' => ['nullable', 'array'],
        ]);

        $customer->update($validated);

        return $this->successResponse($customer, 'Customer updated successfully.');
    }

    /**
     * Activate a customer
     */
    public function activate(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->activate();

        return $this->successResponse($customer, 'Customer activated successfully.');
    }

    /**
     * Deactivate a customer
     */
    public function deactivate(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->deactivate();

        return $this->successResponse($customer, 'Customer deactivated successfully.');
    }

    /**
     * Suspend a customer
     */
    public function suspend(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->suspend();

        return $this->successResponse($customer, 'Customer suspended successfully.');
    }

    /**
     * Bulk update customer status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:customers,id'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        Customer::whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        return $this->successResponse(null, 'Customer status updated successfully.');
    }

    /**
     * Soft delete a customer
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return $this->deletedResponse('Customer deleted successfully.');
    }

    /**
     * Bulk soft delete customers
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

    /**
     * Restore a soft deleted customer
     */
    public function restore(string $id): JsonResponse
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return $this->successResponse($customer, 'Customer restored successfully.');
    }

    /**
     * Bulk restore customers
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:customers,id']
        ]);

        Customer::onlyTrashed()
            ->whereIn('id', $request->ids)
            ->restore();

        return $this->successResponse(null, 'Selected customers restored successfully.');
    }

    /**
     * Permanently delete a customer
     */
    public function forceDelete(string $id): JsonResponse
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $customer->forceDelete();

        return $this->deletedResponse('Customer permanently deleted.');
    }

    /**
     * Bulk force delete customers
     */
    public function bulkForceDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:customers,id']
        ]);

        Customer::withTrashed()
            ->whereIn('id', $request->ids)
            ->forceDelete();

        return $this->deletedResponse('Selected customers permanently deleted.');
    }

    /**
     * Export customers to Excel/CSV
     */
    public function export(Request $request): BinaryFileResponse
    {
        $request->validate([
            'format' => ['required', 'in:xlsx,csv,pdf'],
            'status' => ['nullable', 'in:active,inactive,suspended,all'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['exists:customers,id'],
        ]);

        $query = Customer::query()
            ->search($request->search)
            ->when($request->status && $request->status !== 'all', function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->ids, function ($q) use ($request) {
                $q->whereIn('id', $request->ids);
            });

        $filename = 'customers_' . now()->format('Y-m-d_His');

        return Excel::download(
            new CustomersExport($query),
            "{$filename}.{$request->format}",
            $this->getExcelType($request->format)
        );
    }

    /**
     * Get Excel export type
     */
    private function getExcelType(string $format): string
    {
        return match ($format) {
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            'pdf' => \Maatwebsite\Excel\Excel::DOMPDF,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }

    /**
     * Get customer statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Customer::count(),
            'active' => Customer::active()->count(),
            'inactive' => Customer::inactive()->count(),
            'suspended' => Customer::suspended()->count(),
            'trashed' => Customer::onlyTrashed()->count(),
            'verified' => Customer::whereNotNull('email_verified_at')->count(),
            'with_oauth' => Customer::whereNotNull('oauth_provider')->count(),
        ];

        return $this->successResponse($stats);
    }
}
