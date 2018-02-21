/**
 * @author  MacFJA
 * @license MIT
 */
var autoComplete = require('autocomplete.js');

var completion = {
    /** @type {Object} */
    _values: {},
    /**
     * Add the list of possible value for a key
     * @param {string} key
     * @param {string[]} values
     */
    addCompletion: function (key, values) {
        var final = [];
        for (var index = 0; index < values.length; index++) {
            final.push({value: values[index]});
        }
        this._values[key] = final
    },
    /**
     * Get all values that match.
     * The result is a list of object (keys are: label, value)
     *
     * @param {string} key
     * @param {string} term
     * @returns {Array}
     * @private
     */
    _getValue: function (key, term) {
        var allValues = this._values[key] || [];
        var results = [];

        for (var index = 0; index < allValues.length; index++) {
            var value = allValues[index];
            if (value.value.toLowerCase().indexOf(term.toLowerCase()) > -1) {
                value['label'] = this._highlight(value.value, term);
                results.push(value);
            }
        }

        return results;
    },
    /**
     * Highlight the char sequence that have been found (surround by '<strong>')
     * @param {string} source
     * @param {string} term
     * @returns {string}
     * @private
     */
    _highlight: function (source, term) {
        var start = 0;
        var pos;
        while ((pos = source.toLowerCase().indexOf(term.toLowerCase(), start)) > -1) {
            start = pos + 1;

            source = source.substring(0, pos) + '<strong>' + source.substr(pos, term.length) + '</strong>' + source.substring(pos + term.length);
            start = pos + ('<strong>' + term + '</strong>').length
        }

        return source;
    },
    /**
     * Search for all input that have a "data-completion" attribute, and attache to them an auto-complete object
     */
    initInputs: function () {
        var inputs = document.querySelectorAll('input[data-completion]');
        for (var index = 0; index < inputs.length; index++) {
            var input = inputs.item(index);
            this.initInput(input)
        }
    },
    /**
     * Attach to an input an auto-complete object
     * @param {HTMLInputElement} input
     */
    initInput: function (input) {
        autoComplete(input, {hint: true, minLength: 2}, [{
            source: function (term, callback) {
                callback(this._getValue(input.getAttribute('data-completion'), term))
            }.bind(this),
            displayKey: 'value',
            templates: {
                suggestion: function (suggestion) {
                    return suggestion.label;
                }
            }
        }])
    }
};

window.completion = completion;
module.exports = completion;