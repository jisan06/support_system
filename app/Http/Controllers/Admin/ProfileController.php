<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('roles');

        return view('admin.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'avatar_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $avatarMediaId = $validated['avatar_media_id'] ?? null;
        unset($validated['avatar_media_id']);

        $user->update($validated);

        // sync avatar
        $user->mediaByCollection('avatar')->update([
            'mediable_type' => Media::class,
            'mediable_id'   => 0,
        ]);

        if ($avatarMediaId) {
            Media::where('id', $avatarMediaId)->update([
                'mediable_type' => User::class,
                'mediable_id'   => $user->id,
                'collection'    => 'avatar',
            ]);
        }

        return redirect()->route('profile.index')->with('success', __('profile.profile_updated'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => array_merge(['required'], password_rules(), ['confirmed']),
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.index')->with('success', __('profile.password_updated'));
    }
}