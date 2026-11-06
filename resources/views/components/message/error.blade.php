@if (session('error') || $errors->first())
    <div class="alert alert-danger alert-dismissible">
        {{ session('error') }}
        {{ $errors->first() }}
        @php
            Session::forget('error');
        @endphp
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
