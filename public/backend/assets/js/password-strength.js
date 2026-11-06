/**
 * Password Strength Meter
 * Attach to any password input with: data-strength-meter
 * Rules are read from data-strength-rules JSON attribute.
 */
(function () {
    var defaultRules = {
        min_length: 8,
        uppercase: true,
        lowercase: true,
        numbers: true,
        symbols: false
    };

    function checkRules(password, rules) {
        var results = [];

        results.push({
            key: 'min_length',
            label: 'At least ' + rules.min_length + ' characters',
            pass: password.length >= rules.min_length
        });

        if (rules.lowercase) {
            results.push({
                key: 'lowercase',
                label: 'One lowercase letter',
                pass: /[a-z]/.test(password)
            });
        }

        if (rules.uppercase) {
            results.push({
                key: 'uppercase',
                label: 'One uppercase letter',
                pass: /[A-Z]/.test(password)
            });
        }

        if (rules.numbers) {
            results.push({
                key: 'numbers',
                label: 'One number',
                pass: /\d/.test(password)
            });
        }

        if (rules.symbols) {
            results.push({
                key: 'symbols',
                label: 'One special character (!@#$...)',
                pass: /[^a-zA-Z0-9]/.test(password)
            });
        }

        return results;
    }

    function getStrength(results) {
        var total = results.length;
        var passed = results.filter(function (r) { return r.pass; }).length;

        if (total === 0) return { label: '', cls: '', percent: 0 };

        var percent = Math.round((passed / total) * 100);

        if (passed === total) return { label: 'Strong', cls: 'strong', percent: percent };
        if (percent >= 60)    return { label: 'Good', cls: 'good', percent: percent };
        if (percent >= 40)    return { label: 'Fair', cls: 'fair', percent: percent };
        return { label: 'Weak', cls: 'weak', percent: percent };
    }

    function createMeter(input) {
        var rules = defaultRules;
        if (input.dataset.strengthRules) {
            try { rules = JSON.parse(input.dataset.strengthRules); } catch (e) {}
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'password-strength-meter';

        var barHtml = '<div class="strength-bar"><div class="strength-fill"></div></div>';
        var labelHtml = '<small class="strength-label"></small>';

        var ruleList = checkRules('', rules);
        var rulesHtml = '<ul class="strength-rules">';
        ruleList.forEach(function (r) {
            rulesHtml += '<li data-rule="' + r.key + '">' +
                '<i class="rule-icon"></i> ' + r.label + '</li>';
        });
        rulesHtml += '</ul>';

        wrapper.innerHTML = barHtml + labelHtml + rulesHtml;
        input.parentNode.insertBefore(wrapper, input.nextSibling);

        var fill = wrapper.querySelector('.strength-fill');
        var label = wrapper.querySelector('.strength-label');
        var ruleItems = wrapper.querySelectorAll('.strength-rules li');

        input.addEventListener('input', function () {
            var password = this.value;

            if (!password) {
                wrapper.classList.remove('active');
                fill.style.width = '0';
                fill.className = 'strength-fill';
                label.textContent = '';
                label.className = 'strength-label';
                ruleItems.forEach(function (li) { li.className = ''; });
                return;
            }

            wrapper.classList.add('active');

            var results = checkRules(password, rules);
            var strength = getStrength(results);

            fill.style.width = strength.percent + '%';
            fill.className = 'strength-fill ' + strength.cls;
            label.textContent = strength.label;
            label.className = 'strength-label ' + strength.cls;

            results.forEach(function (r, i) {
                if (ruleItems[i]) {
                    ruleItems[i].className = r.pass ? 'pass' : 'fail';
                }
            });
        });
    }

    function init() {
        var inputs = document.querySelectorAll('[data-strength-meter]');
        inputs.forEach(function (input) {
            createMeter(input);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
