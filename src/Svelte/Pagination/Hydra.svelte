<script>
    import { createEventDispatcher } from "svelte"
    import { _ } from "svelte-intl"
    import { writable, derived } from "svelte/store"

    import Loader from "~/Loader.svelte"
    import Pagination from "~/Pagination.svelte"
    import { watchable } from "~/utils/watchable"

    /**
     * @type {string} - The Base URL of the paginated collection
     * @prop
     */
    export let baseUrl
    /**
     * @type {string} - The query string name of the page attribute
     * @prop
     */
    export let pageParam = "_page"
    /**
     * @type {Number} - Page size
     * @prop
     */
    export let size = 10
    /**
     * @type {function} - The "setter" to force the loading state
     * @prop
     */
    export const forceLoading = () => {
        $forceLoadingState = true
    }
    /**
     * @type {function} - Force reloading the current collection
     * @prop
     */
    export const reload = () => {
        $forceLoadingState = true
        $forceLoadingState = false
    }
    /**
     * @type {function} - Update the base URL of the paginated collection
     * @param {String} newUrl - The collection url
     */
    export const updateUrl = newUrl => {
        baseUrl = newUrl
        $page = 1
    }

    /** @type {writable<Number>} - The current page number */
    let page = writable(1)
    /** @type {writable<Boolean>} - Flag to force loader to show */
    let forceLoadingState = writable(false)
    /** @type {Number} - Total number of item */
    let total = 0
    /**
     * @type {function} - Event dispatcher
     * @param {string} - Event name
     * @param {mixin} - Event data
     */
    const dispatch = createEventDispatcher()

    /**
     * @type {derived<Array<mixin>>} - The list of result
     * Depends on `page` and `forceLoadingState` value
     * @emit change
     */
    let itemsPromise = derived([page, forceLoadingState, watchable(() => baseUrl)], ([$page, $forceLoadingState, $baseUrl]) => {
        if ($forceLoadingState) {
            return new Promise(() => {})
        }
        let url = new URL($baseUrl)
        url.searchParams.set(pageParam, $page)
        return fetch(url.toString())
            .then(response => response.json())
            .then(response => {
                total = response["hydra:totalItems"]
                let items = response["hydra:member"]
                dispatch("change", {items})
                return items
            })
    })
    /**
     * @type {function} - Change the page according to the pager
     * @param {Event} event
     * @event Pagination:change-page
     */
    const prepareRequest = event => $page = event.detail.page
</script>

{#await $itemsPromise}
    <slot name="loading">
        <Loader />
    </slot>
{:then items}
    <slot name="before"></slot>
    {#each items as item (item["@id"])}
        <slot name="item" item="{item}"></slot>
    {:else}
        <slot name="empty">{$_("pagination.empty")}</slot>
    {/each}
    <slot name="after"></slot>
{/await}
{#if total > 0}
    <slot name="pagination" page="{$page}" {size} {total} onChange="{prepareRequest}">
        <Pagination page={$page} size={size} total={total} on:change-page={prepareRequest} />
    </slot>
{/if}
