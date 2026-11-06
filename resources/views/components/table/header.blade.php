<div class="align-items-center card-header d-flex flex-wrap justify-content-between px-3 py-0">
    <!-- Left Section -->
    <div class="d-flex gap-4 align-items-center">
        @if($icon)
            <h5 class="title font-size-15 mb-0 pe-3 py-3">
                <i class="{{ $icon }} me-2"></i> {{ $title }}
            </h5>
        @else
            <h5 class="title font-size-16 mb-0 pe-3 py-2 text-primary">{{ $title }}</h5>
        @endif

        <!-- Tabs -->
        @if(!empty($tabs))
            <div class="align-items-center d-flex gap-4">
                @foreach($tabs as $tab)
                    @if(empty($tab['permission']) || auth()->user()->hasPermissionTo($tab['permission']))
                        <a href="{{ route($tab['route']) }}"
                           class="nav-link font-size-13 {{ request()->routeIs($tab['route']) ? 'active fw-bold' : '' }}"
                        >
                            @if(isset($tab['icon']))
                                <i class="{{ $tab['icon'] }} me-1"></i>
                            @endif
                            {{ $tab['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <!-- Right Section -->
    @if($addButton && (empty($addButton['permission']) || auth()->user()->hasPermissionTo($addButton['permission'])))
        <div class="right-section ps-3 py-2">
            <a href="{{ route($addButton['route']) }}"
               class="add-btn border font-size-15 rounded-0 text-white"
            >
                @if(isset($addButton['icon']))
                    <i class="{{ $addButton['icon'] }}"></i>
                @endif
                {{ $addButton['label'] }}
            </a>
        </div>
    @endif
</div>
