{{-- Offcanvas panel --}}
<div id="theme-customizer" class="theme-customizer-panel">
    <div class="customizer-header">
        <h5 class="mb-0">Appearance</h5>
        <button type="button" id="customizer-close" class="btn-close"></button>
    </div>

    <div class="customizer-body" data-simplebar>

        {{-- Presets --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Presets</h6>
            <div class="d-flex gap-2 flex-wrap">
                <button class="customizer-preset-btn" data-preset="default" title="Default">
                    <span class="preset-sidebar" style="background:#2a3042;"></span>
                    <span class="preset-topbar" style="background:#fff;border-bottom:1px solid #e9ecef;"></span>
                    <span class="preset-body" style="background:#f8f8fb;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="dark" title="Dark">
                    <span class="preset-sidebar" style="background:#1a1a2e;"></span>
                    <span class="preset-topbar" style="background:#16213e;"></span>
                    <span class="preset-body" style="background:#0f0f23;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="blue" title="Blue">
                    <span class="preset-sidebar" style="background:#1b2a4a;"></span>
                    <span class="preset-topbar" style="background:#3b7ddd;"></span>
                    <span class="preset-body" style="background:#f5f7fb;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="green" title="Green">
                    <span class="preset-sidebar" style="background:#1a3c34;"></span>
                    <span class="preset-topbar" style="background:#fff;border-bottom:1px solid #e9ecef;"></span>
                    <span class="preset-body" style="background:#f5f7f5;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="purple" title="Purple">
                    <span class="preset-sidebar" style="background:#2d2248;"></span>
                    <span class="preset-topbar" style="background:#7b5ea7;"></span>
                    <span class="preset-body" style="background:#f8f5fb;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="light" title="Light">
                    <span class="preset-sidebar" style="background:#fff;border-right:1px solid #e9ecef;"></span>
                    <span class="preset-topbar" style="background:#fff;border-bottom:1px solid #e9ecef;"></span>
                    <span class="preset-body" style="background:#f8f8fb;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="warm" title="Warm">
                    <span class="preset-sidebar" style="background:#3d2b1f;"></span>
                    <span class="preset-topbar" style="background:#e74c3c;"></span>
                    <span class="preset-body" style="background:#fdf6f0;"></span>
                </button>
                <button class="customizer-preset-btn" data-preset="ocean" title="Ocean">
                    <span class="preset-sidebar" style="background:#0a3d62;"></span>
                    <span class="preset-topbar" style="background:#0a3d62;"></span>
                    <span class="preset-body" style="background:#f0f8ff;"></span>
                </button>
            </div>
        </div>

        <hr class="my-2">

        {{-- Theme Mode --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Theme Mode</h6>
            <div class="d-flex gap-2">
                <button class="customizer-opt-btn active" data-setting="theme" data-value="light">
                    <i class="bx bx-sun"></i> Light
                </button>
                <button class="customizer-opt-btn" data-setting="theme" data-value="dark">
                    <i class="bx bx-moon"></i> Dark
                </button>
            </div>
        </div>

        {{-- Direction --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Direction</h6>
            <div class="d-flex gap-2">
                <button class="customizer-opt-btn active" data-setting="direction" data-value="ltr">
                    <i class="bx bx-align-left"></i> LTR
                </button>
                <button class="customizer-opt-btn" data-setting="direction" data-value="rtl">
                    <i class="bx bx-align-right"></i> RTL
                </button>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Sidebar</h6>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <button class="customizer-opt-btn active" data-setting="sidebar" data-value="dark">
                    Dark
                </button>
                <button class="customizer-opt-btn" data-setting="sidebar" data-value="light">
                    Light
                </button>
                <button class="customizer-opt-btn" data-setting="sidebar" data-value="colored">
                    Colored
                </button>
                <button class="customizer-opt-btn" data-setting="sidebar" data-value="custom">
                    Custom
                </button>
            </div>
            <div class="customizer-color-pick d-none" id="sidebarColorPicker">
                <div class="d-flex gap-2">
                    <div class="flex-fill">
                        <label class="form-label mb-1" style="font-size:12px;">Background</label>
                        <input type="color" class="form-control form-control-color w-100" id="sidebarCustomColor" value="#2a3042">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label mb-1" style="font-size:12px;">Text</label>
                        <input type="color" class="form-control form-control-color w-100" id="sidebarCustomTextColor" value="#ffffff">
                    </div>
                </div>
            </div>
        </div>

        {{-- Topbar --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Topbar</h6>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <button class="customizer-opt-btn active" data-setting="topbar" data-value="light">
                    Light
                </button>
                <button class="customizer-opt-btn" data-setting="topbar" data-value="dark">
                    Dark
                </button>
                <button class="customizer-opt-btn" data-setting="topbar" data-value="colored">
                    Colored
                </button>
                <button class="customizer-opt-btn" data-setting="topbar" data-value="custom">
                    Custom
                </button>
            </div>
            <div class="customizer-color-pick d-none" id="topbarColorPicker">
                <div class="d-flex gap-2">
                    <div class="flex-fill">
                        <label class="form-label mb-1" style="font-size:12px;">Background</label>
                        <input type="color" class="form-control form-control-color w-100" id="topbarCustomColor" value="#556ee6">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label mb-1" style="font-size:12px;">Text</label>
                        <input type="color" class="form-control form-control-color w-100" id="topbarCustomTextColor" value="#ffffff">
                    </div>
                </div>
            </div>
        </div>

        {{-- Layout Width --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Layout Width</h6>
            <div class="d-flex gap-2">
                <button class="customizer-opt-btn active" data-setting="layoutWidth" data-value="fluid">
                    Fluid
                </button>
                <button class="customizer-opt-btn" data-setting="layoutWidth" data-value="boxed">
                    Boxed
                </button>
            </div>
        </div>

        {{-- Sidebar Size --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Sidebar Size</h6>
            <div class="d-flex gap-2 flex-wrap">
                <button class="customizer-opt-btn active" data-setting="sidebarSize" data-value="default">
                    Default
                </button>
                <button class="customizer-opt-btn" data-setting="sidebarSize" data-value="small">
                    Compact
                </button>
                <button class="customizer-opt-btn" data-setting="sidebarSize" data-value="icon">
                    Icon
                </button>
            </div>
        </div>

        {{-- Primary Color --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Primary Color</h6>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button class="customizer-color-btn active" data-setting="primaryColor" data-value="default" style="background:#556ee6;" title="Default"></button>
                <button class="customizer-color-btn" data-setting="primaryColor" data-value="#2ab57d" style="background:#2ab57d;" title="Green"></button>
                <button class="customizer-color-btn" data-setting="primaryColor" data-value="#e83e8c" style="background:#e83e8c;" title="Pink"></button>
                <button class="customizer-color-btn" data-setting="primaryColor" data-value="#e74c3c" style="background:#e74c3c;" title="Red"></button>
                <button class="customizer-color-btn" data-setting="primaryColor" data-value="#f1b44c" style="background:#f1b44c;" title="Orange"></button>
                <button class="customizer-color-btn" data-setting="primaryColor" data-value="#50a5f1" style="background:#50a5f1;" title="Cyan"></button>
                <button class="customizer-color-btn" data-setting="primaryColor" data-value="#7b5ea7" style="background:#7b5ea7;" title="Purple"></button>
                <input type="color" class="customizer-color-input" id="primaryCustomColor" value="#556ee6" title="Custom Color">
            </div>
        </div>

        {{-- Card Header --}}
        <div class="customizer-section">
            <h6 class="customizer-title">Card Header</h6>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <button class="customizer-opt-btn active" data-setting="cardHeader" data-value="default">
                    Default
                </button>
                <button class="customizer-opt-btn" data-setting="cardHeader" data-value="primary">
                    Primary
                </button>
                <button class="customizer-opt-btn" data-setting="cardHeader" data-value="dark">
                    Dark
                </button>
                <button class="customizer-opt-btn" data-setting="cardHeader" data-value="custom">
                    Custom
                </button>
            </div>
            <div class="customizer-color-pick d-none" id="cardHeaderColorPicker">
                <div class="d-flex gap-2">
                    <div class="flex-fill">
                        <label class="form-label mb-1" style="font-size:12px;">Background</label>
                        <input type="color" class="form-control form-control-color w-100" id="cardHeaderCustomColor" value="#556ee6">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label mb-1" style="font-size:12px;">Text</label>
                        <input type="color" class="form-control form-control-color w-100" id="cardHeaderCustomTextColor" value="#ffffff">
                    </div>
                </div>
            </div>
        </div>

        {{-- Reset --}}
        <div class="customizer-section border-top pt-3">
            <button class="btn btn-outline-danger w-100" id="customizer-reset">
                <i class="bx bx-reset me-1"></i> Reset to Default
            </button>
        </div>

    </div>
</div>

{{-- Overlay --}}
<div id="customizer-overlay"></div>