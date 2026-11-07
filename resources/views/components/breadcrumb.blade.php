@php
    use App\Services\BreadcrumbsManager;

    $routeName = request()->route()->getName();
    $params = request()->route()->parameters(); // dynamic parameters like $role
    $breadcrumbs = BreadcrumbsManager::get($routeName, $params);

    // Count only crumbs that are visible (show=true)
    $visibleCrumbs = array_filter($breadcrumbs ?? [], fn($c) => $c['show'] ?? true);
@endphp

@if(!empty($visibleCrumbs) && count($visibleCrumbs) > 1)
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <ol class="breadcrumb m-0">
            @foreach($visibleCrumbs as $crumb)
                @if(!empty($crumb['route']))
                    <li class="breadcrumb-item"><a href="{{ route($crumb['route']) }}">{{ $crumb['label'] }}</a></li>
                @elseif($loop->last)
                    <li class="breadcrumb-item active">{{ $crumb['label'] }}</li>
                @else
                    <li class="breadcrumb-item">{{ $crumb['label'] }}</li>
                @endif
            @endforeach
        </ol>
    </div>
@endif
