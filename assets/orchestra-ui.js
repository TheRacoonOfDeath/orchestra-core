/*
 * Orchestra UI behavior, shared by all Orchestra pages. Everything works without JavaScript as
 * well; the server checks every rule again.
 *
 * - data-orchestra-confirm="Message"            on a form: asks before submitting
 *   data-orchestra-confirm-field="status"        only ask if this field's value ...
 *   data-orchestra-confirm-unless="accepted"     ... is not this value
 * - data-orchestra-move-confirm="Message"       on a form with a <select multiple>: asks if a
 *   selected option has data-current, then sets the hidden field "move" to 1
 * - data-orchestra-add-row="template-id"        on a button: appends the <template> content to
 *   the element named in data-orchestra-target; "__INDEX__" is replaced by a new number
 * - data-orchestra-confirm="Message"            on a submit button: asks before submitting with it
 * - data-orchestra-remove-row                   on a button: removes the row (<tr> or
 *   [data-orchestra-row]) it is in
 * - data-orchestra-sortable                     on a container (e.g. <tbody>): its children can be
 *   reordered by dragging their [data-orchestra-drag-handle] (mouse and touch; the page scrolls
 *   near the window edges) or with the arrow keys while the handle has focus. The order is
 *   submitted through fields inside the rows.
 */
(function () {
    'use strict';

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
            var moving = select && Array.prototype.some.call(select.selectedOptions, function (option) {
                return option.hasAttribute('data-current');
            });
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

    document.addEventListener('click', function (event) {
        var remove = event.target instanceof Element ? event.target.closest('[data-orchestra-remove-row]') : null;
        if (remove) {
            event.preventDefault();
            var removed = remove.closest('[data-orchestra-row], tr');
            if (removed) {
                removed.remove();
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
            target.appendChild(wrapper.content.firstElementChild);
        }
        var input = target.lastElementChild && target.lastElementChild.querySelector('input, select, textarea');
        if (input) {
            input.focus();
        }
    });

    // Sorting ----------------------------------------------------------------------------------

    function sortableRow(handle) {
        var container = handle.closest('[data-orchestra-sortable]');
        var row = handle;
        while (row && row.parentElement !== container) {
            row = row.parentElement;
        }
        return container && row ? {container: container, row: row} : null;
    }

    var drag = null;
    var EDGE = 60;

    function placeDraggedRow(clientY) {
        var siblings = Array.prototype.filter.call(drag.container.children, function (child) {
            return child !== drag.row;
        });
        var before = null;
        for (var i = 0; i < siblings.length; i++) {
            var box = siblings[i].getBoundingClientRect();
            if (clientY < box.top + box.height / 2) {
                before = siblings[i];
                break;
            }
        }
        if (before !== drag.row.nextElementSibling) {
            drag.container.insertBefore(drag.row, before);
        }
    }

    // Scrolls while the pointer is held near the top or bottom edge of the window.
    function autoScroll() {
        if (!drag) {
            return;
        }
        var step = 0;
        if (drag.y < EDGE) {
            step = -Math.ceil((EDGE - drag.y) / 3);
        } else if (drag.y > window.innerHeight - EDGE) {
            step = Math.ceil((drag.y - window.innerHeight + EDGE) / 3);
        }
        if (step !== 0) {
            window.scrollBy(0, step);
            placeDraggedRow(drag.y);
        }
        window.requestAnimationFrame(autoScroll);
    }

    document.addEventListener('pointerdown', function (event) {
        var handle = event.target instanceof Element ? event.target.closest('[data-orchestra-drag-handle]') : null;
        var found = handle && event.button === 0 ? sortableRow(handle) : null;
        if (!found) {
            return;
        }
        event.preventDefault();
        handle.setPointerCapture(event.pointerId);
        found.row.classList.add('orchestra-dragging');
        drag = {
            handle: handle,
            row: found.row,
            container: found.container,
            pointerId: event.pointerId,
            y: event.clientY
        };
        window.requestAnimationFrame(autoScroll);
    });

    document.addEventListener('pointermove', function (event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }
        event.preventDefault();
        drag.y = event.clientY;
        placeDraggedRow(event.clientY);
    });

    function endDrag(event) {
        if (!drag || event.pointerId !== drag.pointerId) {
            return;
        }
        drag.row.classList.remove('orchestra-dragging');
        drag.handle.focus();
        drag = null;
    }

    document.addEventListener('pointerup', endDrag);
    document.addEventListener('pointercancel', endDrag);

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
            return;
        }
        var handle = event.target instanceof Element ? event.target.closest('[data-orchestra-drag-handle]') : null;
        var found = handle ? sortableRow(handle) : null;
        if (!found) {
            return;
        }
        event.preventDefault();
        if (event.key === 'ArrowUp' && found.row.previousElementSibling) {
            found.container.insertBefore(found.row, found.row.previousElementSibling);
        } else if (event.key === 'ArrowDown' && found.row.nextElementSibling) {
            found.container.insertBefore(found.row.nextElementSibling, found.row);
        }
        handle.focus();
    });
}());
