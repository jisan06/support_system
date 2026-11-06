<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Installation - @yield('step_title', 'Setup')</title>
    <link href="{{ asset('backend/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary: #556ee6;
            --primary-dark: #3b52c4;
            --success: #34c38f;
            --danger: #f46a6a;
            --warning: #f1b44c;
            --gray: #74788d;
            --light: #f8f9fa;
            --border: #e9ecef;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .install-wrapper {
            max-width: 680px;
            width: 100%;
        }

        .install-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
            overflow: hidden;
        }

        /* ── Stepper Header ─────────────────────────────── */
        .install-header {
            background: #fff;
            padding: 2rem 2rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .install-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .install-brand h4 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1.25rem;
        }

        .install-brand p {
            margin: .25rem 0 0;
            color: var(--gray);
            font-size: .85rem;
        }

        .stepper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 0 1rem;
        }

        .stepper-step {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .stepper-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 600;
            transition: all .4s ease;
            flex-shrink: 0;
        }

        .stepper-dot.pending {
            background: var(--light);
            color: #adb5bd;
            border: 2px solid var(--border);
        }

        .stepper-dot.active {
            background: var(--primary);
            color: #fff;
            border: 2px solid var(--primary);
            box-shadow: 0 4px 12px rgba(85,110,230,.35);
            transform: scale(1.1);
        }

        .stepper-dot.completed {
            background: var(--success);
            color: #fff;
            border: 2px solid var(--success);
        }

        .stepper-label {
            font-size: .8rem;
            font-weight: 500;
            color: #adb5bd;
            white-space: nowrap;
        }

        .stepper-step.active .stepper-label { color: var(--primary); font-weight: 600; }
        .stepper-step.completed .stepper-label { color: var(--success); }

        .stepper-line {
            flex: 1;
            height: 2px;
            background: var(--border);
            margin: 0 .75rem;
            min-width: 40px;
            transition: background .4s ease;
        }

        .stepper-line.completed { background: var(--success); }

        /* ── Body ───────────────────────────────────────── */
        .install-body {
            background: #fff;
            padding: 2rem;
        }

        /* ── Form Styles ────────────────────────────────── */
        .form-label { font-weight: 500; font-size: .875rem; color: #495057; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 .15rem rgba(85,110,230,.15); }

        /* ── Check Items ────────────────────────────────── */
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .6rem .75rem;
            border-radius: 8px;
            margin-bottom: .35rem;
            transition: background .2s;
        }

        .check-item:hover { background: var(--light); }

        .check-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .check-icon.pass { background: rgba(52,195,143,.12); color: var(--success); }
        .check-icon.fail { background: rgba(244,106,106,.12); color: var(--danger); }
        .check-icon.loading { background: rgba(85,110,230,.12); color: var(--primary); }

        /* ── Alert ──────────────────────────────────────── */
        .alert-install {
            border-radius: 10px;
            border: none;
            font-size: .875rem;
        }

        /* ── Progress ───────────────────────────────────── */
        .install-progress {
            display: none;
        }

        .install-progress.active {
            display: block;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .5rem 0;
            color: #adb5bd;
            font-size: .875rem;
            transition: color .3s;
        }

        .progress-step.active { color: var(--primary); }
        .progress-step.done { color: var(--success); }
        .progress-step.error { color: var(--danger); }

        .progress-step .spinner-border { width: 18px; height: 18px; border-width: 2px; }

        /* ── Buttons ────────────────────────────────────── */
        .btn-install {
            background: linear-gradient(135deg, #556ee6, #6777ef);
            color: #fff;
            border: none;
            padding: .6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all .3s;
        }

        .btn-install:hover { background: linear-gradient(135deg, #3b52c4, #556ee6); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(85,110,230,.3); }
        .btn-install:disabled { opacity: .6; transform: none; box-shadow: none; cursor: not-allowed; }

        .btn-back {
            background: var(--light);
            color: var(--gray);
            border: 1px solid var(--border);
            padding: .6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all .2s;
        }

        .btn-back:hover { background: #e9ecef; color: #333; }

        /* ── Animations ─────────────────────────────────── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in-up { animation: fadeInUp .4s ease forwards; }

        @keyframes checkPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .check-pop { animation: checkPop .3s ease forwards; }

        /* ── Password Toggle ─────────────────────────────── */
        .password-input-group {
            position: relative;
        }

        .password-input-group .form-control {
            padding-right: 42px !important;
        }

        .password-toggle-btn {
            position: absolute;
            top: 0;
            right: 0;
            height: 38px;
            width: 38px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #74788d;
            border: none;
            background: transparent;
            font-size: 1.15rem;
            cursor: pointer;
            z-index: 5;
            transition: color .2s ease;
        }

        .password-toggle-btn:hover,
        .password-toggle-btn:focus {
            color: var(--primary);
            outline: none;
            box-shadow: none;
        }
    </style>
    <link href="{{ asset('backend/assets/css/password-strength.css') }}" rel="stylesheet">
</head>
<body>
    <div class="install-wrapper">
        <div class="card install-card">
            <div class="install-header">
                <div class="install-brand">
                    <h4><i class="bx bx-cog bx-spin me-1" style="font-size: 1.3rem;"></i> Installation Wizard</h4>
                    <p>Follow the steps below to set up your application</p>
                </div>

                <div class="stepper">
                    @php $currentStep = $step ?? 1; @endphp

                    {{-- Step 1: System --}}
                    <div class="stepper-step {{ $currentStep == 1 ? 'active' : ($currentStep > 1 ? 'completed' : '') }}">
                        <div class="stepper-dot {{ $currentStep == 1 ? 'active' : ($currentStep > 1 ? 'completed' : 'pending') }}">
                            @if($currentStep > 1) <i class="bx bx-check"></i> @else 1 @endif
                        </div>
                        <span class="stepper-label">System</span>
                    </div>

                    <div class="stepper-line {{ $currentStep > 1 ? 'completed' : '' }}"></div>

                    {{-- Step 2: Setup --}}
                    <div class="stepper-step {{ $currentStep == 2 ? 'active' : ($currentStep > 2 ? 'completed' : '') }}">
                        <div class="stepper-dot {{ $currentStep == 2 ? 'active' : ($currentStep > 2 ? 'completed' : 'pending') }}">
                            @if($currentStep > 2) <i class="bx bx-check"></i> @else 2 @endif
                        </div>
                        <span class="stepper-label">Setup</span>
                    </div>
                </div>
            </div>

            <div class="install-body fade-in-up">
                @yield('content')
            </div>
        </div>

        <div class="text-center mt-3">
            <small style="color: rgba(255,255,255,.5);">Version {{ config('app-version.version', '1.0.0') }}</small>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        async function postJSON(url, data = {}) {
            let res;
            try {
                res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
            } catch (networkErr) {
                throw { message: 'Network request failed or server timed out: ' + (networkErr.message || 'Server connection lost.') };
            }

            const text = await res.text();
            let json;
            try {
                json = JSON.parse(text);
            } catch (parseErr) {
                throw { message: 'Server error (' + res.status + '): ' + (text.substring(0, 200) || 'Unexpected server response.') };
            }

            if (!res.ok) throw json;
            return json;
        }

        function showError(container, message) {
            let el = container.querySelector('.alert-error');
            if (!el) {
                el = document.createElement('div');
                el.className = 'alert alert-danger alert-install alert-error fade-in-up';
                container.prepend(el);
            }
            el.innerHTML = '<i class="bx bx-error-circle me-1"></i> ' + message;
            el.style.display = 'block';
        }

        function hideError(container) {
            const el = container.querySelector('.alert-error');
            if (el) el.style.display = 'none';
        }

        function btnLoading(btn, loading) {
            if (loading) {
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Please wait...';
                btn.disabled = true;
            } else {
                btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
                btn.disabled = false;
            }
        }

        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.className = 'bx bx-hide';
                }
                btn.setAttribute('title', 'Hide password');
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                if (icon) {
                    icon.className = 'bx bx-show';
                }
                btn.setAttribute('title', 'Show password');
                btn.setAttribute('aria-label', 'Show password');
            }
        }
    </script>

    <script src="{{ asset('backend/assets/js/password-strength.js') }}"></script>
    @yield('scripts')
</body>
</html>
