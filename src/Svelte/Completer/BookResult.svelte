<script>
    import { createEventDispatcher, onMount, onDestroy } from "svelte"
    import { _ } from "svelte-intl"
    import { derived } from "svelte/store"
    import TinyEmitter from "tiny-emitter/instance"
    import tippy from "tippy.js"

    import FieldEditor from "./FieldEditor.svelte"
    import GenericResult from "./GenericResult.svelte"

    import Book from "~/Book.svelte"
    import { defaultItems } from "~/Completer/converter"
    import Loader from "~/Loader.svelte"
    import { open } from "~/Modal.svelte"
    import Error from "~/Modal/Error.svelte"
    import { typeJson } from "~/utils/headers"
    import { watchable } from "~/utils/watchable"

    /**
     * @type {Array<DataLine>} - The book data
     * @prop
     */
    export let data = []
    export let editBookId = null
    /** @type {watchable<Array<DataLine>>} - Svelte store of the data */
    let watchedData = watchable(() => data)

    /** @type {tippy} - Missing (required) element to add book */
    let missingTooltip
    /**
     * @type {function} - Event dispatcher
     * @param {string} - Event name
     * @param {mixin} - Event data
     */
    const dispatch = createEventDispatcher()
    /** @type {Array<string>} - Identifiers of required data to add a book */
    const required = ["isbn", "title", "owner"]

    /**
     * @type {derived<Array<DataLine>>} - The list of missing book data
     * Depends on `data` value (through `watchedData`)
     */
    let missing = derived([watchedData, _], ([$watchedData]) => {
        let missingItems = defaultItems().filter((item) => {
            return $watchedData.map((row) => row.key).indexOf(item.key) === -1
        })
        missingItems.push({
            key: "new",
            fusion: "push",
            type: "dynamic",
            label: $_("completer.field.new"),
            value: []
        })
        return missingItems
    })
    /**
     * @type {derived<Array<DataLine>>} - The list of missing book data that are required
     * Depends on `data` value (through `watchedData`)
     */
    let missingRequired = derived([watchedData], ([$watchedData]) => required.filter((item) => {
        return $watchedData.map((row) => row.key).indexOf(item) === -1
    }))

    /**
     * @type {function} - Remove a data from its key
     * @param {string} key
     */
    const removeItem = key => {
        data = data.filter((item) => item.key !== key)
    }

    /**
     * @type {function} - Update a book data according to the fusion rule
     * @param {string} fusion - How the data should be updated
     * @param {string} key - The data identifier
     * @param {string} label - The display name of the data
     * @param {string} type - The data type
     * @param {mixin} value - The data
     * @event *:result-append
     */
    const resultUpdate = (fusion, key, label, type, value) => {
        let updated = false

        data.forEach((item) => {
            if (item.key === key) {
                if (fusion === "replace") {
                    item.value = value
                } else if (fusion === "push") {
                    item.value = item.value.concat(value)
                } else if (fusion === "missing") {
                    // do nothing
                } else {
                    item.value = item.value + ", " + value
                }
                updated = true
            }
        })

        if (!updated) {
            data.push({ key, type, label, value })
        }

        data = data
    }

    /**
     * @type {function} - Open the field editor for a data
     * @param {string} key - The data identifier
     * @param {string} label - The display name of the data
     * @param {string} type - The data type
     * @param {mixin} value - The data
     */
    const editField = (key, label, type, value) => {
        open(FieldEditor, { label, type, value, onChange: (changes) => {
            if (label !== changes.label) {
                removeItem(key)
                let newLabel = changes.label,
                    newValue = changes.value,
                    newKey = newLabel
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/, "_")
                        .replace(/_+/, "_")
                        .replace(/^_/, "")
                        .replace(/_$/, "")
                key = newKey
                resultUpdate("replace", newKey, newLabel, type, newValue)
                return
            }
            resultUpdate("replace", key, label, type, changes.value)
        } })
    }

    /**
     * @type {function} - Remove all data of the book
     */
    const clear = () => data = []

    /**
     * @type {function} - Add the book
     */
    const send = () => {
        open(Loader, {}, true)

        let url = editBookId !== null ? window.app.url.bookEdit.replace("0", editBookId) : window.app.url.bookAdd

        fetch(url, {
            headers: typeJson(),
            method: "POST",
            body: JSON.stringify(data),
        })
            .then((response) => response.json())
            .then((response) => {
                response["iri"] = response["@id"]
                response["id"] = response["bookId"]
                open(Book, response).then(() => dispatch("close"))
            })
            .catch((message) => {
                open(Error, {
                    reason: $_("completer.error.save.message", {message}),
                    title: $_("completer.error.save.title")
                })
            })
    }

    /**
     * @type {function} - Add a new data
     * @param {DataLine} item - The data to add
     * @event HTMLElement:click
     */
    const addItem = item => {
        missingTooltip.hide()
        data.push(item)
        data = data
        editField(item.key, item.label, item.type, item.value)
    }

    /**
     * @type {function} - Initialize the missing popin
     * @param {HTMLElement} element
     * @init HTMLElement
     */
    const initMissing = element => {
        missingTooltip = tippy(element, {
            content: document.getElementById("missing-dropdown"),
            allowHTML: true,
            trigger: "click",
            interactive: true,
        })
    }

    /**
     * @type {function} - Initialize the required data list popin
     * @param {HTMLElement} element
     * @init HTMLElement
     */
    const requiredTooltip = element => {
        tippy(element, {
            onShow(instance) {
                if ($missingRequired.length === 0) {
                    return false
                }
                instance.setContent(
                    $_("completer.error.missing", {
                        missing:
                            defaultItems()
                                .filter(
                                    (item) =>
                                        $missingRequired.indexOf(item.key) !== -1
                                )
                                .map((item) => item.label)
                                .join(", ")
                    })
                )
            },
        })
    }

    onMount(() => {
        TinyEmitter.on("result-append", resultUpdate)
    })
    onDestroy(() => {
        TinyEmitter.off("result-append")
        try {
            missingTooltip.unmount()
            missingTooltip.destroy()
        } catch (e) {
            // Do nothing
        }
    })
