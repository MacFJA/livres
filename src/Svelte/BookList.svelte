<script>
    import { onMount } from "svelte"
    import { writable, derived } from "svelte/store"
    import { slide, fly, scale } from "svelte/transition"

    import Book from "./Book.svelte"
    import Loader from "./Loader.svelte"
    import { open } from "./Modal.svelte"
    import Pagination from "./Pagination.svelte"
    import Hydra from "./Pagination/Hydra.svelte"
    import Bar from "./Search/Bar.svelte"
    import Criteria from "./Search/Criteria.svelte"

    /**
     * @type {Number} - The number of book to display
     * @prop
     */
    export let size = 20
    /**
     * @type {string} - The display mode
     * list: Condense display
     * grid: Emphases the cover
     * @prop
     */
    export let display = "list"

    /**
     * @type {function} - Handler of intersection observer
     * @param {Array<IntersectionObserverEntry>} entries - List of observed entry that are visible
     * @param {IntersectionObserver} observer - The Intersection observer
     * @event viewport:IntersectionObserver
     */
    const lazyLoader = (entries, observer) => {
        entries.forEach(
            entry => {
                if (!entry.isIntersecting) {
                    return
                }
                if (entry.target.hasAttribute("data-src")) {
                    entry.target.setAttribute("src", entry.target.getAttribute("data-src"))
                    observer.unobserve(entry.target)
                }
            }
        )
    }
    /** @type {IntersectionObserver} - The image visibility observer */
    let observer = new IntersectionObserver(lazyLoader, {})

    /** @type {Hydra} - The pagination wrapper */
    let hydra

    let
        /** @type {writable<Number>} - The book page number criterion */
        searchPage = writable(0),
        /** @type {writable<string>} - The book page number operator criterion */
        searchPageOperator = writable("gt"),
        /** @type {writable<string>} - The book storage criterion */
        searchStorage = writable(""),
        /** @type {writable<string>} - The book movement criterion */
        searchInMovement = writable("-1"),
        /** @type {writable<string>} - The book search query criterion */
        searchQuery = writable("")

    /**
     * @type {function} - Initializer of image lazy loading
     * @param {HTMLIFrameElement} element - The image to lazy load
     */
    const updateLazy = element => observer.observe(element)

    /**
     * @type {derived<string>} - The book collection filter string
     * Depends on `searchPage`, `searchPageOperator`, `searchStorage`, `searchInMovement` and `searchQuery` value
     */
    let filterString = derived(
        [searchPage, searchPageOperator, searchStorage, searchInMovement, searchQuery],
        ([$searchPage, $searchPageOperator, $searchStorage, $searchInMovement, $searchQuery]) => {
            let urlParams = new URLSearchParams()
            if ($searchPageOperator && $searchPage && $searchPageOperator !== "gt" && $searchPage > 0) {
                urlParams.set(`pages[${$searchPageOperator}]`, $searchPage)
            }
            if ($searchInMovement === "0") {
                urlParams.set("in_movement", "false")
            }
            if ($searchInMovement === "1") {
                urlParams.set("in_movement", "true")
            }
            let mapping = {
                storage: $searchStorage || "",
                query: $searchQuery || ""
            }
            Object.entries(mapping).forEach((line) => {
                if (line[1] !== "") {
                    urlParams.set(line[0], line[1])
                }
            })
            return urlParams.toString()
        }
    )
    onMount(() => {
        filterString.subscribe(() => {
            hydra.updateUrl(window.app.url.books + `?_size=${size}&${$filterString}`)
        })
    })

    /** @type {function} - Reset all filter */
    const showAll = () => {
        $searchStorage = $searchQuery = ""
        $searchPageOperator = "gt"
        $searchPage = 0
        $searchInMovement = "-1"
    }

    /**
     * @type {function} - Display a book
     * @param {Object} data - The book data from the API
     */
    const showBook = data => {
        let bookData = data
        bookData["iri"] = bookData["@id"]
        bookData["id"] = bookData["bookId"]
        open(Book, bookData)
    }

    /**
     * @type {function} - Inject full and preview cover on all book entry
     * @param {Event} event - Update event
     * @event Hydra:change
     */
    const updateItems = event => {
        event.detail.items = event.detail.items.map(item => {
            item.coverFull = window.coverFull.replace(
                "placeholder.jpg",
                item.cover
            )
            item.coverPreview = window.coverPreview.replace(
                "placeholder.jpg",
                item.cover
            )
            return item
        })
    }
