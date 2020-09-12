import { _ } from "svelte-intl"
import { get } from "svelte/store"

import {headerRawCover} from "~/utils/headers"

/**
 * Book datatype
 * @typedef {Object} Book
 * @property {Number} id - The book internal id
 * @property {string} title - Book title
 * @property {string} cover - Partial cover name
 * @property {string} coverFull - Full cover url
 * @property {string} isbn - Book ISBN
 * @property {string} iri - Book API identifier
 * @property {Array<BookDetail>} details - List of details of the book
 */
/**
 * Book detail item datatype
 * @typedef {Object} BookDetail
 * @property {string} key - Detail key
 * @property {string} label - Detail label
 * @property {string} type - Detail type
 * @property {mixin} value - Detail value
 */

/**
 * @type {function} - Load a book from its internal id
 * @param {Number} bookId - The internal id of the book
 * @param {boolean} [rawCover=false] - Should get raw cover
 * @return {Promise<Book>}
 */
export const loadBookFromId = (bookId, rawCover) => {
    let headers = new Headers()
    if (rawCover === true) {
        headers = headerRawCover(headers)
    }
    return fetch(window.app.url.book.replace("__placeholder", bookId), {headers})
        .then((response) => response.json())
        .then((responseJson) => {
            let book = {}
            book.id = responseJson.id
            book.title = responseJson.title
            book.cover = responseJson.cover
            book.isbn = responseJson.isbn
            book.details = responseJson.details
            book.iri = responseJson["@id"]
            return prepareCover(book)
        })
}
/**
 * @type {function} - Load (create) a book from its data
 * @param {Number} id - The book internal id
 * @param {string} title - Book title
 * @param {string} cover - Partial cover name
 * @param {string} isbn - Book ISBN
 * @param {Array<BookDetail>} details - List of details of the book
 * @param {string} iri - Book API identifier
 * @return {Promise<Book>}
 */
export const loadBookFromParams = (id, title, cover, isbn, details, iri) => {
    return Promise.resolve(prepareCover({
        id,
        title,
        cover,
        isbn,
        details,
        iri
    }))
}

/**
 * @type {function} - Load (create) a book from its data or its id
 * @param {Number|null} id - The book internal id
 * @param {string} title - Book title
 * @param {string} cover - Partial cover name
 * @param {string|null} isbn - Book ISBN
 * @param {Array<BookDetail>} details - List of details of the book
 * @param {string|null} iri - Book API identifier
 * @return {Promise<Book>}
 */
export const loadBookFrom = (id, title, cover, isbn, details, iri) => {
    // Try from params first
    if (id !== null && title !== "" && cover !== "" && isbn !== null && iri !== null) {
        return loadBookFromParams(id, title, cover, isbn, details, iri)
    } else if (id !== null) {
        return loadBookFromId(id)
    } else {
        return Promise.reject(get(_)("book.loading.error"))
    }
}

/**
 * @type {function} - Add the full cover to the book
 * @param {Book} book - The book to work on
 * @return {Book}
 */
const prepareCover = book => {
    book.coverFull = window.coverFull.replace(
        "placeholder.jpg",
        book.cover
    )
    return book
}