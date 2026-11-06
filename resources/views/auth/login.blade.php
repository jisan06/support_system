<x-guest-layout>
    <x-slot name="title">{{ __('Login') }}</x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(config('demo.enabled'))
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg">
            <p class="text-xs font-semibold text-blue-800 dark:text-blue-300 mb-2 uppercase tracking-wide">Demo Credentials</p>
            <div class="space-y-1">
                @foreach(config('demo.credentials') as $cred)
                    @if($cred['email'])
                        <button type="button"
                            onclick="document.getElementById('email').value='{{ $cred['email'] }}';document.getElementById('password').value='{{ $cred['password'] }}';"
                            class="w-full flex items-center justify-between px-2.5 py-1.5 text-xs rounded-md bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer text-left">
                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $cred['role'] }}</span>
                            <span class="text-gray-400 dark:text-gray-500">{{ $cred['email'] }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 text-center">Click a role to fill credentials</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div class="login-password-wrapper">
                <x-text-input id="password" class="block w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <button type="button"
                        id="toggle-password"
                        onclick="toggleLoginPassword()"
                        class="login-password-toggle"
                        title="{{ __('Show password') }}"
                        aria-label="{{ __('Show password') }}"
                        tabindex="-1">
                    <svg id="eye-show" class="toggle-eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg id="eye-hide" class="toggle-eye-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request') && !config('demo.enabled'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <style>
        .login-password-wrapper {
            position: relative;
            margin-top: 0.25rem;
            width: 100%;
        }
        .login-password-wrapper input {
            padding-right: 2.75rem !important;
            width: 100%;
        }
        .login-password-toggle {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 2.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            padding: 0;
            color: #9ca3af;
            cursor: pointer;
            outline: none;
            transition: color 0.15s ease-in-out;
            z-index: 10;
        }
        .login-password-toggle:hover,
        .login-password-toggle:focus {
            color: #4b5563;
            outline: none;
        }
        .toggle-eye-icon {
            width: 1.25rem;
            height: 1.25rem;
            pointer-events: none;
        }
        .dark .login-password-toggle {
            color: #9ca3af;
        }
        .dark .login-password-toggle:hover,
        .dark .login-password-toggle:focus {
            color: #e5e7eb;
        }
    </style>

    <script>
        function toggleLoginPassword() {
            const input = document.getElementById('password');
            const eyeShow = document.getElementById('eye-show');
            const eyeHide = document.getElementById('eye-hide');
            const btn = document.getElementById('toggle-password');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                eyeShow.style.display = 'none';
                eyeHide.style.display = 'block';
                btn.setAttribute('title', '{{ __("Hide password") }}');
                btn.setAttribute('aria-label', '{{ __("Hide password") }}');
            } else {
                input.type = 'password';
                eyeHide.style.display = 'none';
                eyeShow.style.display = 'block';
                btn.setAttribute('title', '{{ __("Show password") }}');
                btn.setAttribute('aria-label', '{{ __("Show password") }}');
            }
        }
    </script>
</x-guest-layout>
