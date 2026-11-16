/*
 * Orchestra UI behavior, shared by all Orchestra pages. Everything works without JavaScript as
 * well; the server checks every rule again.
 *
 * - data-orchestra-confirm="Message"            on a form: asks before submitting
 *   data-orchestra-confirm-field="status"        only ask if this field's value ...
 *   data-orchestra-confirm-unless="accepted"     ... is not this value
 * - data-orchestra-move-confirm="Message"       on a form with a <select multiple> or checkboxes:
 *   asks if a selected option or checked box has data-current, then sets the hidden field "move"
 *   to 1
 * - data-orchestra-add-row="template-id"        on a button: appends the <template> content to
 *   the element named in data-orchestra-target; "__INDEX__" is replaced by a new number
 * - data-orchestra-confirm="Message"            on a submit button: asks before submitting with it
 * - data-orchestra-remove-row                   on a button: removes the row (<tr> or
 *   [data-orchestra-row]) it is in
 * - data-orchestra-switch                       on a <select>: in the same row (<tr> or
 *   [data-orchestra-row]), elements with data-orchestra-show-for="value1 value2" are only shown
 *   while one of these values is selected; also in rows added with data-orchestra-add-row
 * - data-orchestra-sortable                     on a container (e.g. <tbody>): its children move one
 *   place up or down with their buttons [data-orchestra-move="up"] and [data-orchestra-move="down"]
 *   (Html::moveButtons(), since 0.19; drag and drop until 0.18). The order is submitted through
 *   fields inside the rows.
 * - data-orchestra-copy="element-id"           on a button (rendered hidden, shown by this script):
 *   copies the value (or the text) of that element to the clipboard; the button's label becomes
 *   data-orchestra-copied="Copied" for a few seconds
 * - Unsaved changes (since 0.19): leaving a page after changing a form that posts to admin-post.php
 *   (typing, choosing, adding, removing or moving rows) asks first, until the form is submitted.
 *   Scripts that change a form without input events dispatch an "orchestra:change" event (bubbling)
 *   on it or one of its fields. data-orchestra-no-unsaved on a form or field leaves it out.
 * - Forms posting to admin-post.php return to the same scroll position (not in wp-admin): the position is kept in
 *   sessionStorage on submit and restored if the next page is the same page (same path, all of its
 *   query arguments) within a minute. Notices of that return ([data-orchestra-returned]) then float
 *   at the bottom of the screen; success notices disappear after a few seconds, others stay until
 *   closed. Scripts that submit a form themselves use form.requestSubmit(), so this still works.
 */
