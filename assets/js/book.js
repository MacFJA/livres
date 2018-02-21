/**
 * @author  MacFJA
 * @license MIT
 */
var ajax = require('@fdaciuk/ajax');
var modal = require('msg-modal/dist/msg.js');
var trans = require('./translate');

var book = {
    _filterModel: null,
    /**
     * Serialize a list of input into a JSON string
     *
     * @param {HTMLInputElement[]} elements
     * @param {boolean} [valueIsJson=false]
     */
    serializedInputs: function (elements, valueIsJson) {
        var obj = {};
        if (valueIsJson == undefined) {
            valueIsJson = false;
        }
        for (var i = 0; i < elements.length; ++i) {
            var element = elements[i];
            var name = element.name;
            var id = element.id;
            var value = element.value;

            if (name && value.length > 0) {
                obj[name] = valueIsJson ? JSON.parse(value) : value;
            } else if (id && value.length > 0) {
                obj[id] = valueIsJson ? JSON.parse(value) : value;
            }
        }
        return JSON.stringify(obj);
    },
    /**
     * Open the popin window to add a movement on a book
     * @param {string} url
     * @returns {promise}
     */
    addMovement: function (url) {
        return ajax({url: url, method: 'get'}).then(function (response) {
            modal.factory({"persistent": false}).show(response);
            completion.initInput(document.getElementById('form_person'))
        })
    },
    /**
     * Open the filter popin
     */
    showFilter: function () {
        if (!this._filterModel) {
            this._filterModel = modal.factory({
                "class": "black"    ,
                "window_max_width": "90vw",
                "window_min_width": "90vw",
                "enable_titlebar": true
            });
            this._filterModel.set_title(trans.get('Search'));
            this._filterModel.set(document.getElementById('book-list-filter'))
        }
        this._filterModel.show()
    },
    /**
     * Send an Base64 and JSON encoded form (the encoded string is append to the form action)
     * @param {HTMLFormElement} form
     * @param {string}          listUrl The url on the unfiltered list
     * @returns {boolean}
     */
    sendFilter: function (form, listUrl) {
        var formData = btoa(book.serializedInputs(form.elements));
        if (formData == btoa('{}')) {
            return this.displayList(listUrl)
        }

        window.location = form.action.substr(0, form.action.length) + formData;
        return false
    },

    displayList: function (listUrl) {
        window.location = listUrl;
        return false
    }
};

window.book = book;
module.exports = book;