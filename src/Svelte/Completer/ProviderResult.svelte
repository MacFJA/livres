<script>
    import { _ } from "svelte-intl"
    import TinyEmitter from "tiny-emitter/instance"

    import GenericResult from "./GenericResult.svelte"

    /**
     * @typedef {Object} DataLine
     * @property {string} key - The data identifier
     * @property {string} fusion - How the data should be updated
     * @property {string} type - The data type
     * @property {string} label - The display name of the data
     * @property {mixin} value - The data
     */

    /**
     * @type {Array<DataLine>} - The book data
     * @prop
     */
    export let data = []
    /** @type {string} - The provider name */
    export let provider

    /**
     * @type {function} - Notify the BookResult to add a data
     * @param {string} fusion - How the data should be updated
     * @param {string} key - The data identifier
     * @param {string} type - The data type
     * @param {string} label - The display name of the data
     * @param {mixin} value - The data
     * @emit result-append
     * @event HTMLButtonElement:click
     */
    const updateBook = (fusion, key, type, label, value) => {
        TinyEmitter.emit("result-append", fusion, key, label, type, value)
    }

    /**
     * @type {function} - Notify the BookResult to add all data
     * @param {string} fusion - The type of fusion to do
     * @event HTMLButtonElement:click
     * @emit result-append
     */
    const globalUpdateBook = fusion => {
        let toUpdate = data.map(item => {
            if (fusion !== "append") {
                item.fusion = fusion
            }
            return item
        })
        toUpdate.forEach(item =>
            updateBook(item.fusion, item.key, item.type, item.label, item.value)
        )
    }
</script>

<GenericResult data="{data}">
    <span slot="header">{provider}</span>
    <span slot="subheader">
        <button class="color" on:click="{() => globalUpdateBook('append')}">
            {$_("completer.result.append")}
        </button>
        <button
            class="color danger"
            on:click="{() => globalUpdateBook('replace')}"
        >
            {$_("completer.result.replace")}
        </button>
        <button
            class="color primary"
            on:click="{() => globalUpdateBook('missing')}"
        >
            {$_("completer.result.add_missing")}
        </button>
    </span>
    <span slot="buttons" let:fusion let:value let:label let:key let:type>
        <button
            class="color danger"
            on:click="{() => updateBook('replace', key, type, label, value)}"
        >
            {$_("completer.result.replace")}
        </button>
        {#if fusion !== "replace"}
            <button
                class="color"
                on:click="{() => updateBook(fusion, key, type, label, value)}"
            >
                {$_("completer.result.append")}
            </button>
        {/if}
    </span>
</GenericResult>
