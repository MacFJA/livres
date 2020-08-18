<script>
    import { parse } from "isbn3"
    import Quagga from "quagga"

    import Completer from "~/Completer.svelte"

    /** @type {Array<string>} - List of found ISBN */
    let founds = []
    /** @type {Boolean} - Indicate is the live scanning is running */
    let scanning = false

    /**
     * @type {function} - Stop the live scanning
     * @event HTMLButtonElement:click
     */
    const stopLiveScanning = () => {
        Quagga.stop()
        let children = document.getElementById("streamFeedback").children, max = children.length
        for(let index = max; index-->0 ;) {
            children.item(index).remove()
        }
        scanning = false
    }
    /**
     * @type {function} - Start the live scanning
     * @event HTMLButtonElement:click
     */
    const startLiveScanning = () => {
        document.getElementById("startScanningButton").setAttribute("disabled", true)
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.getElementById("streamFeedback")
            },
            frequency: 2,
            decoder: {
                readers: ["ean_reader"],
                debug: {
                    showFoundPatches: true,
                }
            },
            locator: {
                halfSample: true,
                patchSize: "large",
                debug: {
                    showFoundPatches: true,
                }
            },
            numOfWorkers: 1,
            locate: true
        }, err => {
            if (err) {
                console.error(err)
                return
            }

            Quagga.start()
            scanning = true
        })
        Quagga.onDetected(result => {
            let code = result.codeResult.code
            if (founds.indexOf(code) === -1 && parse(code) !== null) {
                founds.push(code)
                founds = founds
            }
        })
        Quagga.onProcessed(result => {
            let drawingCtx = Quagga.canvas.ctx.overlay,
                drawingCanvas = Quagga.canvas.dom.overlay

            if (result) {
                if (result.boxes) {
                    drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")))
                    result.boxes
                        .filter(box => box !== result.box)
                        .forEach(box => Quagga.ImageDebug.drawPath(
                            box,
                            {x: 0, y: 1},
                            drawingCtx,
                            {color: "green", lineWidth: 2}
                        ))
                }

                if (result.box) {
                    Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2})
                }

                if (result.codeResult && result.codeResult.code) {
                    Quagga.ImageDebug.drawPath(result.line, {x: "x", y: "y"}, drawingCtx, {color: "red", lineWidth: 3})
                }
            }
        })
    }

    /**
     * @type {function} - Remove an isbn from the result list
     * @param {string} code - The ISBN to remove
     * @event HTMLButtonElement:click
     */
    const removeIsbn = code => founds = founds.filter(isbn => isbn !== code)

    /**
     * @type {function} - Start the book completion
     * @event HTMLButtonElement:click
     */
    const startCompletion = () => {
        let isbn = founds.shift()
        founds = founds

        if (isbn === undefined) {
            return
        }

        const completer = new Completer({
            target: document.getElementById("completer"),
            props: {isbn, nextable: true}
        })
        completer.$on("finish", () => {
            completer.$destroy()
            startCompletion()
        })
        completer.search()
    }
</script>

<style lang="scss">
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

        section {
            display: inline-block;
            padding: 1ex;

            header {
                font-size: 1.2rem;
                margin: 1rem;
            }

            ul {
                margin: 0;
                padding: 0;

                li {
                    margin: 0.5ex 0;
                    padding: 0;
                    list-style: none;
                    vertical-align: baseline;
                }
            }
        }
    }

    #streamFeedback {
        display: inline-block;
        position: relative;

        &:empty {
            display: none;
        }
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
    <section>
        <header>Found ISBN</header>
        <ul>
            {#each founds as found}
                <li>
                    <code>{found}</code>
                    <button class="color danger" on:click={() => removeIsbn(found)}>Remove</button>
                </li>
            {:else}
                <i>Nothing yet...</i>
            {/each}
        </ul>
    </section>
</div>
<div class="buttons">
    {#if scanning}
        <button class="big danger" on:click={stopLiveScanning}>Stop</button>
    {:else}
        <button class="big success" id="startScanningButton" on:click={startLiveScanning}>Start</button>
    {/if}
    {#if founds.length > 0}
        <button class="big" on:click={startCompletion}>Search</button>
    {/if}
</div>

<div id="completer"></div>
