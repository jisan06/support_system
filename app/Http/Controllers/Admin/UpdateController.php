<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UpdateService;

class UpdateController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    public function index()
    {
        return view('admin.update.index', [
            'enabled'    => $this->updateService->isEnabled(),
            'updateInfo' => $this->updateService->isEnabled() ? $this->updateService->checkForUpdate() : null,
        ]);
    }

    public function check()
    {
        if (!$this->updateService->isEnabled()) {
            return redirect()->route('update.index')->with('error', __('update.not_configured'));
        }

        $updateInfo = $this->updateService->forceCheck();

        $message = $updateInfo['error']
            ? __('update.check_failed')
            : ($updateInfo['update_available']
                ? __('update.new_version_available', ['version' => $updateInfo['latest_version']])
                : __('update.up_to_date'));

        $type = $updateInfo['error'] ? 'error' : 'success';

        return redirect()->route('update.index')->with($type, $message);
    }
}
