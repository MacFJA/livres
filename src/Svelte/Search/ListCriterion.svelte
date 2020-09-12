<script>
    import { _ } from "svelte-intl"

    /**
     * @type {string} - Display name of the filter
     * @prop
     */
    export let field
    /**
     * @type {Array<string>} - List of all possible values
     * @prop
     */
    export let completions = []
    /**
     * @type {string} - The filter value
     * @prop
     */
    export let value
</script>

<label class="filter" class:empty="{value === undefined || value === ''}">
    <span>{field}</span>
    <select bind:value>
        <option value="">{$_("filter.any")}</option>
        {#each completions as completion}
        <option>{completion}</option>
        {/each}
    </select>
</label>
