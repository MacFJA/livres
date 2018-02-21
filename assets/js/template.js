/**
 * @author  MacFJA
 * @license MIT
 */
var pure = require('pure');
pure = pure.$p;

var template = {
    /**
     * @internal
     * @type {Object}
     */
    _renderCache: {},
    /**
     *
     * @param {string|HTMLElement} querySelector
     * @param {*} data
     * @param {*} directive
     * @returns {string}
     */
    render: function (querySelector, data, directive) {
        return pure(querySelector).render(data, directive);
    },
    /**
     *
     * @param {string|HTMLElement} querySelector
     * @param {*} directive
     * @param {string} [name]
     * @returns {function}
     */
    preRender: function (querySelector, directive, name) {
        var cacheKey = name || JSON.stringify({'targer': querySelector, 'directive': directive});
        if (!template._renderCache[cacheKey]) {
            template._renderCache[cacheKey] = pure(querySelector).compile(directive);
        }
        return template._renderCache[cacheKey];
    }
};

window.template = template;
module.exports = template;