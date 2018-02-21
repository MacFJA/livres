/**
 * @author  MacFJA
 * @license MIT
 */
var Quagga = require('quagga');
var modal = require('msg-modal/dist/msg.js');
var template = require('./template');
var search = require('./search');

var scanner = {
    /**
     * @internal
     * @type {Object} The confirmation modal object
     */
    _confirmation: modal.factory({
        class: 'blue',
        persistent: false,
        lock: true,
    }),
    /**
     * @internal
     * @type {function} The pre-compiled template for a found ISBN
     */
    _foundTemplate: null,
    /**
     *
     * @param {HTMLInputElement} input     The HTMLInputElement of the file input
     * @param {string}           successQS The (DOM) query selector of Element that contain the success message
     * @param {string}           failQS    The (DOM) query selector of Element that contain the fail message
     * @param {string}           inputQS   The (DOM) query selector of HTMLInputElement of the input used in search form
     * @param {string}           displayQS The (DOM) query selector of Element that will contain the found ISBN
     * @private
     */
    _scanFileInput: function (input, successQS, failQS, inputQS, displayQS) {
        var files = input.files;
        if (files.length > 0) {
            var fileToLoad = files[0];

            var fileReader = new FileReader();

            fileReader.onload = function (fileLoadedEvent) {
                var success = document.querySelector(successQS),
                    fail = document.querySelector(failQS),
                    input = document.querySelector(inputQS),
                    display = document.querySelector(displayQS);

                success.setAttribute('hidden', true);
                fail.setAttribute('hidden', true);
                scanner._decodeImageUrl(fileLoadedEvent.target.result, function (result) {
                    if (result && result.codeResult) {
                        input.value = result.codeResult.code;
                        display.textContent = result.codeResult.code;
                        success.removeAttribute('hidden');
                    } else {
                        fail.removeAttribute('hidden');
                    }
                });
            };

            fileReader.readAsDataURL(fileToLoad);
        }
    },
    /**
     * Initialise the file input (attach an observe for automatic file decoding)
     *
     * @param {string} fileQS    The (DOM) query selector of HTMLInputElement of the file input
     * @param {string} successQS The (DOM) query selector of Element that contain the success message
     * @param {string} failQS    The (DOM) query selector of Element that contain the fail message
     * @param {string} inputQS   The (DOM) query selector of HTMLInputElement of input used in search form
     * @param {string} displayQS The (DOM) query selector of Element that will contain the found ISBN
     */
    initFile: function (fileQS, successQS, failQS, inputQS, displayQS) {
        var element = document.querySelector(fileQS);
        element.addEventListener('change', function (event) {
            scanner._scanFileInput(event.target, successQS, failQS, inputQS, displayQS);
        });
    },

    /**
     * Decode a file
     * @param {*} data
     * @param {function} callback
     * @internal
     */
    _decodeImageUrl: function (data, callback) {
        Quagga.decodeSingle({
            decoder: {
                readers: ["ean_reader"] // List of active readers
            },
            locate: true, // try to locate the barcode in the image
            // You can set the path to the image in your server
            // or using it's base64 data URI representation data:image/jpg;base64, + data
            src: data
        }, callback);
    },

    /**
     * Initialize the video stream
     * @param {string} canvasQS The (DOM) query selector of the Element that will contain video stream
     */
    initStream: function (canvasQS) {
        if (this._foundTemplate == null) {
            this._foundTemplate = template.preRender('#templates .tpl-modal-found', {
                'div strong': 'isbn',
                'button@data-isbn': 'isbn'
            });
        }
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                // Or '#yourElement' (optional)
                target: document.querySelector(canvasQS)
            },
            frequency: 2,
            decoder: {
                readers: ["ean_reader"],
                debug: {
                    showFoundPatches: true,
                }
            },
            locator: {
                halfSample: true,
                patchSize: "large", // x-small, small, medium, large, x-large
                debug: {
                    showFoundPatches: true,
                }
            },
            numOfWorkers: 1,
            locate: true
        }, function (err) {
            if (err) {
                console.log(err);
                return
            }
            console.log("Initialization finished. Ready to start");
            scanner._confirmation.set_title('Found an ISBN/EAN');

            Quagga.start();
        });
        Quagga.onDetected(function (result) {
            Quagga.pause();
            var code = result.codeResult.code;
            scanner._confirmation.show(scanner._foundTemplate({'isbn': code}));
            scanner._confirmation.options.before_close = function () {
                if (!scanner._modalRequestSearch) {
                    Quagga.start();
                }
                return true;
            }
        });
        Quagga.onProcessed(function (result) {
            var drawingCtx = Quagga.canvas.ctx.overlay,
                drawingCanvas = Quagga.canvas.dom.overlay;

            if (result) {
                if (result.boxes) {
                    drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                    result.boxes.filter(function (box) {
                        return box !== result.box;
                    }).forEach(function (box) {
                        Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                    });
                }

                if (result.box) {
                    Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
                }

                if (result.codeResult && result.codeResult.code) {
                    Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
                }
            }
        });
    },
    /**
     * @internal
     * @type {object} The modal popin object for validating a video found ISBN
     */
    _modalRequestSearch: false,
    /**
     * Close the modal and start search with providers
     * @param {string} code The found ISBN
     */
    modalSearch: function (code) {
        this._modalRequestSearch = true;
        this._confirmation.close();
        this._confirmation.destroy();
        Quagga.stop();
        search.start(code);
    },
    /**
     * Close the modal and continue scanning the video stream
     */
    modalCancel: function () {
        this._confirmation.close();
    },
    /**
     * Start scanning the video stream
     *
     * @param {string} startBtnQS The (DOM) query selector of the Element of the start button
     * @param {string} stopBtnQS  The (DOM) query selector of the Element of the stop button
     * @param {string} canvasQS   The (DOM) query selector of the Element that contains the video
     */
    startLive: function (startBtnQS, stopBtnQS, canvasQS) {
        document.querySelector(startBtnQS).setAttribute('hidden', true);
        document.querySelector(stopBtnQS).removeAttribute('hidden');
        this.initStream(canvasQS);

    },
    /**
     * Stop the scanning of the video
     *
     * @param {string} startBtnQS The (DOM) query selector of the Element of the start button
     * @param {string} stopBtnQS  The (DOM) query selector of the Element of the stop button
     */
    stopLive: function (startBtnQS, stopBtnQS) {
        document.querySelector(stopBtnQS).setAttribute('hidden', true);
        document.querySelector(startBtnQS).removeAttribute('hidden');
        Quagga.stop();
    }
};

window.scanner = scanner;
module.exports = scanner;