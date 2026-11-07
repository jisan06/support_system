<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerProfileUpdateRequest;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    protected UserService $userService;
    protected MediaService $mediaService;

    public function __construct(UserService $userService, MediaService $mediaService)
    {
        $this->userService = $userService;
        $this->mediaService = $mediaService;
    }

    /**
     * Display the authenticated customer's profile form.
     */
    public function edit(Request $request): View
    {
        $customer = $request->user()->load('media');
        $avatarUrl = $customer->getFirstMediaUrl('avatar');

        return view('customer.profile.edit', compact('customer', 'avatarUrl'));
    }

    /**
     * Alias for profile viewing.
     */
    public function index(Request $request): View
    {
        return $this->edit($request);
    }

    /**
     * Update the authenticated customer's profile.
     * Enforces strict authorization: customers can only update their own account,
     * and cannot mutate roles, permissions, account_type, or status.
     */
    public function update(CustomerProfileUpdateRequest $request): RedirectResponse
    {
        $customer = $request->user();
        $validated = $request->validated();

        // 1. Prepare safe user update payload
        $updateData = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        // 2. Optional phone update if schema supports phone column
        if (isset($validated['phone']) && Schema::hasColumn('users', 'phone')) {
            $updateData['phone'] = $validated['phone'];
        }

        // 3. Optional password update
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // 4. Update the customer's attributes
        $customer->update($updateData);

        // 5. Handle avatar removal
        if ($request->boolean('remove_avatar')) {
            foreach ($customer->mediaByCollection('avatar')->get() as $oldAvatar) {
                Storage::disk($oldAvatar->disk)->delete($oldAvatar->path);
                $oldAvatar->delete();
            }
        }

        // 6. Handle new avatar upload
        if ($request->hasFile('avatar')) {
            // Remove previous avatar before attaching the new one
            foreach ($customer->mediaByCollection('avatar')->get() as $oldAvatar) {
                Storage::disk($oldAvatar->disk)->delete($oldAvatar->path);
                $oldAvatar->delete();
            }

            $this->mediaService->upload(
                $request->file('avatar'),
                User::class,
                $customer->id,
                'avatar',
                0,
                'public',
                $customer->id
            );
        }

        return redirect()->route('customer.profile.edit')
            ->with('success', 'Your profile information has been updated successfully.');
    }
}