</script>

<style lang="scss">
    .display-mode {
        padding: 1ex;
        border-radius: 0.5ex;
        background: rgba(0, 0, 0, 0.4);
        margin: 1ex;
        text-align: center;
        border: none;
        display: flex;

        > * {
            flex: auto;
            align-self: center;
        }
    }

    .display-grid {
        display: flex;
        flex-wrap: wrap;
    }

    .display-grid > div {
        min-width: 200px;
        min-height: 200px;
        position: relative;
        margin: 4px;
        flex: auto;
        box-shadow: 0 0 2px;

        img {
            max-width: 100%;
            width: 100%;
            vertical-align: bottom;
            object-fit: cover;
            min-height: 100%;
        }

        h2 {
            position: absolute;
            top: 0;
            margin: 0;
            font-size: 150%;
            padding: 1ex;
            font-weight: bold;
            color: white;
            background: rgba(0, 0, 0, 0.6);
            display: block;
            text-align: left;
            width: 100%;
            box-sizing: border-box;
            font-family: cursive;
            text-shadow: 0 1px 2px black;
        }

        button {
            background: rgba(100%, 100%, 100%, 0.3);
            backdrop-filter: blur(4px) brightness(2) saturate(2);
            position: absolute;
            bottom: 1.5ex;
            right: 1.5ex;
            text-align: center;
            padding: 1ex 2em;
            border: none;
            color: black;
            font-size: 0.7rem;
            border-radius: calc(1em + 1.5ex);
            cursor: pointer;
        }
    }

    .display-list > div {
        border-bottom: 1px solid rgba(0, 0, 0, 0.3);
        box-shadow: 0 1px 0 rgba(100%, 100%, 100%, 0.2);
        padding: 0.5ex;
        display: flex;
        align-items: center;

        &:last-of-type {
            border-bottom: none;
            box-shadow: none;
        }

        * {
            padding: 1ex;
        }

        img {
            height: 2em;
            width: auto;
            padding: 0;
        }

        h2 {
            margin: 0;
            flex: 1;
            font-size: 1.2rem;
            font-family: cursive;
        }
    }

    .criteria {
        margin-top: 1ex;
    }

    aside[slot=pagination],
    aside[slot=loading] {
        flex: 100%;
        display: block !important;
    }
</style>

<Bar on:search={event => $searchQuery = event.detail} />

<div class="criteria">
    <Criteria
        bind:page="{$searchPage}"
        bind:pageOperator="{$searchPageOperator}"
        bind:storage="{$searchStorage}"
        bind:inMovement={$searchInMovement}
    />
</div>

<div class="display-mode">
    <label>
        <input type="radio" value="list" bind:group="{display}" />
        List
    </label>
    <label>
        <input type="radio" value="grid" bind:group="{display}" />
        Grid
    </label>
    {#if $filterString !== ""}<button transition:scale on:click={() => showAll()} class="color warn">Show all</button>{/if}
</div>

<div class="display-{display}">
    <Hydra bind:this={hydra} baseUrl="{window.app.url.books + `?_size=${size}&`}" size="{size}" on:change={updateItems}>
        <div slot="item" let:item out:slide in:fly="{{ x: -10 }}">
            <img
                    use:updateLazy
                    src="{window.placeholderPreview}"
                    onerror="this.src = window.coverPreview"
                    data-src="{item.coverPreview}"
                    alt="{item.title}"
            />
            <h2>
                {#if item.series}<u>{item.series}</u>{/if}
                {item.title}
            </h2>
            <button class:color={display === "list"} on:click="{() => showBook(item)}">Show</button>
        </div>
        <aside slot="pagination" let:page let:size let:total let:onChange>
            <Pagination page={page} size={size} total={total} on:change-page={onChange} />
        </aside>
        <aside slot="loading">
            <Loader />
        </aside>
    </Hydra>
</div>