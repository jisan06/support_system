<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CustomersDataTable;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\CustomerRequest;
use App\Models\User;
use App\Services\CustomerService;
use App\ViewModels\Admin\CustomerFormData;

class CustomerController extends Controller
{
    protected CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    protected function authorizeCustomerAccess(User $customer): void
    {
        if (!$customer->isCustomer()) {
            abort(404, 'Customer account not found.');
        }
    }

    /**
     * Display a listing of customer resources.
     */
    public function index(CustomersDataTable $dataTable)
    {
        $data = [
            'page_type' => 'index',
            'bulk_actions' => [
                (object)['value' => 'trash', 'label' => __('common.move_trash')],
            ],
            'bulk_delete_url' => route('customers.bulk_delete'),
        ];

        return $dataTable->render('admin.customer.index', $data);
    }

    /**
     * Display a listing of trashed customer resources.
     */
    public function trash(CustomersDataTable $dataTable)
    {
        $data = [
            'page_type' => 'trash',
            'bulk_actions' => [
                (object)['value' => 'restore', 'label' => __('common.restore')],
                (object)['value' => 'force_delete', 'label' => __('common.delete_permanently')],
            ],
            'bulk_restore_url' => route('customers.bulk_restore'),
            'bulk_delete_url' => route('customers.bulk_force_delete'),
        ];

        return $dataTable->render('admin.customer.index', $data);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('admin.customer.form',
            CustomerFormData::forCreate()->toArray()
        );
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('customers.index')
            ->with('success', __('customer.created', ['default' => 'Customer created successfully.']));
    }

    /**
     * Display the specified customer.
     */
    public function show(User $customer)
    {
        $this->authorizeCustomerAccess($customer);

        return view('admin.customer.show', compact('customer'));
    }

    /**
     * Show the form for editing the customer.
     */
    public function edit(User $customer)
    {
        $this->authorizeCustomerAccess($customer);

        return view('admin.customer.form',
            CustomerFormData::forEdit($customer)->toArray()
        );
    }

    /**
     * Update the specified customer.
     */
    public function update(CustomerRequest $request, User $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $this->service->update($customer->id, $request->validated());

        return redirect()->route('customers.index')
            ->with('success', __('customer.updated', ['default' => 'Customer updated successfully.']));
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(User $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $this->service->delete($customer->id);

        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Bulk soft delete customers.
     */
    public function bulkDestroy(BulkActionRequest $request)
    {
        $ids = $this->filterCustomerIds($request->validated('ids'));
        $this->service->bulkDelete($ids);

        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Restore the soft deleted customer.
     */
    public function restore($id)
    {
        $this->service->restore($id);

        return ResponseHelper::success([], __('common.restored'));
    }

    /**
     * Bulk restore soft deleted customers.
     */
    public function bulkRestore(BulkActionRequest $request)
    {
        $ids = $this->filterCustomerIds($request->validated('ids'), true);
        $this->service->bulkRestore($ids);

        return ResponseHelper::success([], __('common.restored'));
    }

    /**
     * Permanently delete customer.
     */
    public function forceDelete($id)
    {
        $this->service->forceDelete($id);

        return ResponseHelper::success([], __('common.permanent_deleted'));
    }

    /**
     * Bulk permanently delete customers.
     */
    public function bulkForceDelete(BulkActionRequest $request)
    {
        $ids = $this->filterCustomerIds($request->validated('ids'), true);
        $this->service->bulkForceDelete($ids);

        return ResponseHelper::success([], __('common.permanent_deleted'));
    }

    protected function filterCustomerIds(array $ids, bool $withTrashed = false): array
    {
        $query = User::whereIn('id', $ids)->isCustomer();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->pluck('id')->toArray();
    }
}

