@extends('install.layout', ['step' => 3])
@section('step_title', 'Complete')

@section('content')
    <div class="text-center py-3">
        <div class="mb-3" style="animation: checkPop .5s ease forwards;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(52,195,143,.1); display: inline-flex; align-items: center; justify-content: center;">
                <i class="bx bx-check-circle" style="font-size: 48px; color: #34c38f;"></i>
            </div>
        </div>

        <h4 style="color: #34c38f; font-weight: 700;" class="mb-2">Installation Complete!</h4>
        <p class="text-muted mb-4" style="font-size: .9rem;">
            Your application has been installed successfully.<br>
            You can now log in with the admin credentials you created.
        </p>

        <div class="alert alert-light border text-start mb-4" style="border-radius: 10px;">
            <h6 class="mb-2" style="font-size: .85rem;"><i class="bx bx-shield-quarter me-1"></i> Security Tips</h6>
            <ul class="mb-0 ps-3 text-muted" style="font-size: .8rem; line-height: 2;">
                <li>Set proper file permissions on your server</li>
                <li>Configure HTTPS for secure access</li>
                <li>Regularly backup your database</li>
            </ul>
        </div>

        <a href="{{ url('/login') }}" class="btn btn-install btn-lg px-5">
            <i class="bx bx-log-in-circle me-1"></i> Go to Login
        </a>
    </div>
@endsection
