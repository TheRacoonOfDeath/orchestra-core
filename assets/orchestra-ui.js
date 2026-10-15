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
 */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
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
        var wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, index);
        while (wrapper.firstElementChild) {
            target.appendChild(wrapper.firstElementChild);
        }
        var input = target.lastElementChild && target.lastElementChild.querySelector('input, select, textarea');
        if (input) {
            input.focus();
        }
    });
}());
