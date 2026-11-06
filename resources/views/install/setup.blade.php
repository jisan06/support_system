@extends('install.layout', ['step' => 2])
@section('step_title', 'Setup')

@section('content')
    {{-- ── Setup Form ──────────────────────────────────────── --}}
    <div id="setup-form">
        <h5 class="mb-1"><i class="bx bx-slider-alt me-1"></i> Setup</h5>
        <p class="text-muted mb-4" style="font-size: .875rem;">Configure your database and create your admin account.</p>

        {{-- Database Section --}}
        <h6 class="text-uppercase text-muted mb-3" style="font-size: .75rem; letter-spacing: .5px;">
            <i class="bx bx-data me-1"></i> Database
        </h6>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Database Host</label>
                <input type="text" id="db_host" class="form-control" value="127.0.0.1">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Port</label>
                <input type="text" id="db_port" class="form-control" value="3306">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Database Name</label>
            <input type="text" id="db_name" class="form-control" placeholder="my_database">
            <small class="text-muted">The database must already exist on your server.</small>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" id="db_user" class="form-control" value="root">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <div class="password-input-group">
                    <input type="password" id="db_password" class="form-control" placeholder="Leave empty if none">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('db_password', this)" tabindex="-1" title="Show password" aria-label="Show password">
                        <i class="bx bx-show"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- DB Test Result --}}
        <div id="db-status" class="mb-3" style="display: none;"></div>

        <div class="text-end mb-4">
            <button type="button" id="btn-test-db" class="btn btn-sm btn-outline-primary" onclick="testDB()">
                <i class="bx bx-plug me-1"></i> Test Connection
            </button>
        </div>

        {{-- Site + Admin Section (hidden until DB verified) --}}
        <div id="admin-section" style="display: none;">
        <hr class="my-4">

        <h6 class="text-uppercase text-muted mb-3" style="font-size: .75rem; letter-spacing: .5px;">
            <i class="bx bx-user-circle me-1"></i> Site & Admin Account
        </h6>

        <div class="mb-3">
            <label class="form-label">Site Name</label>
            <input type="text" id="site_name" class="form-control" value="My Admin" placeholder="My Website">
        </div>
        <div class="mb-3">
            <label class="form-label">Admin Name</label>
            <input type="text" id="name" class="form-control" placeholder="John Doe">
        </div>
        <div class="mb-3">
            <label class="form-label">Admin Email</label>
            <input type="email" id="email" class="form-control" placeholder="admin@example.com">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <div class="password-input-group">
                    <input type="password" id="password" class="form-control" minlength="{{ config('password.min_length', 8) }}" data-strength-meter data-strength-rules='@json(config("password"))'>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('password', this)" tabindex="-1" title="Show password" aria-label="Show password">
                        <i class="bx bx-show"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="password-input-group">
                    <input type="password" id="password_confirmation" class="form-control" minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('password_confirmation', this)" tabindex="-1" title="Show password" aria-label="Show password">
                        <i class="bx bx-show"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" id="demo_data" class="form-check-input" value="1" checked>
            <label class="form-check-label" for="demo_data">
                Install demo data <small class="text-muted">(sample users, roles & activity logs)</small>
            </label>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('install.check') }}" class="btn btn-back">
                <i class="bx bx-left-arrow-alt me-1"></i> Back
            </a>
            <button type="button" id="btn-install" class="btn btn-install" onclick="runInstall()">
                Install Now <i class="bx bx-rocket ms-1"></i>
            </button>
        </div>
        </div>{{-- /admin-section --}}
    </div>

    {{-- ── Installation Progress ───────────────────────────── --}}
    <div id="install-progress" class="install-progress">
        <div class="text-center mb-4">
            <h5 class="mb-1"><i class="bx bx-loader-alt bx-spin me-1"></i> Installing...</h5>
            <p class="text-muted" style="font-size: .875rem;">Please wait while we set up your application.</p>
        </div>

        <div class="card border" style="border-radius: 10px;">
            <div class="card-body py-3">
                <div class="progress-step" id="prog-db">
                    <span class="prog-icon"><i class="bx bx-circle"></i></span>
                    <span>Connecting to database</span>
                </div>
                <div class="progress-step" id="prog-migrate">
                    <span class="prog-icon"><i class="bx bx-circle"></i></span>
                    <span>Creating tables</span>
                </div>
                <div class="progress-step" id="prog-seed">
                    <span class="prog-icon"><i class="bx bx-circle"></i></span>
                    <span>Setting up default data</span>
                </div>
                <div class="progress-step" id="prog-admin">
                    <span class="prog-icon"><i class="bx bx-circle"></i></span>
                    <span>Creating admin account</span>
                </div>
                <div class="progress-step" id="prog-finalize">
                    <span class="prog-icon"><i class="bx bx-circle"></i></span>
                    <span>Finalizing installation</span>
                </div>
            </div>
        </div>

        <div id="install-error" class="mt-3" style="display: none;"></div>
    </div>
@endsection

