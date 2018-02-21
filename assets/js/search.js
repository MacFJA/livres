/**
 * @author  MacFJA
 * @license MIT
 */
var ajax = require('@fdaciuk/ajax');
var modal = require('msg-modal/dist/msg.js');
var merge = require('array.merge');
var trans = require('./translate');
var template = require('./template');

var search = window.search || {
        /**
         * @internal
         * @type {string[]}
         */
        _providers: [],
        /**
         * @internal
         * @type {string[]} List of provider to call
         */
        _providerQueue: [],
        /**
         * @internal
         * @type {string}
         */
        _baseUrl: '',
        /**
         * @internal
         * @type {Object} Modal object
         */
        _searchModal: modal.factory({
            "class": "black",
            "persistent": false,
            "lock": true,
            "closeable": false,
            "window_x": "none"
        }),
        /**
         * @internal
         * @type {int} The "increment" for field identifier (to avoid id collision)
         */
        _fieldIndex: 0,
        /**
         * @internal
         * @type {function} The precompiled template for results
         */
        _resultRenderer: template.preRender('#result-template .app-block', {
            '.app-block-title strong': 'providerLabel',
            'tbody tr': {
                'field <- fields': {
                    '.tpl-field': 'field.label',
                    '.tpl-value': function (field) {
                        if (this.type && this.type == 'image') {
                            return '<img src="' + this.value + '" class="pure-img" />';
                        }
                        if (this.value.date) {
                            return this.value.date;
                        }
                        return this.value;
                    },
                    '.tpl-value@data-json': function (field) {
                        return JSON.stringify(this.value)
                    },
                    '.tpl-value@id': function (field) {
                        return this.name + '-' + search._fieldIndex
                    },
                    'button@data-source': function (field) {
                        return this.name + '-' + search._fieldIndex
                    },
                    'button@data-destination': function (field) {
                        return this.name + '-final'
                    },
                    'button@data-type': 'field.type'
                }
            }
        }),
        /**
         * @internal
         * @type {function} The precompiled template for final book field
         */
        _finalFieldRenderer: template.preRender("#field-template > div", {
            'label': 'label',
            'button@data-field': function (field) {
                return this.field + '-final'
            },
            'label@for': function (field) {
                return this.field + '-final'
            },
            'input@id': function (field) {
                return this.field + '-final'
            },
            'input@name': 'field'
        }),
        /**
         * Add the provider result(s) to the result stack
         * @param {string} html
         * @private
         */
        _addResult: function (html) {
            var stack = document.getElementById('app-stacks'),
                holder = document.getElementById('tpl-result-holder');
            stack.removeAttribute('hidden');
            document.getElementById('search-no-results').setAttribute('hidden', true);
            document.getElementById('final-book-holder').removeAttribute('hidden');
            var wasEmpty = holder.innerHTML.length == 0;
            holder.innerHTML += html;
            if (wasEmpty) {
                holder.firstElementChild.removeAttribute('hidden');
            } else {
                if (!stack.classList.contains('multiple')) {
                    stack.className += ' multiple';
                }
            }
            document.getElementById('count_result').innerHTML = "" + holder.childElementCount;
        },
        /**
         * Add the fields to the book final
         * @param {Array} fields
         * @private
         */
        _addFields: function (fields) {
            var newBookFieldset = document.getElementById('final-book-fields-holder');
            for (var field in fields) {
                var label = fields[field];
                var fieldElement = document.getElementById(field + '-final');
                if (fieldElement == null) {
                    newBookFieldset.innerHTML += search._finalFieldRenderer({field: field, label: label})
                }
            }
        },
        /**
         * Call the next provider
         * @param {string} isbn The ISBN to search
         * @private
         */
        _searchNext: function (isbn) {
            if (search._providerQueue.length == 0) {
                search._searchModal.options.closeable = true;
                search._searchModal.close();
                search._end();
                return;
            }

            var provider = search._providerQueue.pop();
            var url = search._baseUrl;
            search._searchModal.set(trans.get('search_with', {'{name}': provider.label}));
            ajax({
                url: url.replace(/\-provider\-/g, provider.code).replace(/\-isbn\-/g, isbn),
                method: 'get'
            }).then(function (response) {
                for (var loop = 0; loop < response.results.length; loop++) {
                    search._addResult(search._resultRenderer(response.results[loop]));
                    search._addFields(response.allFields);
                    search._fieldIndex++;
                }
                search._searchNext(isbn);
            });
        },
        /**
         * Initialize the object
         * @param {string} baseUrl
         * @param {string[]} providers
         */
        init: function (baseUrl, providers) {
            search._baseUrl = baseUrl;
            search._providers = providers;
            search._providerQueue = providers;
        },
        /**
         * Start searching an isbn
         * @param {string} isbn
         */
        start: function (isbn) {
            search._searchModal.show(trans.get('start_searching'));
            var isbnField = document.getElementById('isbn-final');
            if (isbnField.value == '') {
                isbnField.value = isbn;
            }
            search._searchNext(isbn)
        },
        /**
         * End of the search: show no result if nothing have been found
         * @private
         */
        _end: function () {
            var holder = document.getElementById('tpl-result-holder');
            if (holder.childElementCount == 0) {
                document.getElementById('search-no-results').removeAttribute('hidden');
                document.getElementById('final-book-holder').removeAttribute('hidden');
            }
        },
        /**
         * Display another providers result
         * @param {boolean} next Indicate if the previous or th enext result must be displayed
         */
        changeResult: function (next) {
            var holder = document.getElementById('tpl-result-holder');
            var results = holder.children;
            for (var index = 0; index < results.length; index++) {
                var current = results[index];
                if (!current.hasAttribute('hidden')) {
                    current.setAttribute('hidden', true);

                    if (next) {
                        if (index == results.length - 1) {
                            results[0].removeAttribute('hidden');
                        } else {
                            results[index + 1].removeAttribute('hidden');
                            return
                        }
                    } else {
                        if (index == 0) {
                            results[results.length - 1].removeAttribute('hidden');
                            return
                        } else {
                            results[index - 1].removeAttribute('hidden');
                            return
                        }
                    }
                }
            }
        },
        /**
         * @type {Object} All the functions about the final result
         */
        final: {
            /**
             * Add the provider result to the final book field
             * @param {HTMLElement} dataHolder
             * @returns {*}
             */
            appendItem: function (dataHolder) {
                var source = dataHolder.getAttribute('data-source'),
                    destination = dataHolder.getAttribute('data-destination'),
                    type = dataHolder.hasAttribute('data-type') ? dataHolder.getAttribute('data-type') : null;

                var sourceData = JSON.parse(document.getElementById(source).getAttribute('data-json'));
                var destinationData = document.getElementById(destination).value,
                    result;
                if (destinationData.length > 0) {
                    try {
                        destinationData = JSON.parse(destinationData);
                    } catch (e) {
                        // Do nothing
                    }
                } else {
                    return search.final.replaceItem(dataHolder);
                }
                if ((typeof destinationData != "object") && (typeof destinationData != "undefined")) {
                    destinationData = new Array(destinationData)
                }

                destinationData = merge(sourceData, destinationData);

                switch (type) {
                    case 'text':
                    case 'string':
                        result = destinationData.join(', ');
                        break;
                    case 'smallint':
                    case 'integer':
                        result = sourceData * 1;
                        break;
                    case 'image':
                        result = sourceData;
                        break;
                    case 'date':
                    case 'json_array':
                    case 'simple_array':
                    default:
                        result = JSON.stringify(destinationData);

                }
                document.getElementById(destination).value = result;
                document.getElementById(destination).parentElement.setAttribute('data-value', result);
            },
            /**
             * Replace the final result with the provider result
             * @param {HTMLElement} dataHolder
             */
            replaceItem: function (dataHolder) {
                var source = dataHolder.getAttribute('data-source'),
                    destination = dataHolder.getAttribute('data-destination'),
                    type = dataHolder.hasAttribute('data-type') ? dataHolder.getAttribute('data-type') : null;

                var sourceData = JSON.parse(document.getElementById(source).getAttribute('data-json')),
                    result;

                switch (type) {
                    case 'text':
                    case 'string':
                    // result = sourceData;
                    // break;
                    case 'smallint':
                    case 'integer':
                    // result = sourceData*1;
                    // break;
                    case 'image':
                    // result = sourceData;
                    // break;
                    case 'json_array':
                    case 'simple_array':
                    default:
                        result = JSON.stringify(sourceData);

                }
                document.getElementById(destination).value = result;
            },
            /**
             * Add all value that are in the provider result but not in the final result
             * @param {HTMLElement} appBlock
             */
            addMissing: function (appBlock) {
                var nodes = appBlock.querySelectorAll('button.tpl-add');

                for (var index = 0; index < nodes.length; index++) {
                    var dataHolder = nodes.item(index);
                    var destination = dataHolder.getAttribute('data-destination');

                    if (document.getElementById(destination).value == '') {
                        search.final.replaceItem(dataHolder);
                    }
                }
            },
            /**
             * Add all value of the provider result to the final result
             * @param {HTMLElement} appBlock
             */
            appendAll: function (appBlock) {
                var nodes = appBlock.querySelectorAll('button.tpl-add');

                for (var index = 0; index < nodes.length; index++) {
                    var dataHolder = nodes.item(index);
                    search.final.appendItem(dataHolder);
                }
            },
            /**
             * Replace in teh final result all value that are in the provider results
             * @param {HTMLElement} appBlock
             */
            replaceAll: function (appBlock) {
                var nodes = appBlock.querySelectorAll('button.tpl-add');

                for (var index = 0; index < nodes.length; index++) {
                    var dataHolder = nodes.item(index);
                    search.final.replaceItem(dataHolder);
                }
            }
        }
    };

if (typeof window.search == 'undefined') {
    window.search = search;
}
module.exports = search;