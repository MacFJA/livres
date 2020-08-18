<script>
    import moment from "moment"
    import { derived } from "svelte/store"
    import { slide } from "svelte/transition"

    import { watchable } from "~/utils/watchable"

    /**
     * @typedef {Object} DataChanges
     * @property {string} label - The current label
     * @property {mixin} value - The current value
     */

    /**
     * @type {string} - Data display name
     * @prop
     */
    export let label
    /**
     * @type {string} - Data type
     * @prop
     */
    export let type
    /**
     * @type {mixin} - Data
     * @prop
     */
    export let value
    /**
     * @type {function} - The callback when data change
     * @prop
     * @param {DataChanges} [changes]
     */
    export let onChange = (changes) => { changes }
    /**
     * @type {string} - The original pass to the component
     */
    const original = JSON.stringify({value, label})

    /**
     * @type {derived<undefined>} - Magic variable to handle label and value changes
     * Depends on `label` and `value` value
     */
    let labelAndValue = derived([watchable(() => label), watchable(() => value)], ([$label, $value]) => {
        onChange({label: $label, value: $value})
    })
    $labelAndValue//Force variable to exist

    /**
     * @type {function} - Remove a value item
     * @param {Number} index - The value item position in the Array
     */
    const deleteItem = index => { value.splice(index, 1); value = value }

    /**
     * @type {function} - Restore value (and label) as they were pass to the component
     */
    const resetValue = () => {
        let originalData = JSON.parse(original)
        value = originalData.value
        label = originalData.label
    }

    /**
     * @type {function} - Add a new value item
     */
    const addItem = () => {
        value.push("")
        value = value
    }

    /**
     * @type {function} - Data change for date type
     * @param {InputEvent} event
     * @event HTMLInputElement:input
     */
    const dateChange = event => value = event.target.value
</script>

<style lang="scss">
    header {
        font-size: 2rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    div[class^="data-type-"] {
        margin: 1rem 0;
    }

    footer {
        text-align: center;
    }

    .group {
        display: flex;
        flex-wrap: nowrap;

        input {
            flex: auto;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
        }

        &:last-of-type input {
            border-bottom: none;
        }

        &:first-of-type input {
            border-top-left-radius: 0.75rem;
        }

        button {
            flex: 0;
            border-radius: 0;
        }

        &:first-of-type button {
            border-top-right-radius: 0.75rem;
        }
    }

    div[class^="data-type-"]:not(.data-type-array):not(.data-type-dynamic) input {
        width: 100%;
        border-radius: 0.75rem;
    }

    .data-type-array button.add,
    .data-type-dynamic button.add {
        border-radius: 0 0 0.75rem 0.75rem;
        width: 100%;
        transition: border-radius 0.6s;

        &:first-child {
            border-top-right-radius: 0.75rem;
            border-top-left-radius: 0.75rem;
        }
    }

    @media (prefers-color-scheme: dark) {
        .group input {
            border-bottom: 1px solid rgba(100%, 100%, 100%, 0.2);
        }
    }
</style>

<header>
    {#if type === "dynamic"}
        <input class="big" type="text" bind:value={label} />
    {:else}
        {label}
    {/if}
</header>
<div class="data-type-{type}">
    {#if type === "array" || type === "dynamic"}
        {#each value as line, index}
            <div class="group" transition:slide|local>
                <input class="big" type="text" bind:value="{line}" />
                <button
                    class="big danger remove"
                    on:click="{() => deleteItem(index)}"
                >
                    Remove
                </button>
            </div>
        {/each}
        <button class="big success add" on:click="{addItem}">Add</button>
    {:else if type === "number"}
        <input
            class="big"
            type="number"
            bind:value
        />
    {:else if type === "date"}
        {#if value instanceof Date}
            <input
                class="big"
                type="date"
                on:input="{dateChange}"
                value="{moment(value).format('Y-MM-DD')}"
            />
        {:else if typeof value === "object" && "date" in value}
            <input
                class="big"
                type="date"
                on:input="{dateChange}"
                value="{moment(value.date).format('Y-MM-DD')}"
            />
        {:else}
            <input
                class="big"
                type="date"
                bind:value
            />
        {/if}
    {:else}
        <input
            class="big"
            type="text"
            bind:value
        />
    {/if}
</div>
<footer>
    <button class="big warn" on:click="{resetValue}">Reset</button>
</footer>
