<script>
    import { _ } from "svelte-intl"

    /**
     * @type {string} - Display name of the filter
     * @prop
     */
    export let field
    /**
     * @type {Number} - The numeric value
     * @prop
     */
    export let value
    /**
     * @type {string} - The number operator
     * gt: Greater than
     * lt: Less than
     * gte: Greater or equals to
     * lte: Less or equals to
     * @prop
     */
    export let operator
</script>

<label class="filter" class:empty="{value <= 0}">
    <span>{field}</span>
    <select bind:value="{operator}">
        <option value="lt">{$_("filter.lt")}</option>
        <option value="lte">{$_("filter.lte")}</option>
        <option value="gt">{$_("filter.gt")}</option>
        <option value="gte">{$_("filter.gte")}</option>
    </select>
    <input type="number" bind:value />
</label>
