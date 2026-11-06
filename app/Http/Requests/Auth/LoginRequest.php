<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $attempts = RateLimiter::attempts($this->throttleKey());
            if ($attempts >= 3) {
                $admins = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->get();
                if ($admins->isEmpty()) {
                    $admins = \App\Models\User::where('id', 1)->get();
                }
                notify(
                    $admins,
                    __('notification.failed_login_title'),
                    __('notification.failed_login_message', [
                        'count' => $attempts,
                        'email' => $this->input('email'),
                        'ip'    => $this->ip(),
                    ]),
                    'warning'
                );
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Enforce account_type: Admin portal login strictly permits internal admin/staff accounts
        if (! Auth::user()->isAdmin()) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Access denied. This login is for internal staff accounts only.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
