<script>
    import { parse } from "isbn3"
    import { Line } from "progressbar.js"
    import { createEventDispatcher } from "svelte"
    import { derived, writable } from "svelte/store"

    import BookResult from "./Completer/BookResult.svelte"
    import ProviderResult from "./Completer/ProviderResult.svelte"
    import Pagination from "./Pagination.svelte"

    /**
     * @typedef {Object} ProviderInformation
     * @property {string} name - The provider name
     * @property {string} code - The provider code
     * @property {string} url - The provider search url
     */
    /**
     * @typedef {Object} Result
     * @property {string} provider - The provider name
     * @property {string} code - The provider code
     * @property {Array<DataLine>} result - Search result
     */
    /**
     * @typedef {Object} DataLine
     * @property {string} key - The data identifier
     * @property {string} fusion - How the data should be updated
     * @property {string} type - The data type
     * @property {string} label - The display name of the data
     * @property {mixin} value - The data
     */
    /**
     * @type {string} - The ISBN to complete/search
     * @prop
     */
    export let isbn = ""
    /**
     * @type {boolean} - Indicate that there are multiple ISBN to treat
     * @prop
     */
    export let nextable = false
    /** @type {Promise<Array<Result>>} - List of all provider results */
    let providersResults
    /** @type {Line} - Progressbar */
    let progress
    /** @type {writable<Number>} - Number of completed provider search */
    let received = writable(0)
    /** @type {Number} - Result page number */
    let resultIndex = 1
    /**
     * @type {function} - Event dispatcher
     * @param {string} - Event name
     * @param {mixin} - Event data
     */
    const dispatch = createEventDispatcher()
    /**
     * @type {derived<number>} - The percentage of receive result
     * Depends on `received` value
     */
    let percentReceive = derived([received], ([$received]) => $received / Math.max(1, window.app.providers.length))
    percentReceive.subscribe(value => {
        if (progress) {
            progress.animate(value)
        }
    })

    /**
     * @type {function} - Start the providers search
     * @prop
     */
    export const search = () => {
        $received = 0
        resultIndex = 1
        let requests = window.app.providers.map(providerData => searchWithProvider(providerData, isbn))

        providersResults = Promise.allSettled(requests).then((results) => {
            return results
                .filter((promise) => promise.status === "fulfilled" && promise.value !== null)
                .map((promise) => promise.value)
                .reduce((all, result) => all.concat(result), [])
        })
    }

    /**
     * @type {function} - Start a provider search
     * @param {ProviderInformation} providerData - The provider information
     * @param {string} isbn - The ISBN to search
     * @return {Promise<Array<Result>>}
     */
    const searchWithProvider = (providerData, isbn) => {
        return fetch(providerData.url.replace("0", isbn))
            .then((response) => response.json())
            .then((response) => {
                $received = $received+1
                return Promise.resolve(response)
            })
            .then((response) =>
                response.map((item) => {
                    return {
                        provider: providerData.name,
                        code: providerData.code,
                        result: item,
                    }
                })
            )
            .catch(() => {
                $received = $received+1
                return null
            })
    }

    /**
     * @type {function} - Initialize the search progressbar
     * @param {HTMLDivElement} element - The progressbar holder
     */
    const initLoader = element => {
        progress = new Line(element, {
            color: "var(--progress-color)",
            trailColor: "transparent",
            trailWidth: 1,
            strokeWidth: 1,
            duration: 300,
        })
    }

    /**
     * @type {function} - Trigger the finish event (next search, or book preview is close)
     * @event BookResult:close
     * @event HTMLButtonElement:click
     * @emit finish
     */
    const onBookClose = () => {
        dispatch("finish")
    }
</script>

<style>
    .search-result {
        display: flex;
        flex-wrap: wrap;
    }

    .search-result > * {
        flex: 1;
    }

    .search-result > .no-provider-result {
        flex: 100%;
        text-align: center;
        font-size: 1.5rem;
        font-weight: lighter;
    }

    input[type="search"] {
        font-size: 1.5rem;
        height: 2.4rem;
        display: block;
        width: 50%;
        min-width: 20ex;
        margin: 1em auto;
        padding: 0 0.6em;
        border-radius: 1.2rem;
        border: rgba(0, 0, 0, 0.2);
        line-height: 2.4rem;
        box-sizing: border-box;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        text-shadow: 0 0 3px rgba(100%, 100%, 100%, 0.5);
        text-align: center;
    }

    form button {
        margin: 1em auto;
        display: block;
        border-radius: calc(1ex);
    }

    .progress {
        min-width: 100%;
        padding: 1rem;
        box-sizing: border-box;
    }

    .progress :global(svg) {
        border-radius: 8px;
        background-color: var(--progress-background);
        box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.5);
        height: 16px;
    }

    .buttons {
        text-align: center;
    }
</style>

{#if !providersResults}
    <form on:submit|preventDefault="{search}">
        <input
            type="search"
            name="isbn"
            bind:value="{isbn}"
            minlength="10"
            maxlength="17"
            pattern="[\d-]{(10, 17)}"
        />
        <button class="big primary" on:click|preventDefault="{search}" disabled="{parse(isbn) === null}">Search</button>
    </form>
{:else}
    <input type="search" name="isbn" readonly disabled value="{isbn}" />
    <div class="search-result">
        {#await providersResults}
            <div class="progress" use:initLoader></div>
        {:then allResults}
            {#if allResults.length > 0}
                <div class="providers-result">
                    <ProviderResult
                        provider="{allResults[resultIndex - 1].provider}"
                        data="{allResults[resultIndex - 1].result}"
                    />
                    <Pagination
                        bind:page="{resultIndex}"
                        size="1"
                        total="{allResults.length}"
                    />
                </div>
            {:else}
                <p class="no-provider-result">Book Not Found</p>
            {/if}
            <div class="book-result">
                <BookResult on:close={onBookClose} />
            </div>
        {:catch error}
            <p>Something went wrong: {error.message}</p>
        {/await}
    </div>
{/if}
{#if nextable}
<div class="buttons">
    <button class="big warn" on:click={onBookClose}>Next</button>
</div>
{/if}