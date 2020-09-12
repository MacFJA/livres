<script>
    import isbn from "isbn3"
    import moment from "moment"
    import { _ } from "svelte-intl"
    import tippy from "tippy.js"

    import { barcode } from "~/utils/barcode"

    /**
     * @type {string} - The field type
     * @prop
     */
    export let type
    /**
     * @type {mixin} - The field value
     * @prop
     */
    export let value

    /**
     * @type {function} - Initialize the tooltip of an image
     * @param {HTMLSpanElement} element - The element that contains the image url
     * @return {{destroy: (function(): *)}}
     */
    const imageTooltip = element => {
        let instance = tippy(element, {
            content:
                "<img src=\"" +
                element.textContent +
                "\" style=\"max-width: 100%\"/>",
            allowHTML: true,
            animation: "scale",
            arrow: true,
            inertia: true,
        })

        return {
            destroy: () => instance.destroy()
        }
    }

    /**
     * @type {function} - Initialize the tooltip of an URL
     * @param {HTMLAnchorElement} element - The link element
     * @param {string} url - The link URL
     * @return {{destroy: (function(): *)}}
     */
    const textTooltip = (element, url) => {
        let instance = tippy(element, {
            content: url,
            allowHTML: false,
            animation: "scale",
            arrow: true,
            inertia: true,
        })

        return {
            destroy: ()  => instance.destroy()
        }
    }

    /**
     * @type {function} - Test if the provided value is a date
     * @param {Date|Object|string} value - The value to test
     * @return {boolean}
     */
    const isDate = value => value instanceof Date
            || (value !== null && typeof value === "object" && "date" in value)
            || moment(value, moment.HTML5_FMT.DATE, true).isValid()

</script>

<style>
    .tag {
        display: inline-block;
        padding: 0.2ex 0.5ex;
        margin: 0 0.5ex;
        background: rgba(0%, 60%, 60%, 0.2);
        border-radius: 0.5ex;
    }

    .image {
        word-break: break-word;
    }
</style>

{#if (type === "array" || type === "dynamic") && Array.isArray(value)}
    {#each value as item}
        <span class="tag">
            <svelte:self value="{item}" type="" />
        </span>
    {/each}
{:else if type === "number"}
    <span style="font-family: monospace;">{value}</span>
{:else if type === "date" || isDate(value)}
    {#if value instanceof Date}
        {moment(value).format("LL")}
    {:else if typeof value === "object" && "date" in value}
        {moment(value.date).format("LL")}
    {:else if moment(value, moment.HTML5_FMT.DATE, true).isValid()}
        {moment(value).format("LL")}
    {:else}
        {value}
    {/if}
{:else if type === "image"}
    <span class="image" use:imageTooltip>{value}</span>
{:else if type === "barcode"}
    {#if isbn.parse(value)}
        {isbn.asIsbn13(value, true)}
    {:else}
        {value}
    {/if}
    <svg class="type-barcode" use:barcode="{value}"></svg>
{:else if type === "url" || (typeof value === "string" && value.substr(0, 4) === "http")}
    <a href="{value}" target="_blank" use:textTooltip="{value}">
        {$_("book.field.visit_webpage")}
    </a>
{:else}
    {value}
{/if}
