<script>
    import { onMount } from "svelte"

    import Edit from "./Book/Edit.svelte"
    import BookList from "./BookList.svelte"
    import Completer from "./Completer.svelte"
    import ImageScan from "./Completer/ImageScan.svelte"
    import LiveScan from "./Completer/LiveScan.svelte"
    import Modal, { close } from "./Modal.svelte"

    /** @type {string} - The current section (page) identifier */
    let section
    /** @type {Number|null} - A book id */
    let bookId

    onMount(() => {
        sectionFromUrl(window.location.href)
    })

    /**
     * @type {function} - Change the section from an url
     * @param {string} url - The URL to analyze
     */
    const sectionFromUrl = url => {
        close()
        if (url.endsWith("#add-isbn")) {
            section = "add-isbn"
        } else if (url.endsWith("#add-live")) {
            section = "add-live"
        } else if (url.endsWith("#add-photo")) {
            section = "add-photo"
        } else if (url.match(/#edit-book-\d+$/)) {
            let matches = url.match(/#edit-book-(\d+)$/)
            bookId = matches[1]
            section = "edit-book"
        } else {
            section = "library"
        }
    }

    /**
     * @type {function} - Update the section when browser url change
     * @param {HashChangeEvent} event - The browser URL Hash change event
     * @event Window:hashchange
     */
    const updateSection = event => sectionFromUrl(event.newURL)

    /**
     * @type {function} - Change the page according to a section identifier
     * @param {string} sectionName - The section identifier
     */
    const changeSection = sectionName => {
        if (["add-isbn", "add-live", "add-photo"].indexOf(sectionName) === -1) {
            sectionName = "library"
        }
        window.location.hash = sectionName
    }
</script>

<style lang="scss">
    nav {
        display: flex;

        > button {
            flex: 1;

            &:not(:first-child) {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }

            &:not(:last-child) {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }
        }
    }

    p.lead {
        font-size: 1.2rem;
        margin-top: 3em;
    }
</style>

<svelte:window on:hashchange={updateSection} />

<nav>
    {#if window.app.roles.view}
        <button class="color" on:click={() => changeSection("library")} class:primary={section === "library"}>Library</button>
    {/if}
    {#if window.app.roles.add}
        <button class="color" on:click={() => changeSection("add-isbn")} class:primary={section === "add-isbn"}>Add by ISBN</button>
        <button class="color" on:click={() => changeSection("add-live")} class:primary={section === "add-live"}>Add by Scanning</button>
        <button class="color" on:click={() => changeSection("add-photo")} class:primary={section === "add-photo"}>Add by Image</button>
    {/if}
    {#if window.app.roles.connected}
        <button on:click="{() => window.location = window.app.url.logout}" class="color danger">Logout</button>
    {/if}
</nav>

<Modal/>
{#if section === "add-isbn" && window.app.roles.add}
    <Completer />
{:else if section === "add-live" && window.app.roles.add}
    <LiveScan />
{:else if section === "edit-book" && window.app.roles.edit}
    <Edit {bookId} />
{:else if section === "add-photo" && window.app.roles.add}
    <ImageScan />
{:else if section !== undefined && window.app.roles.view}
    <BookList size="{window.app.pageSize}" />
{:else}
    <p class="lead">To use the application you must be connected</p>
    <button class="big primary" on:click={() => location.href = window.app.url.login}>Sign in</button>
{/if}