@section('scripts')
<script>
    // ── Test DB Connection ────────────────────────────────────
    let dbVerified = false;

    async function testDB() {
        const status = document.getElementById('db-status');
        const btn = document.getElementById('btn-test-db');

        status.style.display = 'block';
        status.className = 'alert alert-install alert-info mb-3';
        status.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Testing connection...';
        btnLoading(btn, true);

        try {
            const res = await postJSON('{{ route("install.test-database", [], false) }}', getDBFields());
            status.className = 'alert alert-install alert-success mb-3 fade-in-up';
            status.innerHTML = '<i class="bx bx-check-circle me-1"></i> ' + res.message;
            dbVerified = true;
            showAdminSection();
        } catch (err) {
            status.className = 'alert alert-install alert-danger mb-3 fade-in-up';
            status.innerHTML = '<i class="bx bx-error-circle me-1"></i> ' + (err.message || 'Connection failed.');
            dbVerified = false;
            hideAdminSection();
        }

        btnLoading(btn, false);
    }

    function showAdminSection() {
        const section = document.getElementById('admin-section');
        section.style.display = '';
        section.classList.add('fade-in-up');
    }

    function hideAdminSection() {
        document.getElementById('admin-section').style.display = 'none';
    }

    // Reset DB verified state when DB fields change
    document.querySelectorAll('#db_host, #db_port, #db_name, #db_user, #db_password').forEach(el => {
        el.addEventListener('input', () => {
            if (dbVerified) {
                dbVerified = false;
                hideAdminSection();
                const status = document.getElementById('db-status');
                status.style.display = 'none';
            }
        });
    });

    function getDBFields() {
        return {
            db_host: document.getElementById('db_host').value,
            db_port: document.getElementById('db_port').value,
            db_name: document.getElementById('db_name').value,
            db_user: document.getElementById('db_user').value,
            db_password: document.getElementById('db_password').value,
        };
    }

    function getFormData() {
        return {
            ...getDBFields(),
            site_name: document.getElementById('site_name').value,
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
            demo_data: document.getElementById('demo_data').checked ? 1 : 0,
        };
    }

    // ── Client-side Validation ────────────────────────────────
    function validateForm() {
        const d = getFormData();

        if (!d.db_name.trim()) return 'Please enter a database name.';
        if (!d.db_user.trim()) return 'Please enter a database username.';
        if (!d.site_name.trim()) return 'Please enter a site name.';
        if (!d.name.trim()) return 'Please enter the admin name.';
        if (!d.email.trim()) return 'Please enter the admin email.';
        if (!d.password) return 'Please enter a password.';
        if (d.password.length < {{ config('password.min_length', 8) }}) return 'Password must be at least {{ config('password.min_length', 8) }} characters.';
        if (d.password !== d.password_confirmation) return 'Passwords do not match.';

        return null;
    }

    // ── Progress UI ───────────────────────────────────────────
    function setProgress(id, state) {
        const el = document.getElementById(id);
        const icon = el.querySelector('.prog-icon');

        el.className = 'progress-step ' + state;

        if (state === 'active') {
            icon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
        } else if (state === 'done') {
            icon.innerHTML = '<i class="bx bx-check-circle"></i>';
        } else if (state === 'error') {
            icon.innerHTML = '<i class="bx bx-error-circle"></i>';
        }
    }

    async function simulateProgress(steps, activeIndex) {
        // Mark previous steps as done
        for (let i = 0; i < activeIndex; i++) {
            setProgress(steps[i], 'done');
        }
        // Mark current step as active
        if (activeIndex < steps.length) {
            setProgress(steps[activeIndex], 'active');
        }
    }

    // ── Run Installation ──────────────────────────────────────
    async function runInstall() {
        const form = document.getElementById('setup-form');
        const progress = document.getElementById('install-progress');

        // Validate
        hideError(form);
        const err = validateForm();
        if (err) {
            showError(form, err);
            return;
        }

        // Switch to progress view
        form.style.display = 'none';
        progress.classList.add('active');

        const steps = ['prog-db', 'prog-migrate', 'prog-seed', 'prog-admin', 'prog-finalize'];

        // Animate progress steps with timing
        simulateProgress(steps, 0);
        let stepInterval = null;

        try {
            // Start timer for step animation
            let currentStep = 0;
            stepInterval = setInterval(() => {
                currentStep++;
                if (currentStep < steps.length - 1) {
                    simulateProgress(steps, currentStep);
                }
            }, 3000);

            const res = await postJSON('{{ route("install.run", [], false) }}', getFormData());

            clearInterval(stepInterval);

            // Mark all done
            steps.forEach(s => setProgress(s, 'done'));

            // Redirect after brief pause
            setTimeout(() => {
                window.location.href = res.redirect || '{{ route("install.finish") }}';
            }, 600);

        } catch (err) {
            if (stepInterval) clearInterval(stepInterval);
            // Find current active step and mark as error
            steps.forEach(s => {
                const el = document.getElementById(s);
                if (el.classList.contains('active')) {
                    setProgress(s, 'error');
                }
            });

            const errorDiv = document.getElementById('install-error');
            errorDiv.style.display = 'block';
            errorDiv.innerHTML = `
                <div class="alert alert-danger alert-install fade-in-up">
                    <i class="bx bx-error-circle me-1"></i> ${err.message || 'Installation failed.'}
                </div>
                <button class="btn btn-back" onclick="backToForm()">
                    <i class="bx bx-left-arrow-alt me-1"></i> Go Back & Fix
                </button>
            `;
        }
    }

    function backToForm() {
        const form = document.getElementById('setup-form');
        const progress = document.getElementById('install-progress');

        progress.classList.remove('active');
        form.style.display = '';

        // Reset progress icons
        document.querySelectorAll('.progress-step').forEach(el => {
            el.className = 'progress-step';
            el.querySelector('.prog-icon').innerHTML = '<i class="bx bx-circle"></i>';
        });

        document.getElementById('install-error').style.display = 'none';
    }
</script>
@endsection
