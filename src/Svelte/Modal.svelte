<script context="module">
    let instanceClose
    let instanceOpen

    /**
     * @type {function} - Close the application modal
     */
    export const close = () => {
        instanceClose()
    }

    /**
     * @type {function|Promise} - Function to open application modal
     * @param {object} withComponent - The Svelte component to display inside the modal
     * @param {object} withProps - The props to use on the component
     * @param {boolean} [preventClose=false] - Hide close button
     */
    export const open = (withComponent, withProps, preventClose) => {
        return instanceOpen(withComponent, withProps, preventClose)
    }
</script>

<script>
    import { fade } from "svelte/transition"

    /** @type {mixin|null} - The component to display */
    let component = null
    /** @type {Object|null} - The component props */
    let props = null
    /** @type {boolean} - A flag to hide close button */
    let noClose = false
    /**
     * @type {function} - The function call when the modal is about to be closed
     * @param {component} component - The component in the modal
     * @param {Object} props - The component props
     */
    let promiseResolve

    /**
     * @type {function} - Open the modal with a component
     * @param {component} withComponent - The component to display inside the modal
     * @param {Object} [withProps={}] - The props of the component
     * @param {boolean} [preventClose=false] - Flag to disable modal closing
     * @return {Promise<undefined>}
     */
    const openModal = (withComponent, withProps, preventClose) => {
        component = withComponent
        props = withProps || {}
        noClose = preventClose || false
        document.body.classList.add("no-scroll")
        return new Promise((resolve) => {
            promiseResolve = resolve
        })
    }

    /**
     * @type {function} - Close the modal
     */
    const closeModal = () => {
        if (typeof promiseResolve === "function") {
            promiseResolve(component, props)
            document.body.classList.remove("no-scroll")
            props = null
            component = null
        }
    }

    /**
     * @type {function} - Keyup handler to close modal on "ESC"
     * @param {KeyboardEvent} event - The event
     * @event Window:keyup
     */
    const handleKeyup = (event) => {
        if (component && event.key === "Escape" && !noClose) {
            closeModal()
        }
    }

    instanceClose = closeModal
    instanceOpen = openModal
</script>

<style lang="scss">
    :global(body.no-scroll) {
        overflow: hidden;
    }

    .modal {
        z-index: 100;
        position: fixed;
        backdrop-filter: blur(10px) saturate(0);
        background: rgba(100%, 100%, 100%, 0.3);
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        text-shadow: 0 -1px 0 rgba(100%, 100%, 100%, 0.3);
        box-shadow: inset 0 0 10vh rgba(0, 0, 0, 0.4);

        div.content {
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            padding: 4em;
            height: 100%;
            overflow-y: auto;
            box-sizing: border-box;
        }

        button {
            position: absolute;
            top: 1ex;
            right: 1ex;
            background: none;
            border: none;
            color: inherit;
            font-size: 1rem;
            cursor: pointer;
            z-index: 10;

            &::after {
                content: "\D7";
                margin-left: 1ex;
            }
        }
    }

    @media (prefers-color-scheme: dark) {
        .modal {
            background: rgba(0, 0, 0, 0.5);
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.7);
        }
    }
</style>

<svelte:options immutable="{true}" />
<svelte:window on:keyup="{handleKeyup}" />

{#if component !== null}
    <div class="modal" transition:fade>
        {#if !noClose}
            <button on:click="{closeModal}">Close</button>
        {/if}
        <div class="content">
            <svelte:component
                this="{component}"
                {...props}
            />
        </div>
    </div>
{/if}
<slot />
