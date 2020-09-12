import { _ } from "svelte-intl"
import { get } from "svelte/store"

/**
 * @typedef {Object} DataLine
 * @property {string} key - The data identifier
 * @property {string} fusion - How the data should be updated
 * @property {string} type - The data type
 * @property {string} label - The display name of the data
 * @property {mixin} value - The data
 */

/**
 * @type {Array<DataLine>}
 */
export const defaultItems = () => [
    {
        key: "title",
        fusion: "replace",
        type: "text",
        label: get(_)("book.field.title"),
        value: "",
    },
    {
        key: "sortTitle",
        fusion: "replace",
        type: "text",
        label: get(_)("book.field.sort_title"),
        value: "",
    },
    {
        key: "isbn",
        fusion: "replace",
        type: "barcode",
        label: get(_)("book.field.isbn"),
        value: "",
    },
    {
        key: "publicationDate",
        fusion: "replace",
        type: "date",
        label: get(_)("book.field.publication_date"),
        value: new Date(),
    },
    {
        key: "pages",
        fusion: "replace",
        type: "number",
        label: get(_)("book.field.pages"),
        value: 0,
    },
    {
        key: "series",
        fusion: "concat",
        type: "text",
        label: get(_)("book.field.series"),
        value: "",
    },
    {
        key: "keywords",
        fusion: "push",
        type: "array",
        label: get(_)("book.field.keywords"),
        value: [],
    },
    {
        key: "genres",
        fusion: "push",
        type: "array",
        label: get(_)("book.field.genres"),
        value: [],
    },
    {
        key: "format",
        fusion: "concat",
        type: "text",
        label: get(_)("book.field.format"),
        value: "",
    },
    {
        key: "dimension",
        fusion: "concat",
        type: "text",
        label: get(_)("book.field.dimension"),
        value: "",
    },
    {
        key: "illustrators",
        fusion: "push",
        type: "array",
        label: get(_)("book.field.illustrators"),
        value: [],
    },
    {
        key: "authors",
        fusion: "push",
        type: "array",
        label: get(_)("book.field.authors"),
        value: [],
    },
    {
        key: "cover",
        fusion: "replace",
        type: "image",
        label: get(_)("book.field.cover"),
        value: "",
    },
    {
        key: "owner",
        fusion: "replace",
        type: "text",
        label: get(_)("book.field.owner"),
        value: ""
    }
]

export const getDataLine = (key, label, value) => {
    let matches = defaultItems().filter(item => {
        return item.key === key || item.label === label
    })
    if (matches.length === 0) {
        matches = [{
            key: key,
            fusion: "push",
            type: "dynamic",
            label: key,
            value: []
        }]
    }
    let match = matches.shift()

    match.value = value

    return match
}