<script>
    import { _ } from "svelte-intl"

    import BooleanCriterion from "./BooleanCriterion.svelte"
    import ListCriterion from "./ListCriterion.svelte"
    import NumericCriterion from "./NumericCriterion.svelte"

    export let
        /**
             * @type {Number} - Number of page
             * @prop
             */
        page = 0,
        /**
             * @type {string} - The page number operator
             * gt: Greater than
             * lt: Less than
             * gte: Greater or equals to
             * lte: Less or equals to
             * @prop
             */
        pageOperator = "gt",
        /**
             * @type {string} - Where are the book
             * @prop
             */
        storage,
        /**
             * @type {int} - Flag to indicate if the book is currently in a movement
             * -1: No filter
             * 1: Only books with movement in progress
             * 0: Only books with no movement or no movement in progress
             * @prop
             */
        inMovement = -1

    /**
     * @type {Promise<Array>}
     */
    let storageCompletionsPromise = fetch(window.app.url.completions.replace("__placeholder", "storage"))
        .then(response => response.json())
        .then(response => response.values)


</script>

<style>
    .container {
        position: relative;
        min-height: 1ex;
        border: 4px solid rgba(0, 0, 0, 0.5);
        padding: 0.5ex;
        border-radius: 1ex;
    }
</style>

<div class="container">
    {#await storageCompletionsPromise}
        Loading...
    {:then completions}
        <NumericCriterion
            field="{$_('filter.type.pages')}"
            bind:value="{page}"
            bind:operator="{pageOperator}"
        />
        <ListCriterion field="{$_('filter.type.storage')}" bind:value={storage} completions="{completions}" />
        <BooleanCriterion field="{$_('filter.type.movement')}" bind:value={inMovement} />
    {/await}
</div>
