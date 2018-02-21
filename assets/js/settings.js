/**
 * @author  MacFJA
 * @license MIT
 */
var ajax = require('@fdaciuk/ajax');
var modal = require('msg-modal/dist/msg.js');

var settings = {
    /**
     * Initialize the object
     */
    init: function () {
        this._addEventListener()
    },
    /**
     * Add an event listener on all "Active" checkbox
     * @private
     */
    _addEventListener: function () {
        var fieldsets = document.querySelectorAll('fieldset');

        for (var index = 0; index < fieldsets.length; index++) {
            var fieldset = fieldsets.item(index);

            var inputs = fieldset.querySelectorAll('input[type=text]');
            var active = fieldset.querySelector('input[type=checkbox]');

            this._updateRequired(active, inputs);

            if (inputs.length > 0) {
                active.addEventListener('change', function () {
                    this.self._updateRequired(this.active, this.inputs);
                }.bind({self: this, active: active, inputs: inputs}))
            }
        }
    },
    /**
     * Call the purge url
     * @param {string} url
     * @param {HTMLButtonElement} button
     */
    purge: function (url, button) {
        button.disabled = true;
        button.querySelector('i').className += ' fa-spin';

        ajax({
            url: url,
            method: 'get'
        }).then(function (response) {
            if (response.status == 'OK') {
                modal.factory({
                    "persistent": false,
                    "class": "green",
                    "autoclose": true,
                    "enable_progressbar": true,
                    "window_x": "none"
                }).show(response.text);
            } else {
                modal.factory({
                    "persistent": false,
                    "class": "red",
                    "autoclose": true,
                    "enable_progressbar": true,
                    "window_x": "none"
                }).show("An error occurs");
            }
        }).catch(function (response) {
            modal.factory({
                "persistent": false,
                "class": "red",
                "autoclose": true,
                "enable_progressbar": true,
                "window_x": "none"
            }).show("An error occurs: " + response);
        }).always(function () {
            button.querySelector('i').className = button.querySelector('i').className.replace(' fa-spin', '');
            button.disabled = false;
        });
    },
    /**
     * Set the value of the require flag base on the "Active" checkbox status
     * @param {HTMLInputElement} active
     * @param {HTMLInputElement} inputs
     * @private
     */
    _updateRequired: function (active, inputs) {
        for (var loop = 0; loop < inputs.length; loop++) {
            inputs.item(loop).required = active.checked;
        }
    }
};

window.settings = settings;
module.exports = settings;