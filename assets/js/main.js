"use strict";

function updateCartBadge(count) {
    var badge = document.getElementById('cart-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';
}

function showNotification(message, type) {
    type = type || 'success';
    var existing = document.getElementById('toast-notification');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.style.cssText = [
        'position:fixed',
        'top:80px',
        'right:1.5rem',
        'background:' + (type === 'success' ? '#2d4a3e' : '#c0392b'),
        'color:white',
        'padding:0.85rem 1.5rem',
        'border-radius:8px',
        'box-shadow:0 4px 20px rgba(0,0,0,0.2)',
        'z-index:9999',
        'font-size:0.9rem',
        'font-weight:500'
    ].join(';');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 3500);
}

document.addEventListener('DOMContentLoaded', function() {

    // ── Add to Cart AJAX ──────────────────────────────────────
    document.querySelectorAll('[data-ajax-cart="true"]').forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Build absolute URL safely — handles both relative and root-relative paths
            var actionAttr = form.getAttribute('action'); // e.g. "cart_action.php" or "/pages/cart_action.php"
            var actionUrl;

            if (actionAttr.indexOf('http') === 0) {
                // Already absolute
                actionUrl = actionAttr;
            } else if (actionAttr.charAt(0) === '/') {
                // Root-relative: prepend just the origin (no trailing slash issue)
                actionUrl = window.location.protocol + '//' + window.location.host + actionAttr;
            } else {
                // Relative to current directory
                var dir = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
                actionUrl = window.location.protocol + '//' + window.location.host + dir + actionAttr;
            }

            var btn = form.querySelector('[type="submit"]');
            var originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            try {
                var response = await fetch(actionUrl, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                var contentType = response.headers.get('content-type') || '';
                if (contentType.indexOf('application/json') === -1) {
                    // Not JSON — get the text to show a useful error
                    var text = await response.text();
                    console.error('Non-JSON response from server:', text);
                    throw new Error('Server error (HTTP ' + response.status + '). Check console for details.');
                }

                var data = await response.json();

                if (data.success) {
                    updateCartBadge(data.cart_count);
                    showNotification(data.message || 'Added to cart!', 'success');
                    btn.innerHTML = '<i class="bi bi-check" aria-hidden="true"></i> Added';
                    btn.style.background = '#27ae60';
                    setTimeout(function() {
                        btn.innerHTML = originalHTML;
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 2000);
                } else {
                    showNotification(data.message || 'Could not add item.', 'error');
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }

            } catch (err) {
                console.error('Cart AJAX error:', err);
                showNotification(err.message || 'Something went wrong.', 'error');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        });
    });

    // ── Quantity Controls ─────────────────────────────────────
    document.querySelectorAll('.qty-control').forEach(function(ctrl) {
        var input  = ctrl.querySelector('input[type="number"]');
        var btnDec = ctrl.querySelector('[data-action="decrement"]');
        var btnInc = ctrl.querySelector('[data-action="increment"]');

        if (btnDec && input) {
            btnDec.addEventListener('click', function() {
                var val = parseInt(input.value, 10);
                if (val > 1) { input.value = val - 1; }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
        if (btnInc && input) {
            btnInc.addEventListener('click', function() {
                input.value = parseInt(input.value, 10) + 1;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });

    // ── Register Form Validation ──────────────────────────────
    var registerForm = document.getElementById('register-form');
    if (registerForm) {
        var emailEl    = document.getElementById('email');
        var passwordEl = document.getElementById('password');
        var confirmEl  = document.getElementById('confirm_password');

        function setValidity(el, msg) {
            if (!el) return;
            var fb = el.nextElementSibling;
            if (msg) {
                el.classList.add('is-invalid');
                el.classList.remove('is-valid');
                if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = msg;
            } else {
                el.classList.remove('is-invalid');
                el.classList.add('is-valid');
            }
        }

        

        if (emailEl) emailEl.addEventListener('input', function() {
            setValidity(emailEl, /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim()) ? null : 'Enter a valid email.');
        });

        if (passwordEl) passwordEl.addEventListener('input', function() {
            var v = passwordEl.value;
            var msg = null;
            if (v.length < 10)         msg = 'At least 10 characters required.';
            else if (!/[A-Z]/.test(v))            msg = 'Include at least one uppercase letter.';
            else if (!/[a-z]/.test(v))            msg = 'Include at least one lowercase letter.';
            else if (!/[0-9]/.test(v))            msg = 'Include at least one number.';
            else if (!/[^A-Za-z0-9]/.test(v))    msg = 'Include at least one special character.';
            setValidity(passwordEl, msg);
            if (confirmEl && confirmEl.value) {
                if (msg) {
                    confirmEl.classList.remove('is-valid', 'is-invalid');
                } else {
                    setValidity(confirmEl, confirmEl.value === v ? null : 'Passwords do not match.');
                }
            }
        });

        if (confirmEl) confirmEl.addEventListener('input', function() {
            var passwordValid = passwordEl && !passwordEl.classList.contains('is-invalid') && passwordEl.value;
            if (!passwordValid) {
                confirmEl.classList.remove('is-valid', 'is-invalid');
            } else {
                setValidity(confirmEl, confirmEl.value === passwordEl.value ? null : 'Passwords do not match.');
            }
        });

        registerForm.addEventListener('submit', function(e) {
            [emailEl, passwordEl, confirmEl].forEach(function(el) {
                if (el) el.dispatchEvent(new Event('input'));
            });
            if (registerForm.querySelectorAll('.is-invalid').length > 0) {
                e.preventDefault();
                showNotification('Please fix the errors before submitting.', 'error');
            }
        });
    }

    // ── Login Form Validation ─────────────────────────────────
    var loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            var em = document.getElementById('email');
            var pw = document.getElementById('password');
            var ok = true;
            if (em && !em.value.trim()) { em.classList.add('is-invalid'); ok = false; }
            if (pw && !pw.value)        { pw.classList.add('is-invalid'); ok = false; }
            if (!ok) { e.preventDefault(); showNotification('Please fill in all fields.', 'error'); }
        });
    }

    // ── Active nav link ───────────────────────────────────────
    var currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(function(link) {
        var href = link.getAttribute('href');
        if (href && currentPath.endsWith(href.split('/').pop())) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        }
    });

});