</script>

<style lang="scss">
    #missing-dropdown {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;

        button {
            background: none;
            border: none;
            border-left: 1px solid rgba(100%, 100%, 100%, 0.3);
            border-radius: 0;
            color: rgb(80%, 80%, 80%);
            transition: color 0.3s, text-shadow 0.8s;
            text-shadow: 0 0 4px rgba(100%, 100%, 100%, 0);
            padding: 1ex;
            margin: 0.25ex 0;
            font-weight: bold;

            &:hover {
                text-shadow: 0 0 4px rgba(100%, 100%, 100%, 0.7);
                color: rgb(100%, 100%, 100%);
                background: rgba(100%, 100%, 100%, 0.2);
                border-radius: 0.5ex;
                margin-left: 1px;
                padding-left: calc(1ex - 1px);
            }

            &.dynamic {
                color: rgb(30%, 100%, 30%);

                &:hover {
                    color: rgb(70%, 100%, 70%);
                    background: rgba(80%, 100%, 80%, 0.2);
                }
            }

            &:first-child,
            &:nth-child(3n + 1) {
                border-left: none;
            }

            &:hover,
            &:hover + button {
                border-color: transparent;
            }
        }
    }
</style>

<div id="missing-dropdown">
    {#each $missing as item}
        <button class:dynamic={item.type === "dynamic"} on:click="{() => addItem(item)}">{item.label}</button>
    {/each}
</div>

<GenericResult data="{data}">
    <span slot="header">{$_("completer.book.title")}</span>
    <span slot="subheader">
        {#if $missing.length > 0}
            <button class="color" use:initMissing>{$_("completer.book.add_missing")}</button>
        {/if}
        {#if data.length > 0}
            <button class="color danger" on:click="{clear}">{$_("completer.book.clear")}</button>
            <span use:requiredTooltip>
                <button
                    disabled="{$missingRequired.length > 0}"
                    id="add-book"
                    class="color primary"
                    on:click="{send}"
                >
                    {#if editBookId !== null}{$_("completer.book.do_update")}{:else}{$_("completer.book.do_add")}{/if}
                </button>
            </span>
        {/if}
    </span>
    <span slot="buttons" let:fusion let:value let:label let:key let:type>
        <button
            class="color"
            on:click={() => editField(key, label, type, value)}
        >
            {$_("completer.book.line.edit")}
        </button>
        <button class="color danger" on:click="{() => removeItem(key)}">
            {$_("completer.book.line.remove")}
        </button>
    </span>
    <div slot="empty">{$_("completer.book.empty")}</div>
</GenericResult>
