<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Services\SettingService;
use App\ViewModels\Admin\SettingFormData;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Display settings form for the given group.
     */
    public function index(string $group = 'general')
    {
        abort_unless(config("settings.{$group}"), 404);

        return view(
            'admin.setting.index',
            SettingFormData::forGroup($group, $this->settingService)->toArray()
        );
    }

    /**
     * Update settings for the given group.
     */
    public function update(SettingRequest $request, string $group)
    {
        abort_unless(config("settings.{$group}"), 404);

        $this->settingService->updateGroup($group, $request->validated());

        // Sync session locale when default_language is changed
        if ($group === 'general' && $request->has('default_language')) {
            $newLocale = $request->input('default_language');
            session(['locale' => $newLocale]);
            app()->setLocale($newLocale);
        }

        activity('settings')
            ->event('settings_updated')
            ->withProperties(['group' => $group])
            ->log("Settings updated for group: {$group}");

        return redirect()
            ->route('settings.index', $group)
            ->with('success', __('setting.settings_updated'));
    }
}