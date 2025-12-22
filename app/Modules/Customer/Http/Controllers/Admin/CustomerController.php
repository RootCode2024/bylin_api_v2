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
use Modules\Customer\Http\Requests\StoreCustomerAdminRequest;
use Modules\Customer\Http\Requests\UpdateCustomerAdminRequest;
use Modules\Customer\Http\Requests\BulkUpdateCustomerStatusRequest;
use Modules\Customer\Http\Requests\BulkDeleteCustomersRequest;
use Modules\Customer\Http\Requests\BulkRestoreCustomersRequest;
use Modules\Customer\Http\Requests\BulkForceDeleteCustomersRequest;

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
    /**
     * Store a newly created customer
     */
    public function store(StoreCustomerAdminRequest $request): JsonResponse
    {
        // Générer un mot de passe temporaire
        $temporaryPassword = Str::random(16);

        $customer = Customer::create([
            ...$request->validated(),
            'password' => Hash::make($temporaryPassword),
            'status' => $request->status ?? 'active',
            'preferences' => [],
        ]);

        // TODO: Envoyer email avec credentials si send_credentials = true
        // if ($request->send_credentials) {
        //     Mail::to($customer->email)->send(new CustomerCredentials($customer, $temporaryPassword));
        // }

        return $this->createdResponse($customer, 'Client créé avec succès.');
    }

    /**
     * Update customer information
     */
    /**
     * Update customer information
     */
    public function update(UpdateCustomerAdminRequest $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->validated());

        return $this->successResponse($customer, 'Informations du client mises à jour avec succès.');
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
    /**
     * Bulk update customer status
     */
    public function bulkUpdateStatus(BulkUpdateCustomerStatusRequest $request): JsonResponse
    {
        Customer::whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        return $this->successResponse(null, 'Statuts des clients mis à jour avec succès.');
    }

    /**
     * Soft delete a customer
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return $this->deletedResponse('Client supprimé avec succès.');
    }

    /**
     * Bulk soft delete customers
     */
    public function bulkDestroy(BulkDeleteCustomersRequest $request): JsonResponse
    {
        Customer::whereIn('id', $request->ids)->delete();

        return $this->deletedResponse('Clients sélectionnés supprimés avec succès.');
    }

    /**
     * Restore a soft deleted customer
     */
    public function restore(string $id): JsonResponse
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return $this->successResponse($customer, 'Client restauré avec succès.');
    }

    /**
     * Bulk restore customers
     */
    public function bulkRestore(BulkRestoreCustomersRequest $request): JsonResponse
    {
        Customer::onlyTrashed()
            ->whereIn('id', $request->ids)
            ->restore();

        return $this->successResponse(null, 'Clients sélectionnés restaurés avec succès.');
    }

    /**
     * Permanently delete a customer
     */
    public function forceDelete(string $id): JsonResponse
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $customer->forceDelete();

        return $this->deletedResponse('Client supprimé définitivement.');
    }

    /**
     * Bulk force delete customers
     */
    public function bulkForceDelete(BulkForceDeleteCustomersRequest $request): JsonResponse
    {
        Customer::withTrashed()
            ->whereIn('id', $request->ids)
            ->forceDelete();

        return $this->deletedResponse('Clients sélectionnés supprimés définitivement.');
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
