<script>
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
    let completionsPromise = fetch(window.app.url.completions).then((response) =>
        response.json()
    )
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
    {#await completionsPromise}
        Loading...
    {:then completions}
        <NumericCriterion
            field="Page"
            bind:value="{page}"
            bind:operator="{pageOperator}"
        />
        <ListCriterion field="Storage" bind:value={storage} completions="{completions['storage']}" />
        <BooleanCriterion field="In movement" bind:value={inMovement} />
    {/await}
</div>
