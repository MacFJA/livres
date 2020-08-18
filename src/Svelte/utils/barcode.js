import JsBarcode from "jsbarcode"

/**
 * @type {function} - Init the barcode inside a SVG
 * @param {SVGElement} svg - The SVG element to inject the barcode
 * @param {string} code - The value of the EAN13 barcode
 */
export const barcode = (svg, code) => {
    try {
        JsBarcode(svg, code, {
            format: "ean13",
            displayValue: false,
            height: 10,
            width: 1,
            margin: 0,
            flat: true,
            background: "transparent",
        })
    } catch (e) {
        svg.style.display = "none"
    }
}

/**
 * @type {function} - Init the barcode inside a SVG
 * @param {SVGElement} svg - The SVG element to inject the barcode
 * @param {string} code - The value of the EAN13 barcode
 */
export const largeBarcode = (svg, code) => {
    try {
        JsBarcode(svg, code, {
            format: "ean13",
            displayValue: false,
            background: "transparent",
        })
    } catch (e) {
        svg.style.display = "none"
    }
}