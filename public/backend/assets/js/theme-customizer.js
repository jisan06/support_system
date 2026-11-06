(function() {
    'use strict';

    var STORAGE_KEY = 'admin_theme_settings';

    var DEFAULTS = {
        theme: 'light',
        direction: 'ltr',
        sidebar: 'dark',
        topbar: 'light',
        layoutWidth: 'fluid',
        sidebarSize: 'default',
        primaryColor: 'default',
        cardHeader: 'default',
        sidebarCustomColor: '#2a3042',
        sidebarCustomTextColor: '#ffffff',
        topbarCustomColor: '#556ee6',
        topbarCustomTextColor: '#ffffff',
        cardHeaderCustomColor: '#556ee6',
        cardHeaderCustomTextColor: '#ffffff'
    };

    function getSettings() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                var parsed = JSON.parse(saved);
                var result = {};
                for (var key in DEFAULTS) {
                    result[key] = parsed[key] !== undefined ? parsed[key] : DEFAULTS[key];
                }
                return result;
            }
        } catch(e) {}
        return JSON.parse(JSON.stringify(DEFAULTS));
    }

    function saveSettings(settings) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
    }

    function applySettings(settings) {
        var html = document.documentElement;
        var body = document.body;

        // Theme mode
        html.setAttribute('data-bs-theme', settings.theme);

        // Direction (RTL/LTR)
        html.setAttribute('dir', settings.direction || 'ltr');

        // Sidebar
        body.setAttribute('data-sidebar', settings.sidebar);
        if (settings.sidebar === 'custom') {
            body.style.setProperty('--custom-sidebar-color', settings.sidebarCustomColor);
            body.style.setProperty('--custom-sidebar-text-color', settings.sidebarCustomTextColor);
        } else {
            body.style.removeProperty('--custom-sidebar-color');
            body.style.removeProperty('--custom-sidebar-text-color');
        }

        // Topbar
        body.setAttribute('data-topbar', settings.topbar);
        if (settings.topbar === 'custom') {
            body.style.setProperty('--custom-topbar-color', settings.topbarCustomColor);
            body.style.setProperty('--custom-topbar-text-color', settings.topbarCustomTextColor);
        } else {
            body.style.removeProperty('--custom-topbar-color');
            body.style.removeProperty('--custom-topbar-text-color');
        }

        // Layout width
        if (settings.layoutWidth === 'boxed') {
            body.setAttribute('data-layout-size', 'boxed');
        } else {
            body.removeAttribute('data-layout-size');
        }

        // Sidebar size
        body.classList.remove('vertical-collpsed');
        body.removeAttribute('data-sidebar-size');
        if (settings.sidebarSize === 'small') {
            body.setAttribute('data-sidebar-size', 'small');
        } else if (settings.sidebarSize === 'icon') {
            body.classList.add('vertical-collpsed');
        }

        // Primary color
        if (settings.primaryColor && settings.primaryColor !== 'default') {
            body.setAttribute('data-primary-color', settings.primaryColor);
            body.style.setProperty('--bs-primary', settings.primaryColor);
            var r = parseInt(settings.primaryColor.slice(1,3), 16);
            var g = parseInt(settings.primaryColor.slice(3,5), 16);
            var b = parseInt(settings.primaryColor.slice(5,7), 16);
            body.style.setProperty('--bs-primary-rgb', r + ',' + g + ',' + b);
        } else {
            body.removeAttribute('data-primary-color');
            body.style.removeProperty('--bs-primary');
            body.style.removeProperty('--bs-primary-rgb');
        }

        // Card header
        if (settings.cardHeader === 'custom') {
            body.setAttribute('data-card-header', 'custom');
            body.style.setProperty('--custom-card-header-color', settings.cardHeaderCustomColor);
            body.style.setProperty('--custom-card-header-text-color', settings.cardHeaderCustomTextColor);
        } else if (settings.cardHeader && settings.cardHeader !== 'default') {
            body.setAttribute('data-card-header', settings.cardHeader);
            body.style.removeProperty('--custom-card-header-color');
            body.style.removeProperty('--custom-card-header-text-color');
        } else {
            body.removeAttribute('data-card-header');
            body.style.removeProperty('--custom-card-header-color');
            body.style.removeProperty('--custom-card-header-text-color');
        }
    }

    function updateUI(settings) {
        // Option buttons
        document.querySelectorAll('.customizer-opt-btn[data-setting]').forEach(function(btn) {
            var setting = btn.getAttribute('data-setting');
            var value = btn.getAttribute('data-value');
            btn.classList.toggle('active', settings[setting] === value);
        });

        // Color preset buttons
        document.querySelectorAll('.customizer-color-btn[data-setting]').forEach(function(btn) {
            var setting = btn.getAttribute('data-setting');
            var value = btn.getAttribute('data-value');
            btn.classList.toggle('active', settings[setting] === value);
        });

        // Primary custom color input
        var primaryInput = document.getElementById('primaryCustomColor');
        if (primaryInput) {
            var isCustomPrimary = settings.primaryColor && settings.primaryColor !== 'default'
                && !document.querySelector('.customizer-color-btn[data-setting="primaryColor"][data-value="' + settings.primaryColor + '"]');
            primaryInput.classList.toggle('active', isCustomPrimary);
            if (isCustomPrimary) {
                primaryInput.value = settings.primaryColor;
                // deactivate all preset buttons
                document.querySelectorAll('.customizer-color-btn[data-setting="primaryColor"]').forEach(function(b) {
                    b.classList.remove('active');
                });
            }
        }

        // Preset active state
        updatePresetUI(findActivePreset(settings));

        // Toggle color pickers visibility
        toggleColorPicker('sidebarColorPicker', settings.sidebar === 'custom', {
            sidebarCustomColor: settings.sidebarCustomColor,
            sidebarCustomTextColor: settings.sidebarCustomTextColor
        });
        toggleColorPicker('topbarColorPicker', settings.topbar === 'custom', {
            topbarCustomColor: settings.topbarCustomColor,
            topbarCustomTextColor: settings.topbarCustomTextColor
        });
        toggleColorPicker('cardHeaderColorPicker', settings.cardHeader === 'custom', {
            cardHeaderCustomColor: settings.cardHeaderCustomColor,
            cardHeaderCustomTextColor: settings.cardHeaderCustomTextColor
        });
    }

    function updatePresetUI(activePreset) {
        document.querySelectorAll('.customizer-preset-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-preset') === activePreset);
        });
    }

    function findActivePreset(settings) {
        for (var name in PRESETS) {
            var preset = PRESETS[name];
            var match = true;
            for (var key in preset) {
                if (settings[key] !== preset[key]) { match = false; break; }
            }
            if (match) return name;
        }
        return null;
    }

    function toggleColorPicker(pickerId, show, values) {
        var picker = document.getElementById(pickerId);
        if (picker) {
            picker.classList.toggle('d-none', !show);
        }
        for (var id in values) {
            var input = document.getElementById(id);
            if (input && values[id]) {
                input.value = values[id];
            }
        }
    }

    var PRESETS = {
        'default': {
            theme: 'light', sidebar: 'dark', topbar: 'light', primaryColor: 'default',
            cardHeader: 'default', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#2a3042', sidebarCustomTextColor: '#ffffff',
            topbarCustomColor: '#556ee6', topbarCustomTextColor: '#ffffff',
            cardHeaderCustomColor: '#556ee6', cardHeaderCustomTextColor: '#ffffff'
        },
        'dark': {
            theme: 'dark', sidebar: 'custom', topbar: 'custom', primaryColor: '#556ee6',
            cardHeader: 'default', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#1a1a2e', sidebarCustomTextColor: '#a6b0cf',
            topbarCustomColor: '#16213e', topbarCustomTextColor: '#a6b0cf',
            cardHeaderCustomColor: '#556ee6', cardHeaderCustomTextColor: '#ffffff'
        },
        'blue': {
            theme: 'light', sidebar: 'custom', topbar: 'custom', primaryColor: '#3b7ddd',
            cardHeader: 'primary', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#1b2a4a', sidebarCustomTextColor: '#c2d0e2',
            topbarCustomColor: '#3b7ddd', topbarCustomTextColor: '#ffffff',
            cardHeaderCustomColor: '#3b7ddd', cardHeaderCustomTextColor: '#ffffff'
        },
        'green': {
            theme: 'light', sidebar: 'custom', topbar: 'light', primaryColor: '#2ab57d',
            cardHeader: 'default', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#1a3c34', sidebarCustomTextColor: '#b2d8cc',
            topbarCustomColor: '#2ab57d', topbarCustomTextColor: '#ffffff',
            cardHeaderCustomColor: '#2ab57d', cardHeaderCustomTextColor: '#ffffff'
        },
        'purple': {
            theme: 'light', sidebar: 'custom', topbar: 'custom', primaryColor: '#7b5ea7',
            cardHeader: 'primary', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#2d2248', sidebarCustomTextColor: '#c4b5d9',
            topbarCustomColor: '#7b5ea7', topbarCustomTextColor: '#ffffff',
            cardHeaderCustomColor: '#7b5ea7', cardHeaderCustomTextColor: '#ffffff'
        },
        'light': {
            theme: 'light', sidebar: 'light', topbar: 'light', primaryColor: 'default',
            cardHeader: 'default', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#ffffff', sidebarCustomTextColor: '#545a6d',
            topbarCustomColor: '#ffffff', topbarCustomTextColor: '#495057',
            cardHeaderCustomColor: '#556ee6', cardHeaderCustomTextColor: '#ffffff'
        },
        'warm': {
            theme: 'light', sidebar: 'custom', topbar: 'custom', primaryColor: '#e74c3c',
            cardHeader: 'primary', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#3d2b1f', sidebarCustomTextColor: '#e0c9b8',
            topbarCustomColor: '#e74c3c', topbarCustomTextColor: '#ffffff',
            cardHeaderCustomColor: '#e74c3c', cardHeaderCustomTextColor: '#ffffff'
        },
        'ocean': {
            theme: 'light', sidebar: 'custom', topbar: 'custom', primaryColor: '#50a5f1',
            cardHeader: 'primary', layoutWidth: 'fluid', sidebarSize: 'default',
            sidebarCustomColor: '#0a3d62', sidebarCustomTextColor: '#aed6f1',
            topbarCustomColor: '#0a3d62', topbarCustomTextColor: '#ffffff',
            cardHeaderCustomColor: '#0a3d62', cardHeaderCustomTextColor: '#ffffff'
        }
    };

    // Apply theme immediately to prevent flash
    var currentSettings = getSettings();
    document.documentElement.setAttribute('data-bs-theme', currentSettings.theme);

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        var settings = getSettings();
        applySettings(settings);
        updateUI(settings);

        var panel = document.getElementById('theme-customizer');
        var overlay = document.getElementById('customizer-overlay');
        var toggleBtn = document.getElementById('theme-customizer-toggle');
        var closeBtn = document.getElementById('customizer-close');

        function openPanel() {
            panel.classList.add('open');
            overlay.classList.add('show');
        }

        function closePanel() {
            panel.classList.remove('open');
            overlay.classList.remove('show');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openPanel);
        if (closeBtn) closeBtn.addEventListener('click', closePanel);
        if (overlay) overlay.addEventListener('click', closePanel);

        // Preset clicks
        document.querySelectorAll('.customizer-preset-btn[data-preset]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var presetName = this.getAttribute('data-preset');
                var preset = PRESETS[presetName];
                if (preset) {
                    settings = JSON.parse(JSON.stringify(preset));
                    saveSettings(settings);
                    applySettings(settings);
                    updateUI(settings);
                    updatePresetUI(presetName);
                }
            });
        });

        // Option button clicks
        document.querySelectorAll('.customizer-opt-btn[data-setting]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var setting = this.getAttribute('data-setting');
                var value = this.getAttribute('data-value');
                settings[setting] = value;
                saveSettings(settings);
                applySettings(settings);
                updateUI(settings);
            });
        });

        // Color preset button clicks
        document.querySelectorAll('.customizer-color-btn[data-setting]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var setting = this.getAttribute('data-setting');
                var value = this.getAttribute('data-value');
                settings[setting] = value;
                saveSettings(settings);
                applySettings(settings);
                updateUI(settings);
            });
        });

        // Primary color custom picker
        var primaryInput = document.getElementById('primaryCustomColor');
        if (primaryInput) {
            primaryInput.addEventListener('input', function() {
                settings.primaryColor = this.value;
                saveSettings(settings);
                applySettings(settings);
                updateUI(settings);
            });
        }

        // Custom color picker inputs (bg + text)
        var colorInputs = [
            { id: 'sidebarCustomColor', key: 'sidebarCustomColor' },
            { id: 'sidebarCustomTextColor', key: 'sidebarCustomTextColor' },
            { id: 'topbarCustomColor', key: 'topbarCustomColor' },
            { id: 'topbarCustomTextColor', key: 'topbarCustomTextColor' },
            { id: 'cardHeaderCustomColor', key: 'cardHeaderCustomColor' },
            { id: 'cardHeaderCustomTextColor', key: 'cardHeaderCustomTextColor' }
        ];

        colorInputs.forEach(function(item) {
            var input = document.getElementById(item.id);
            if (input) {
                input.addEventListener('input', function() {
                    settings[item.key] = this.value;
                    saveSettings(settings);
                    applySettings(settings);
                });
            }
        });

        // Reset
        document.getElementById('customizer-reset').addEventListener('click', function() {
            settings = JSON.parse(JSON.stringify(DEFAULTS));
            saveSettings(settings);
            applySettings(settings);
            updateUI(settings);
        });
    });
})();