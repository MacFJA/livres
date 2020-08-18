<script>
    import { onMount } from "svelte"

    import Movement from "./Book/Movement.svelte"
    import FieldRender from "./FieldRender.svelte"
    import Loader from "./Loader.svelte"
    import { close } from "./Modal.svelte"
    import Error from "./Modal/Error.svelte"

    import { loadBookFrom } from "~/Book/loadBook"
    import { largeBarcode } from "~/utils/barcode"

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

    /** @type {Number} - The book internal id */
    export let id = null

    export let
        /** @type {string} title - Book title */
        title = "",
        /** @type {string} cover - Partial cover name */
        cover = "",
        /** @type {string} isbn - Book ISBN */
        isbn = null,
        /** @type {Array<BookDetail>} details - List of details of the book */
        details = [],
        /** @type {string} iri - Book API identifier */
        iri = null

    /** @type {Promise<Book>} - The book to display */
    let bookPromise = new Promise(() => {})

    onMount(() => {
        bookPromise = loadBookFrom(id, title, cover, isbn, details, iri)
    })

    const deleteBook = () => {
        confirm("Are you sure to delete this book?") && fetch(window.app.url.bookDelete.replace("__placeholder", id), {method: "DELETE"}).then(() => close())
    }
</script>

<style lang="scss">
    article {
        display: flex;
        padding: 0;
        position: relative;
        font-family: sans-serif;
        flex-wrap: wrap;

        > aside {
            text-align: center;
            min-width: 180px;
            flex: 1;

            > img {
                max-width: calc(100% - 2em);
                height: auto;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
                margin: 1em auto;
                display: block;
            }

            svg {
                max-width: 100%;
            }
        }

        > dl {
            border: 1px solid rgba(0, 0, 0, 0.2);
            display: grid;
            grid-template-columns: 0fr 1fr;
            vertical-align: top;
            flex-grow: 1;
            height: 100%;

            dt {
                text-align: right;
                font-weight: bold;
                background: rgba(0, 0, 0, 0.15);
                white-space: nowrap;
            }

            .action {
                align-self: center;
                text-align: center;
                grid-column-end: 3;
                grid-column-start: 1;
            }

            > * {
                padding: 0.5ex;
                margin: 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.2);
                vertical-align: middle;

                &:last-of-type,
                &:last-child {
                    border-bottom: none !important;
                }
            }
        }

        > section {
            flex: auto;
        }
    }

    @media (prefers-color-scheme: dark) {
        .barcode,
        :global(.type-barcode) {
            filter: #{"invert(1)"};
        }

        article dl {
            border: 1px solid rgba(100%, 100%, 100%, 0.2);

            dt {
                background: rgba(100%, 100%, 100%, 0.15);
            }

            * {
                border-bottom: 1px solid rgba(100%, 100%, 100%, 0.2);
            }
        }
    }
</style>

{#await bookPromise}
    <Loader/>
{:then book}
    <article class="book">
        <aside>
            <img onerror="this.src = window.coverFull" src="{book.coverFull}" alt="{book.title}" />
            <svg use:largeBarcode={book.isbn} class="barcode"></svg>
        </aside>
        <dl>
            {#each book.details as { label, value, type }, index}
                <dt>{label}</dt>
                <dd>
                    <FieldRender type="{type}" value="{value}" />
                </dd>
            {:else}Loading...{/each}
            {#if window.app.roles.edit || window.app.roles.delete}
                <dt class="action">
                    {#if window.app.roles.edit}
                        <button class="color warn" on:click={() => location.hash = `#edit-book-${book.id}`}>Edit</button>
                    {/if}
                    {#if window.app.roles.delete}
                        <button class="color danger" on:click={() => deleteBook()}>Delete</button>
                    {/if}
                </dt>
            {/if}
        </dl>

        {#if book.iri !== undefined && book.iri !== null}
        <section>
            <Movement bookId="{book.id}" iriId="{book.iri}" />
        </section>
        {/if}
    </article>
{:catch reason}
    <Error title="Unable to load the book" reason="{reason}" />
{/await}