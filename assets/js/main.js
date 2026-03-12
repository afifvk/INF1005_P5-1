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

    // ── Personalitea Quiz: show/hide and basic result mapping ──
    var startBtn = document.getElementById('start-quiz-btn');
    var quizSection = document.getElementById('quiz-section');
    var quizForm = document.getElementById('personalitea-quiz');
    var quizResults = document.getElementById('quiz-results');
    var quizCancel = document.getElementById('quiz-cancel');
    var APP = window.__APP__ || { isLoggedIn: false, csrfToken: '' };

    function showQuiz() {
        // Show the actual quiz form (moved below the hero)
        if (quizForm) {
            quizForm.style.display = '';
            quizForm.setAttribute('aria-hidden', 'false');
        }
        if (quizResults) quizResults.style.display = 'none';
        // focus first input for accessibility
        try {
            var firstInput = quizForm && quizForm.querySelector('input[type="radio"], input[type="text"], select, textarea');
            if (firstInput) firstInput.focus();
        } catch (e) {}
        window.location.hash = '#quiz-heading';
    }
    function hideQuiz() {
        if (quizForm) {
            quizForm.style.display = 'none';
            quizForm.setAttribute('aria-hidden', 'true');
            try { quizForm.reset(); } catch (e) {}
        }
        if (quizResults) quizResults.style.display = 'none';
    }

    if (startBtn) startBtn.addEventListener('click', function() { showQuiz(); });
    if (quizCancel) quizCancel.addEventListener('click', function() { hideQuiz(); });

    if (quizForm) {
        quizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var data = new FormData(quizForm);
            var answers = {
                q1: data.get('q1'),
                q2: data.get('q2'),
                q3: data.get('q3'),
                q4: data.get('q4')
            };

            // Produce a tea personality and other suggested types.
            var score = 0;
            if (answers.q1 === 'light') score += 1;
            if (answers.q1 === 'earthy') score += 2;
            if (answers.q1 === 'sweet') score += 3;
            if (answers.q2 === 'morning') score += 2;
            if (answers.q2 === 'afternoon') score += 1;
            if (answers.q2 === 'evening') score += 3;
            if (answers.q3 === 'adventurous') score += 3;
            if (answers.q3 === 'cozy') score += 1;
            if (answers.q3 === 'focused') score += 2;
            if (answers.q4 === 'citrus') score += 1;
            if (answers.q4 === 'vanilla') score += 2;
            if (answers.q4 === 'smoky') score += 3;

            var personality = 'Balanced Sipper';
            var suggestions = [];
            if (score <= 5) {
                personality = 'Bright & Floral';
                suggestions = [
                    { title: 'Jasmine Blossom', desc: 'A light, floral cup.' },
                    { title: 'Citrus Earl', desc: 'Bright notes for daytime.' }
                ];
            } else if (score <= 8) {
                personality = 'Cozy Comfort';
                suggestions = [
                    { title: 'Vanilla Chai', desc: 'Warm and sweet.' },
                    { title: 'Honey Rooibos', desc: 'Smooth, caffeine-free option.' }
                ];
            } else {
                personality = 'Bold Explorer';
                suggestions = [
                    { title: 'Smoky Lapsang', desc: 'Deep and smoky flavors.' },
                    { title: 'Earthy Pu-erh', desc: 'Rich and grounding.' }
                ];
            }

            // Render results. Include a link to browse full catalog.
            if (quizResults) {
                quizResults.style.display = '';
                var html = '<div class="form-wrapper">'
                    + '<h3>Your Personalitea: ' + personality + '</h3>'
                    + '<p>Based on your answers, we suggest the following:</p>'
                    + '<div class="row g-3">';

                suggestions.forEach(function(s, idx) {
                    html += '<div class="col-md-6"><div class="value-card">'
                        + '<h4>' + s.title + '</h4>'
                        + '<p class="text-muted">' + s.desc + '</p>';

                    // If logged in, allow saving the first suggestion
                    if (APP.isLoggedIn) {
                        html += '<div class="mt-2">'
                            + '<button class="btn-store save-reco" data-product="' + encodeURIComponent(s.title) + '" data-idx="' + idx + '">Save recommendation</button>'
                            + '</div>';
                    }

                    html += '<a class="btn-store-outline mt-2 d-inline-block" href="/pages/products.php">View similar</a>'
                        + '</div></div>';
                });

                html += '</div>'
                    + '<div class="text-center" style="margin-top:1rem;">'
                    + '<a class="btn-gold" href="/pages/products.php">Browse for more</a>'
                    + '</div>';

                // If not logged in, show CTA to login or skip
                if (!APP.isLoggedIn) {
                    html += '<div class="mt-3 text-center small text-muted">'
                        + '<p>Login to save your personalised tea recommendations so they are remembered.</p>'
                        + '<a href="/pages/login.php" class="btn-store-outline me-2">Login to save</a>'
                        + '<button id="skip-save" class="btn-store">Skip for now</button>'
                        + '</div>';
                }

                html += '</div>';
                quizResults.innerHTML = html;
                surveyScrollIntoView(quizResults);

                // Attach save handlers for logged-in users
                if (APP.isLoggedIn) {
                    document.querySelectorAll('.save-reco').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var productTitle = btn.getAttribute('data-product');
                            // For demo we don't have product IDs on front-end; send the title as product identifier
                            saveRecommendation({ product_title: productTitle, answers: JSON.stringify(answers) }, function(resp) {
                                if (resp && resp.success) {
                                    showNotification('Recommendation saved.', 'success');
                                } else {
                                    showNotification((resp && resp.message) || 'Could not save.', 'error');
                                }
                            });
                        });
                    });
                } else {
                    var skipBtn = document.getElementById('skip-save');
                    if (skipBtn) skipBtn.addEventListener('click', function() { showNotification('You can save later after logging in.', 'success'); });
                }
            }
        });
    }

    function surveyScrollIntoView(el) {
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Save recommendation (AJAX) — demo: sends title and answers; server may map title -> product id
    function saveRecommendation(payload, cb) {
        var url = window.location.origin + '/pages/save_recommendation.php';
        var form = new FormData();
        // map demo payload into fields server expects: product_id is not available here, so frontend sends product_title
        form.append('product_title', payload.product_title || payload.product_title || '');
        form.append('answers', payload.answers || '');
        form.append('csrf_token', APP.csrfToken || '');

        fetch(url, { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(data){ cb(data); })
            .catch(function(){ cb(null); });
    }


    // ── Gemini Chatbot ───────────────────────────────────────
    if (APP.geminiChatEnabled) {
        var chatbotToggle = document.getElementById('chatbot-toggle');
        var chatbotPanel = document.getElementById('chatbot-panel');
        var chatbotClose = document.getElementById('chatbot-close');
        var chatbotForm = document.getElementById('chatbot-form');
        var chatbotInput = document.getElementById('chatbot-input');
        var chatbotMessages = document.getElementById('chatbot-messages');
        var chatbotSend = document.getElementById('chatbot-send');
        var chatHistory = [];

        function appendChatMessage(text, role) {
            if (!chatbotMessages) return;
            var msg = document.createElement('div');
            msg.className = 'chatbot-message ' + (role === 'user' ? 'chatbot-message-user' : 'chatbot-message-bot');
            msg.textContent = text;
            chatbotMessages.appendChild(msg);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        function setChatOpen(open) {
            if (!chatbotPanel || !chatbotToggle) return;
            chatbotPanel.hidden = !open;
            chatbotToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && chatbotInput) chatbotInput.focus();
        }

        if (chatbotToggle) {
            chatbotToggle.addEventListener('click', function() {
                setChatOpen(chatbotPanel.hidden);
            });
        }

        if (chatbotClose) {
            chatbotClose.addEventListener('click', function() {
                setChatOpen(false);
            });
        }

        if (chatbotForm) {
            chatbotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var message = chatbotInput ? chatbotInput.value.trim() : '';
                if (!message) return;

                appendChatMessage(message, 'user');
                chatHistory.push({ role: 'user', text: message });
                if (chatbotInput) chatbotInput.value = '';
                if (chatbotSend) chatbotSend.disabled = true;

                var typing = document.createElement('div');
                typing.className = 'chatbot-message chatbot-message-bot chatbot-typing';
                typing.textContent = 'Tea Assistant is typing...';
                chatbotMessages.appendChild(typing);
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

                fetch((APP.siteUrl || window.location.origin) + '/pages/gemini_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': APP.csrfToken || ''
                    },
                    body: JSON.stringify({
                        message: message,
                        history: chatHistory
                    })
                })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (typing.parentNode) typing.remove();
                        if (data && data.success && data.reply) {
                            appendChatMessage(data.reply, 'bot');
                            chatHistory.push({ role: 'model', text: data.reply });
                        } else {
                            appendChatMessage((data && data.message) || 'Sorry, the chatbot is unavailable right now.', 'bot');
                        }
                    })
                    .catch(function() {
                        if (typing.parentNode) typing.remove();
                        appendChatMessage('Sorry, the chatbot is unavailable right now.', 'bot');
                    })
                    .finally(function() {
                        if (chatbotSend) chatbotSend.disabled = false;
                    });
            });
        }
    }


});