<script>
    import { _ } from "svelte-intl"

    /**
     * @type {string} - Display name of the filter
     * @prop
     */
    export let field
    /**
     * @type {Number} - Value of the filter
     * @prop
     * -1: All
     * 1: Yes (true)
     * 0: No (false)
     */
    export let value
</script>

<label class="filter" class:empty="{value === '-1'}">
    <span>{field}</span>
    <select bind:value>
        <option value="-1">{$_("filter.any")}</option>
        <option value="1">{$_("filter.yes")}</option>
        <option value="0">{$_("filter.no")}</option>
    </select>
</label>
