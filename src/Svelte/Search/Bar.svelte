<script>
    import JsBarcode from "jsbarcode"
    import debounce from "just-debounce"
    import { createEventDispatcher } from "svelte"
    import { derived, writable } from "svelte/store"

    import Book from "~/Book.svelte"
    import { open } from "~/Modal.svelte"

    /**
     * Facet datatype
     * @typedef {Object} Facet
     * @property {String} value
     * @property {FacetPayload} payload
     */
    /**
     * Facet payload datatype
     * @typedef {Object} FacetPayload
     * @property {String} type
     * @property {String} [full]
     * @property {String} [kind]
     * @property {Number} [bookId]
     */
    /**
     * @type {writable<Array<Facet>>} - The list of validated search facets
     */
    let facets = writable([])
    /**
     * @type {writable<String>} - The search text
     */
    let query = writable("")
    /**
     * @type {derived<String>} - The formatted search query
     * Depends on `facets` and `query` value
     */
    let searchQuery = derived([facets, query], ([$facets, $query]) =>
        ($facets
            .map(facet => {
                const isTag = facet.payload.kind === "tag"
                const rawValue = (facet.payload.full || facet.value)
                let facetQuery = "@" + facet.payload.type
                facetQuery += ":"
                if (isTag) {
                    facetQuery += "{" + rawValue + "}"
                } else if (rawValue.indexOf(" ") > -1) {
                    facetQuery += "\"" + rawValue + "\""
                } else {
                    facetQuery += rawValue
                }

                return facetQuery
            })
            .join(" ") + " " + $query
        ).trim()
    , "")
    /** @type {HTMLInputElement} - The query input element */
    let input
    /** @type {Number} - The number of asynchronous HTTP request currently in progress */
    let requestInProgress = 0

    /**
     * @type {function} - Start an asynchronous search query
     * @return {Promise<Array>}
     */
    const getSearchPreviewPromise = () => {
        if ($searchQuery.trim() === "") {
            return Promise.resolve([])
        }
        requestInProgress++
        return fetch(window.app.url.searchPreview + "?q="+($searchQuery))
            .then(response => {requestInProgress--; return response.json()})
    }
    /**
     * @type {function} - Start an asynchronous suggestion query
     * @return {Promise<Array<Facet>>}
     */
    const getSuggestionsPromise = () => {
        if ($query.trim() === "") {
            return Promise.resolve([])
        }
        requestInProgress++
        return fetch(window.app.url.suggestions + "?q="+($query.trim()))
            .then(response => {requestInProgress--; return response.json()})
    }

    /**
     * @type {derived<Promise<Array>>} - First 5 result of the search
     * Depends on `searchQuery`
     */
    let searchPreview = derived([searchQuery], () => getSearchPreviewPromise())
    /**
     * @type {derived<Promise<Array>>} - Suggestions for the current query
     * Depends on `query`
     */
    let suggestion = derived([query], () => getSuggestionsPromise())
    /**
     * @type {function} - Event dispatcher
     * @param {string} - Event name
     * @param {mixin} - Event data
     */
    const dispatch = createEventDispatcher()
    /**
     * @type {function} - Debounced search field content reader
     * @event HTMLInputElement:input
     * @param {InputEvent} event
     */
    const onInput = debounce(event => $query = event.target.value, 600, false)
    /**
     * @type {function} - Open the modal with a Book
     * @param {Number} id - The book internal id
     */
    const showBook = id => open(Book, {id})
    /**
     * @type {function} - Validate the search (dispatch event to its parent Component)
     * @emit search - with the full search query
     */
    const search = () => dispatch("search", $searchQuery)
    /**
     * @type {function} - Clear the search
     *
     * If the search query is not empty, then it will be emptied.
     * If the search query is already empty, facets are removed.
     */
    const clearSearch = () => {
        if ($query !== "") {
            input.value = ""
            $query = ""
            return
        }
        $facets = []
    }
    /**
     * @type {function} - Focus the input field
     * @event HTMLElement:click
     */
    const focusInput = () => input.focus()

    /**
     * @type {function} - Add a facet in the validated facet list
     * @event HTMLElement:click
     * @param {Facet} suggestion
     */
    const addFacet = suggestion => {
        $facets.push(suggestion)
        $facets = $facets
    }
    /**
     * @type {function} - Remove a facet
     * @event HTMLElement:click
     * @param {Facet} facet
     */
    const removeFacet = facet => {
        $facets = $facets.filter(item => item !== facet)
    }

    /**
     * @type {function} - Init the barcode inside a SVG
     * @param {SVGElement} svg - The SVG element to inject the barcode
     */
    const barcode = svg => {
        try {
            JsBarcode(svg, svg.getAttribute("data-code"), {
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
</script>

<style lang="scss">
    section {
        font-family: sans-serif;
        position: relative;
        margin: 0.5rem 0;

        input {
            border: 1px solid transparent;
            width: 100%;
            outline: none;
            padding: 1ex;
            font-size: 1.4rem;
            border-radius: 1ex;
            box-shadow: 0 1px 2px rgba(0%, 0%, 0%, 0.25);
            transition: box-shadow 1s;
            background: white;
            box-sizing: border-box;

            &.loading {
                animation: rainbow 2s linear;
                animation-iteration-count: infinite;
            }

            &:focus {
                box-shadow: 0 4px 8px rgba(0%, 0%, 0%, 0.4);
            }

            & + .buttons {
                position: absolute;
                top: 0;
                right: 0;
                padding: 1rem;
                font-size: 1rem;

                button {
                    margin-left: 1ex;
                }
            }
        }

        .additional {
            position: absolute;
            z-index: 10;
            top: calc(100% + 1px);
            right: 2ex;
            left: 2ex;
            border-bottom-left-radius: 1ex;
            border-bottom-right-radius: 1ex;
            padding: 0;
            background: rgba(100%, 100%, 100%, 0.5);
            box-shadow: 0 1px 2px rgba(0%, 0%, 0%, 0.4);
            backdrop-filter: blur(10px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s;

            & > .facets {
                display: block;
                border-top: none;
                border-bottom: none;

                .facet {
                    display: inline-flex;
                    margin: 1ex 0 1ex 1ex;
                    padding: 0.5ex 1.5ex;
                    border-radius: 2ex;
                    font-size: 0.8rem;
                    background: rgba(0%, 0%, 0%, 0.2);

                    header {
                        font-weight: bold;
                        color: #888;
                        margin-right: 1ex;
                        text-transform: capitalize;
                        display: inline;
                    }

                    button {
                        margin: 0 -1ex 0 1ex;
                    }
                }
            }

            aside {
                padding: 0 1ex 1ex;

                &.preview:not(:first-child)::before {
                    display: block;
                    content: "\a0";
                    height: 6px;
                    border-radius: 3px;
                    background: rgba(0%, 0%, 0%, 0.2);
                    margin: 0 1ex 1ex 1ex;
                }

                ul {
                    list-style: none;
                    padding: 0;
                    margin: 0;

                    li {
                        list-style: none;
                        padding: 1ex;
                        border-top: 1px solid rgba(0%, 0%, 0%, 0.25);

                        &:first-child {
                            border-top: none;
                        }
                    }
                }

                &.preview li.book {
                    header {
                        font-weight: bold;

                        u {
                            font-weight: normal;
                        }
                    }

                    div {
                        color: #888;
                    }

                    button {
                        float: right;
                    }
                }

                &.suggestions li.suggestion {
                    display: flex;

                    header {
                        font-weight: bold;
                        color: #888;
                        margin-right: 1ex;
                        width: 10ex;
                        text-align: right;
                        text-transform: capitalize;
                    }

                    div {
                        flex: auto;
                        cursor: copy;

                        &:hover {
                            text-decoration: underline;
                        }
                    }
                }
            }
        }

        &:hover .additional,
        input:focus + .additional {
            opacity: 1;
            pointer-events: all;
        }
    }

    @media (prefers-color-scheme: dark) {
        section {
            input {
                background: #2e363e;
                color: inherit;
                border: 1px solid rgba(100%, 100%, 100%, 0.2);

                & + .buttons {
                    padding: calc(1rem + 1px);
                }
            }

            .additional {
                background: rgba(46, 54, 62, 0.5);
                top: 100%;
            }

            aside {
                ul li {
                    border-top: 1px solid rgba(100%, 100%, 100%, 0.25);
                }

                &.preview::before {
                    background: rgba(100%, 100%, 100%, 0.2);
                }
            }
        }
    }
</style>

<section>
    <input bind:this={input} class:loading={requestInProgress>0} on:input|preventDefault|self={onInput} />
    {#if $searchQuery !== ""}
        <div class="buttons">
            <button class="text danger" on:click={() => clearSearch()} on:click={focusInput}>Clear</button>
            <button class="text primary" on:click={() => search()}>Search</button>
        </div>
    {/if}
    <div class="additional">
        {#if $facets.length > 0}
            <nav class="facets">
                {#each $facets as facet}
                    <div class="facet">
                        <header>{facet.payload.type}</header>
                        <span>{facet.payload.full || facet.value}</span>
                        <button class="round-grey" on:click={() => removeFacet(facet)} on:click={focusInput}>Remove</button>
                    </div>
                {/each}
            </nav>
        {/if}
        {#await $suggestion then suggestions}
            {#if suggestions.length > 0}
                <aside class="suggestions">
                    <nav>
                        <ul>
                            {#each suggestions as suggestion}
                                <li class="suggestion">
                                    <header>{suggestion.payload.type}</header>
                                    <div on:click={() => addFacet(suggestion)} on:click={focusInput}>{suggestion.payload.full || suggestion.value}</div>
                                    {#if suggestion.payload.bookId}<button class="color small" on:click={() => showBook(suggestion.payload.bookId)}>View</button>{/if}
                                </li>
                            {/each}
                        </ul>
                    </nav>
                </aside>
            {/if}
        {/await}
        {#await $searchPreview then results}
            {#if results.length > 0}
                <aside class="preview">
                    <nav>
                        <ul>
                            {#each results as result}
                                <li class="book">
                                    <button class="color small" on:click={() => showBook(result.bookId)}>View</button>
                                    <header>{#if result.series !== ""}<u>{result.series}</u>: {/if}{result.title}</header>
                                    <div>{result.author} &mdash; <svg class="type-barcode" use:barcode data-code="{result.isbn}"></svg></div>
                                </li>
                            {/each}
                        </ul>
                    </nav>
                </aside>
            {/if}
        {/await}
    </div>
</section>