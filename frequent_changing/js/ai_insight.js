/* AI Insight page: "Show all" rows and the busy state of the Regenerate button.
   No report text is inserted here: the server renders it escaped (plan S6). */
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
})();
