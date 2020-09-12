<script>
    import Quagga from "quagga"
    import { _ } from "svelte-intl"

    import Completer from "~/Completer.svelte"

    /** @type {string} - The found ISBN */
    let isbn = undefined
    /** @type {Promise<string>|undefined} - The search result */
    let search
    /** @type {Completer} - The completer component */
    let completer

    /**
     * @type {function} - Start the book completion
     * @event HTMLButtonElement:click
     */
    const startCompletion = () => {
        if (isbn === undefined) {
            return
        }

        completer = new Completer({
            target: document.getElementById("completer"),
            props: {isbn}
        })
        completer.$on("finish", () => {
            completer.$destroy()
        })
        completer.search()
    }

    /**
     * @type {function} - Search for an ISBN in an image file
     * @param {Event} event
     * @event HTMLInputElement:change
     */
    function scanFile(event) {
        if (completer) {
            completer.$destroy()
            completer = null
        }

        isbn = undefined

        search = new Promise((resolve, reject) => {
            let files = event.currentTarget.files
            if (files.length > 0) {
                let fileToLoad = files[0]

                let fileReader = new FileReader()

                fileReader.onload = fileLoadedEvent => {
                    Quagga.decodeSingle({
                        decoder: {
                            readers: ["ean_reader"] // List of active readers
                        },
                        locate: true,
                        src: fileLoadedEvent.target.result
                    }, result => {
                        setTimeout(() => {
                            if (result && result.codeResult) {
                                isbn = result.codeResult.code
                                resolve()
                            } else {
                                reject()
                            }
                        }, 500)
                    })
                }

                fileReader.readAsDataURL(fileToLoad)
            }
        })
    }
</script>

<style>
    .streamContainer {
        padding: 1ex;
        background: rgba(0, 0, 0, 0.5);
        box-shadow: inset 0 0.25ex 0.5ex rgba(0, 0, 0, 0.5);
        border-radius: 1ex;
        margin: 1em;
        text-align: center;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
    }

    #streamFeedback {
        display: inline-block;
        position: relative;
    }

    #streamFeedback:empty {
        display: none;
    }

    :global(#streamFeedback canvas) {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    .buttons {
        text-align: center;
    }
</style>

<div class="streamContainer">
    <div id="streamFeedback"></div>
    <input type="file" on:change={scanFile}>
    {#if search}
        {#await search}
            <i>{$_("completer.state.searching")}</i>
        {:then result}
            {isbn}
        {:catch error}
            <i>{$_("completer.state.emtpty")}</i>
        {/await}
    {/if}
</div>

<div class="buttons">
    {#if isbn !== undefined}
        <button class="big" on:click={startCompletion}>{$_("completer.search")}</button>
    {/if}
</div>

<div id="completer"></div>
