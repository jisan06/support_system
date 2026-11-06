<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                @foreach(app(\App\Services\MenuSortService::class)->getSortedMenu() as $menu)
                    @php
                        $visibleChildren = [];

                        // For items with sub_menu, filter children by permission
                        if (!empty($menu['sub_menu'])) {
                            $visibleChildren = collect($menu['sub_menu'])->filter(function ($sub) {
                                return empty($sub['permission']) || auth()->user()->hasPermissionTo($sub['permission']);
                            })->values()->all();

                            // Skip parent if no children are visible
                            if (empty($visibleChildren)) continue;
                        } else {
                            // For single items, check permission
                            if (!empty($menu['permission']) && !auth()->user()->hasPermissionTo($menu['permission'])) continue;
                        }
                    @endphp
                    <li>
                        <a
                            href="{{!empty($menu['route']) ? route($menu['route']) : 'javascript:'}}"
                            class="waves-effect @if(!empty($menu['sub_menu'])) has-arrow @endif"
                        >
                            @if($menu['icon'])
                                <i class="{{$menu['icon']}}"></i>
                            @endif
                            <span key="t-dashboards">{{trans_choice($menu['title'], 2)}}</span>
                        </a>
                        @if(!empty($visibleChildren))
                            <ul
                                class="sub-menu"
                                aria-expanded="false"
                            >
                                @foreach($visibleChildren as $sub_menu)
                                    <li class="{{ \App\Helpers\MenuHelper::isActive($sub_menu['slug'], $sub_menu['route_params'] ?? null) ? 'mm-active' : '' }}">
                                        <a
                                            href="{{!empty($sub_menu['route']) ? route($sub_menu['route'], $sub_menu['route_params'] ?? []) : 'javascript:'}}"
                                            key="t-tui-calendar"
                                        >
                                            @if($sub_menu['icon'])
                                                <i class="{{$sub_menu['icon']}}"></i>
                                            @endif
                                            {{trans_choice($sub_menu['title'], 2)}}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach

            </ul>
        </div>
        <!-- Sidebar -->

        <div class="text-center text-muted py-3" style="font-size: 11px;">
            v{{ config('app-version.version', '1.0.0') }}
        </div>
    </div>
</div>
