/**
 * @author  MacFJA
 * @license MIT
 */
var pure = require('pure');
pure = pure.$p;

var trans = {
    _labels: {},
    /**
     * Add a translation
     * @param {string} code The translation identifier
     * @param {string} text The translation value
     */
    add: function (code, text) {
        this._labels[code] = text;
    },
    /**
     * Set the translations
     * @param {Object[]} labels
     */
    setLabels: function (labels) {
        this._labels = labels;
    },
    /**
     * Get a translated text
     * @param {string} code     The translation identifier
     * @param {Object} [params] The list of variables to inject
     * @returns {string}
     */
    get: function (code, params) {
        params = params || {};
        var text = this._labels[code];
        if (text == undefined) {
            return code;
        }
        for (var key in params) {
            text = text.split(key).join(params[key]);
        }
        return text;
    },
    /**
     * Get a translated text of a complex translation
     * @param {string} code      The translation identifier
     * @param {Object} params    The list of variables to inject
     * @param {Object} directive The rule for injecting variable (same as the template)
     * @returns {string}
     */
    complex: function (code, params, directive) {
        var text = this._labels[code];
        if (text == undefined) {
            return code;
        }

        var fake = document.createElement('span');
        fake.innerHTML = text;
        return pure(fake).render(params, directive).innerHTML;
    }
};

window.trans = trans;
module.exports = trans;