@extends('install.layout', ['step' => 1])
@section('step_title', 'System Check')

@section('content')
    <div id="check-container">
        <h5 class="mb-1"><i class="bx bx-check-shield me-1"></i> System Check</h5>
        <p class="text-muted mb-4" style="font-size: .875rem;">Verifying your server meets all requirements.</p>

        {{-- Requirements --}}
        <h6 class="text-uppercase text-muted mb-2" style="font-size: .75rem; letter-spacing: .5px;">Server Requirements</h6>
        <div class="mb-4" id="requirements-list">
            @foreach($requirements as $i => $req)
                <div class="check-item" data-index="{{ $i }}" style="opacity: 0;">
                    <div>
                        <span style="font-size: .875rem;">{{ $req['name'] }}</span>
                        <small class="text-muted ms-1">({{ $req['current'] }})</small>
                    </div>
                    <div class="check-icon {{ $req['status'] ? 'pass' : 'fail' }}">
                        <i class="bx {{ $req['status'] ? 'bx-check' : 'bx-x' }}"></i>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Permissions --}}
        <h6 class="text-uppercase text-muted mb-2" style="font-size: .75rem; letter-spacing: .5px;">Folder Permissions</h6>
        <div class="mb-4" id="permissions-list">
            @foreach($permissions as $i => $perm)
                <div class="check-item" data-index="{{ count($requirements) + $i }}" style="opacity: 0;">
                    <div>
                        <code style="font-size: .8rem;">{{ $perm['folder'] }}</code>
                        <small class="text-muted ms-1">({{ $perm['permission'] }})</small>
                    </div>
                    <div class="check-icon {{ $perm['status'] ? 'pass' : 'fail' }}">
                        <i class="bx {{ $perm['status'] ? 'bx-check' : 'bx-x' }}"></i>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end" id="check-actions" style="display: none !important;">

            @php
                $allPassed = collect($requirements)->every(fn($r) => $r['status'])
                          && collect($permissions)->every(fn($p) => $p['status']);
            @endphp

            @if($allPassed)
                <a href="{{ route('install.setup') }}" class="btn btn-install" id="btn-next">
                    Continue <i class="bx bx-right-arrow-alt ms-1"></i>
                </a>
            @else
                <button class="btn btn-install" disabled>
                    <i class="bx bx-error-circle me-1"></i> Fix issues to continue
                </button>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const items = document.querySelectorAll('.check-item');
        const allPassed = {{ $allPassed ? 'true' : 'false' }};
        let delay = 0;

        items.forEach((item, i) => {
            delay += 80;
            setTimeout(() => {
                item.style.opacity = '1';
                item.classList.add('fade-in-up');
                const icon = item.querySelector('.check-icon');
                icon.classList.add('check-pop');
            }, delay);
        });

        // Show actions after all items animate
        setTimeout(() => {
            const actions = document.getElementById('check-actions');
            actions.style.display = '';
            actions.removeAttribute('style');
            actions.classList.add('fade-in-up');

        }, delay + 200);
    });
</script>
@endsection
