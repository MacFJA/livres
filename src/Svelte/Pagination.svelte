<script>
    import { createEventDispatcher } from "svelte"
    import { _ } from "svelte-intl"
    import { derived } from "svelte/store"
    import Pagination, { ITEM_TYPES } from "ultimate-pagination"

    import { watchable } from "~/utils/watchable"

    /**
     * @type {Number} - The current page index
     * @prop
     */
    export let page = 1
    /**
     * @type {Number} - The number of item on each page
     * @prop
     */
    export let size = 10
    /**
     * @type {Number} - The total number of items
     * @prop
     */
    export let total = 1

    /**
     * @type {function} - Event dispatcher
     * @param {string} - Event name
     * @param {mixin} - Event data
     */
    const dispatch = createEventDispatcher()

    /**
     * @type {derived<Array>} - The pagination items
     * Depends on `page`, `size` and `total` value
     */
    let pagination = derived([watchable(() => parseInt(page)), watchable(() => size), watchable(() => total)], ([$page, $size, $total]) => {
        if ($total === 0 || $size === 0) {
            return []
        } else {
            return Pagination.getPaginationModel({
                currentPage: $page,
                totalPages: Math.ceil($total / $size),
                boundaryPagesRange: 1,
                siblingPagesRange: 1,
            })
        }
    })

    /**
     * @type {function} - Change the current page
     * @param {Number} pageNumber - pageNumber
     * @event HTMLButtonElement:click
     * @emit change-page
     */
    const toPage = pageNumber => {
        page = pageNumber
        dispatch("change-page", { page })
    }
</script>

<style lang="scss">
    nav.pagination {
        padding: 1ex;
        border-radius: 0.5ex;
        background: rgba(0, 0, 0, 0.4);
        margin: 1ex;
        text-align: center;
        border: none;

        button {
            color: inherit;
            font-size: 0.7rem;
            border: none;
            background: none;
            cursor: pointer;
            margin: 0 0.5ex;
            padding: 1ex;
            border-radius: 0.5ex;

            &:not([disabled]):hover {
                background: rgba(100%, 100%, 100%, 0.2);
            }

            &.active {
                color: black;
                background: white;
            }
        }

        span,
        button[disabled] {
            opacity: 0.5;
            margin: 0 0.5ex;
            cursor: auto;
        }
    }
</style>

{#if $pagination.length > 0}
    <nav class="pagination">
        {#each $pagination as { key, type, value, isActive } (key)}
            {#if type === ITEM_TYPES.FIRST_PAGE_LINK}
                <button disabled="{isActive}" on:click="{() => toPage(value)}">
                    {$_("pagination.first")}
                </button>
            {:else if type === ITEM_TYPES.PREVIOUS_PAGE_LINK}
                <button disabled="{isActive}" on:click="{() => toPage(value)}">
                    {$_("pagination.previous")}
                </button>
            {:else if type === ITEM_TYPES.PAGE}
                <button
                    on:click="{() => toPage(value)}"
                    class:active="{isActive}"
                >
                    {value}
                </button>
            {:else if type === ITEM_TYPES.ELLIPSIS}
                <span>&hellip;</span>
            {:else if type === ITEM_TYPES.NEXT_PAGE_LINK}
                <button disabled="{isActive}" on:click="{() => toPage(value)}">
                    {$_("pagination.next")}
                </button>
            {:else if type === ITEM_TYPES.LAST_PAGE_LINK}
                <button disabled="{isActive}" on:click="{() => toPage(value)}">
                    {$_("pagination.last")}
                </button>
            {/if}
        {/each}
    </nav>
{/if}
