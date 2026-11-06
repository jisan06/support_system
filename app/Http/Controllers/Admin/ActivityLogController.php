<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use App\DataTables\ActivityLogsDataTable;

class ActivityLogController extends Controller
{
    protected ActivityLogService $service;

    public function __construct(ActivityLogService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ActivityLogsDataTable $dataTable)
    {
        $data = [
            'page_type' => 'index',
            'bulk_actions' => [
                (object)['value' => 'trash', 'label' => __('common.delete')]
            ],
            'bulk_delete_url' => route('activity-logs.bulk_delete'),
        ];
        return $dataTable->render('admin.activity-log.index', $data);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $activity_log = ActivityLog::with(['causer', 'subject'])->findOrFail($id);
        return view('admin.activity-log.show', compact('activity_log'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->service->delete($id);
        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Remove bulk item from storage.
     */
    public function bulkDestroy(BulkActionRequest $request)
    {
        $this->service->bulkDelete($request->validated('ids'));
        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Clear all activity logs.
     */
    public function clear()
    {
        $this->service->deleteAll();
        return ResponseHelper::success([], __('activity_log.logs_cleared'));
    }
}