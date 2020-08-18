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
export const defaultItems = [
    {
        key: "title",
        fusion: "replace",
        type: "text",
        label: "Title",
        value: "",
    },
    {
        key: "sortTitle",
        fusion: "replace",
        type: "text",
        label: "Sort title",
        value: "",
    },
    {
        key: "isbn",
        fusion: "replace",
        type: "barcode",
        label: "Isbn",
        value: "",
    },
    {
        key: "publicationDate",
        fusion: "replace",
        type: "date",
        label: "Publication date",
        value: new Date(),
    },
    {
        key: "pages",
        fusion: "replace",
        type: "number",
        label: "Pages",
        value: 0,
    },
    {
        key: "series",
        fusion: "concat",
        type: "text",
        label: "Series",
        value: "",
    },
    {
        key: "keywords",
        fusion: "push",
        type: "array",
        label: "Keywords",
        value: [],
    },
    {
        key: "genres",
        fusion: "push",
        type: "array",
        label: "Genres",
        value: [],
    },
    {
        key: "format",
        fusion: "concat",
        type: "text",
        label: "Format",
        value: "",
    },
    {
        key: "dimension",
        fusion: "concat",
        type: "text",
        label: "Dimension",
        value: "",
    },
    {
        key: "illustrator",
        fusion: "push",
        type: "array",
        label: "Illustrator",
        value: [],
    },
    {
        key: "authors",
        fusion: "push",
        type: "array",
        label: "Authors",
        value: [],
    },
    {
        key: "translators",
        fusion: "push",
        type: "array",
        label: "Translators",
        value: [],
    },
    {
        key: "cover",
        fusion: "replace",
        type: "image",
        label: "Cover",
        value: "",
    },
    {
        key: "owner",
        fusion: "replace",
        type: "text",
        label: "Owner",
        value: ""
    }
]

export const getDataLine = (key, label, value) => {
    let matches = defaultItems.filter(item => {
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