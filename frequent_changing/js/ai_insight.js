/* AI Insight page: "Show all" rows, the busy state of the Regenerate button and the Ask AI panel.
   Report and answer text is escaped on the server (plan S6): answers arrive as safe HTML,
   the user's own question is shown with textContent. */
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.ai-more-btn') : null;
        if (!btn) {
            return;
        }
        var box = btn.closest('.ai-collapsible');
        if (!box) {
            return;
        }
        if (!btn.getAttribute('data-more')) {
            btn.setAttribute('data-more', btn.textContent);
        }
        var collapsed = box.classList.toggle('ai-collapsed');
        btn.textContent = collapsed ? btn.getAttribute('data-more') : btn.getAttribute('data-less');
    });
    var forms = document.querySelectorAll('.ai-regen-form');
    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (e) {
            var btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) {
                e.preventDefault();
                return;
            }
            // the request takes up to a minute: one click only
            btn.disabled = true;
            btn.textContent = btn.getAttribute('data-busy');
        });
    });

    /* ---------- Ask AI ---------- */
    var chat = document.getElementById('ai_chat');
    if (!chat) {
        return;
    }
    var log = chat.querySelector('.ai-chat-log');
    var form = chat.querySelector('.ai-chat-form');
    var box = form.querySelector('textarea');
    var send = form.querySelector('button[type="submit"]');
    var left = chat.querySelector('.ai-chat-left');
    var suggest = chat.querySelector('.ai-chat-suggest');

    function add(kind, content, asHtml) {
        var div = document.createElement('div');
        div.className = 'ai-msg ' + kind;
        if (asHtml) {
            div.innerHTML = content; // server-escaped answer HTML
        } else {
            div.textContent = content;
        }
        log.appendChild(div);
        log.scrollTop = log.scrollHeight;
        return div;
    }
    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-AI-CSRF': chat.getAttribute('data-token'), 'X-Requested-With': 'XMLHttpRequest'},
            body: new URLSearchParams(data)
        }).then(function (r) {
            return r.json();
        });
    }
    function ask() {
        var question = box.value.trim();
        if (!question || send.disabled) {
            return;
        }
        add('is-user', question, false);
        box.value = '';
        suggest.hidden = true;
        send.disabled = true;
        var wait = add('is-wait', chat.getAttribute('data-thinking'), false);
        post(chat.getAttribute('data-send'), {question: question, session_id: chat.getAttribute('data-session')})
            .then(function (j) {
                wait.remove();
                if (j.ok) {
                    add('is-ai', j.html, true);
                    chat.setAttribute('data-session', j.session_id);
                } else {
                    add('is-error', j.error || chat.getAttribute('data-error'), false);
                }
                if (j.left) {
                    left.textContent = j.left;
                }
            })
            .catch(function () {
                wait.remove();
                add('is-error', chat.getAttribute('data-error'), false);
            })
            .then(function () {
                send.disabled = false;
                box.focus();
            });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-chat-open]'), function (btn) {
        btn.addEventListener('click', function () {
            chat.hidden = false;
            log.scrollTop = log.scrollHeight;
            box.focus();
        });
    });
    chat.querySelector('[data-chat-close]').addEventListener('click', function () {
        chat.hidden = true;
    });
    chat.querySelector('[data-chat-new]').addEventListener('click', function () {
        post(chat.getAttribute('data-new'), {}).then(function (j) {
            if (j.ok) {
                chat.setAttribute('data-session', '0');
                log.innerHTML = '';
                suggest.hidden = false;
                if (j.left) {
                    left.textContent = j.left;
                }
                box.focus();
            }
        }).catch(function () {
            add('is-error', chat.getAttribute('data-error'), false);
        });
    });
    suggest.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('button') : null;
        if (b) {
            box.value = b.textContent.trim();
            ask();
        }
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        ask();
    });
    box.addEventListener('keydown', function (e) {
        // Enter sends, Shift+Enter makes a new line
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            ask();
        }
    });
})();
