<script>
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
        <option value="lt">Less than</option>
        <option value="lte">Less or equals to</option>
        <option value="gt">Greater than</option>
        <option value="gte">Greater or equals to</option>
    </select>
    <input type="number" bind:value />
</label>