(function () {
    'use strict';

    var SCROLL_KEY = 'orchestra-scroll';
    var SCROLL_MAX_AGE = 60000;
    var SUCCESS_SECONDS = 5;

    function readScroll() {
        try {
            var stored = window.sessionStorage.getItem(SCROLL_KEY);
            window.sessionStorage.removeItem(SCROLL_KEY);
            return stored ? JSON.parse(stored) : null;
        } catch (error) {
            return null;
        }
    }

    // The same page: same path, and every query argument of the old page is still there.
    function isSamePage(stored) {
        if (!stored || typeof stored.y !== 'number' || Date.now() - stored.at > SCROLL_MAX_AGE
            || stored.path !== window.location.pathname) {
            return false;
        }
        var current = new URLSearchParams(window.location.search);
        var previous = new URLSearchParams(stored.query);
        var same = true;
        previous.forEach(function (value, name) {
            if (current.getAll(name).indexOf(value) === -1) {
                same = false;
            }
        });

        return same;
    }

    function floatNotices() {
        var notices = document.querySelectorAll('.orchestra-notice[data-orchestra-returned]');
        if (notices.length === 0) {
            return;
        }
        var container = document.createElement('div');
        container.className = 'orchestra-notices--floating';
        (notices[0].closest('.orchestra') || document.body).appendChild(container);
        Array.prototype.forEach.call(notices, function (notice) {
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'orchestra-notice__close';
            close.setAttribute('aria-label', notice.getAttribute('data-orchestra-close') || 'Close');
            close.textContent = '\u00d7';
            close.addEventListener('click', function () {
                notice.remove();
            });
            notice.appendChild(close);
            container.appendChild(notice);
            if (notice.classList.contains('orchestra-notice--success')) {
                window.setTimeout(function () {
                    notice.remove();
                }, SUCCESS_SECONDS * 1000);
            }
        });
    }

    // wp-admin shows its notices at the top of the screen, so its pages open at the top.
    var returned = readScroll();
    if (!document.body.classList.contains('wp-admin') && isSamePage(returned)) {
        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
        }
        floatNotices();
        var restore = function () {
            window.scrollTo(0, returned.y);
        };
        restore();
        // Images and fonts that load later can still change the height of the page.
        window.addEventListener('load', restore);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var submitter = event.submitter;
        var buttonMessage = submitter ? submitter.getAttribute('data-orchestra-confirm') : null;
        if (buttonMessage && !window.confirm(buttonMessage)) {
            event.preventDefault();
            return;
        }

        var message = form.getAttribute('data-orchestra-confirm');
        if (message) {
            var fieldName = form.getAttribute('data-orchestra-confirm-field');
            var field = fieldName ? form.elements.namedItem(fieldName) : null;
            var skip = field && field.value === form.getAttribute('data-orchestra-confirm-unless');
            if (!skip && !window.confirm(message)) {
                event.preventDefault();
                return;
            }
        }

        var moveMessage = form.getAttribute('data-orchestra-move-confirm');
        if (moveMessage) {
            var select = form.querySelector('select[multiple]');
            var moving = (select && Array.prototype.some.call(select.selectedOptions, function (option) {
                return option.hasAttribute('data-current');
            })) || form.querySelector('input[type="checkbox"][data-current]:checked') !== null;
            if (moving) {
                if (!window.confirm(moveMessage)) {
                    event.preventDefault();
                    return;
                }
                var move = form.elements.namedItem('move');
                if (move) {
                    move.value = '1';
                }
            }
        }
    });

    // Registered after the confirmations, so a cancelled submit is not remembered.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        // getAttribute(), as form.action is the hidden field "action" of an Orchestra form.
        if (event.defaultPrevented || !(form instanceof HTMLFormElement)
            || (form.getAttribute('action') || '').indexOf('admin-post.php') === -1) {
            return;
        }
        try {
            window.sessionStorage.setItem(SCROLL_KEY, JSON.stringify({
                path: window.location.pathname,
                query: window.location.search,
                y: window.scrollY,
                at: Date.now()
            }));
        } catch (error) {
            // Without storage the page opens at the top, as without JavaScript.
        }
    });

    // Switches ---------------------------------------------------------------------------------

    function applySwitch(select) {
        var row = select.closest('[data-orchestra-row], tr');
        if (!row) {
            return;
        }
        row.querySelectorAll('[data-orchestra-show-for]').forEach(function (element) {
            var values = element.getAttribute('data-orchestra-show-for').split(/\s+/);
            element.hidden = values.indexOf(select.value) === -1;
        });
    }

    function applySwitches(root) {
        root.querySelectorAll('select[data-orchestra-switch]').forEach(applySwitch);
    }

    document.addEventListener('change', function (event) {
        if (event.target instanceof HTMLSelectElement && event.target.hasAttribute('data-orchestra-switch')) {
            applySwitch(event.target);
        }
    });
    applySwitches(document);

    document.addEventListener('click', function (event) {
        var remove = event.target instanceof Element ? event.target.closest('[data-orchestra-remove-row]') : null;
        if (remove) {
            event.preventDefault();
            var removed = remove.closest('[data-orchestra-row], tr');
            if (removed) {
                var parent = removed.parentElement;
                markChanged(remove);
                removed.remove();
                if (parent && parent.hasAttribute('data-orchestra-sortable')) {
                    updateMoveButtons(parent);
                }
            }
            return;
        }

        var button = event.target instanceof Element ? event.target.closest('[data-orchestra-add-row]') : null;
        if (!button) {
            return;
        }
        event.preventDefault();

        var template = document.getElementById(button.getAttribute('data-orchestra-add-row'));
        var target = document.getElementById(button.getAttribute('data-orchestra-target'));
        if (!(template instanceof HTMLTemplateElement) || !target) {
            return;
        }

        var index = String(Date.now()) + String(Math.floor(Math.random() * 1000));
        // Parsed in a <template>, because a <div> would drop table rows.
        var wrapper = document.createElement('template');
        wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, index);
        while (wrapper.content.firstElementChild) {
            var added = target.appendChild(wrapper.content.firstElementChild);
            applySwitches(added);
            if (added.matches('select[data-orchestra-switch]')) {
                applySwitch(added);
            }
        }
        markChanged(target);
        if (target.hasAttribute('data-orchestra-sortable')) {
            updateMoveButtons(target);
        }
        var input = target.lastElementChild && target.lastElementChild.querySelector('input, select, textarea');
        if (input) {
            input.focus();
        }
    });

    // Copying --------------------------------------------------------------------------------

    var COPIED_SECONDS = 3;

    function copyText(source) {
        var text = 'value' in source ? source.value : source.textContent;
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        // Without HTTPS there is no clipboard API; selecting and copying still works.
        if ('select' in source) {
            source.select();
        }
        return document.execCommand('copy') ? Promise.resolve() : Promise.reject(new Error('copy'));
    }

    document.querySelectorAll('[data-orchestra-copy][hidden]').forEach(function (button) {
        button.hidden = false;
    });

    document.addEventListener('click', function (event) {
        var button = event.target instanceof Element ? event.target.closest('[data-orchestra-copy]') : null;
        var source = button ? document.getElementById(button.getAttribute('data-orchestra-copy')) : null;
        if (!source) {
            return;
        }
        event.preventDefault();
        copyText(source).then(function () {
            var label = button.getAttribute('data-orchestra-copied');
            if (!label || button.hasAttribute('data-orchestra-label')) {
                return;
            }
            button.setAttribute('data-orchestra-label', button.textContent);
            button.textContent = label;
            window.setTimeout(function () {
                button.textContent = button.getAttribute('data-orchestra-label');
                button.removeAttribute('data-orchestra-label');
            }, COPIED_SECONDS * 1000);
        }, function () {
            if ('select' in source) {
                source.focus();
                source.select();
            }
        });
    });

    // Sorting ----------------------------------------------------------------------------------

    function sortableRow(element) {
        var container = element.closest('[data-orchestra-sortable]');
        var row = element;
        while (row && row.parentElement !== container) {
            row = row.parentElement;
        }
        return container && row ? {container: container, row: row} : null;
    }

    // The first row cannot move up, the last one not down.
    function updateMoveButtons(container) {
        var rows = container.children;
        Array.prototype.forEach.call(rows, function (row, index) {
            row.querySelectorAll('[data-orchestra-move]').forEach(function (button) {
                if (sortableRow(button).row !== row) {
                    return;
                }
                var up = button.getAttribute('data-orchestra-move') === 'up';
                button.disabled = up ? index === 0 : index === rows.length - 1;
            });
        });
    }

    function updateAllMoveButtons() {
        document.querySelectorAll('[data-orchestra-sortable]').forEach(updateMoveButtons);
    }

    document.addEventListener('click', function (event) {
        var button = event.target instanceof Element ? event.target.closest('[data-orchestra-move]') : null;
        var found = button ? sortableRow(button) : null;
        if (!found) {
            return;
        }
        event.preventDefault();
        var up = button.getAttribute('data-orchestra-move') === 'up';
        if (up && found.row.previousElementSibling) {
            found.container.insertBefore(found.row, found.row.previousElementSibling);
        } else if (!up && found.row.nextElementSibling) {
            found.container.insertBefore(found.row.nextElementSibling, found.row);
        } else {
            return;
        }
        updateMoveButtons(found.container);
        markChanged(found.container);
        // At the top or bottom the button is disabled now; the other one keeps the focus.
        var other = found.row.querySelector('[data-orchestra-move="' + (up ? 'down' : 'up') + '"]');
        (button.disabled && other ? other : button).focus();
    });

    updateAllMoveButtons();

    // Unsaved changes --------------------------------------------------------------------------

    // Orchestra forms that save something: they post to admin-post.php.
    function isTrackedForm(form) {
        return form instanceof HTMLFormElement && !form.hasAttribute('data-orchestra-no-unsaved')
            && (form.getAttribute('method') || '').toLowerCase() === 'post'
            && (form.getAttribute('action') || '').indexOf('admin-post.php') !== -1;
    }

    function markChanged(element) {
        var form = element instanceof HTMLFormElement ? element
            : (element.form || (element.closest ? element.closest('form') : null));
        if (isTrackedForm(form)) {
            form.setAttribute('data-orchestra-changed', '');
        }
    }

    ['input', 'change', 'orchestra:change'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            if (event.target instanceof Element && !event.target.closest('[data-orchestra-no-unsaved]')) {
                markChanged(event.target);
            }
        });
    });

    // Registered after the confirmations, so a cancelled submit keeps the changes marked.
    document.addEventListener('submit', function (event) {
        if (!event.defaultPrevented && event.target instanceof HTMLFormElement) {
            event.target.removeAttribute('data-orchestra-changed');
        }
    });

    window.addEventListener('beforeunload', function (event) {
        if (document.querySelector('form[data-orchestra-changed]')) {
            event.preventDefault();
            // Older browsers only ask if returnValue is set; the text is the browser's own.
            event.returnValue = '';
        }
    });
}());
