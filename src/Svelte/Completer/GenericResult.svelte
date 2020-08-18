<script>
    import { fly, slide } from "svelte/transition"

    import FieldRender from "../FieldRender.svelte"

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
    export let data

    /**
     * @type {function} - Compute the unique key of a data
     * @property {string} key - The data identifier
     * @property {string} type - The data type
     * @property {mixin} value - The data
     * @return {string}
     */
    const uniqueKey = (key, type, value) => {
        if (type === "image" || type === "barcode" || type === "url" || (typeof value === "string" && value.substr(0, 4) === "http")) {
            return `${key}:${value}`
        }
        return key
    }
</script>

<style lang="scss">
    article {
        margin: 1ex;
        border: 2px solid rgba(0, 0, 0, 0.5);
        border-radius: 1ex;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        min-width: 400px;

        > * {
            padding: 1ex;
        }

        > header {
            font-weight: bold;
            background: rgba(0, 0, 0, 0.2);
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.5);
        }

        > aside {
            text-align: right;
            border-bottom: 1px solid rgba(0, 0, 0, 0.5);
        }

        > section {
            width: 100%;
            box-sizing: border-box;

            > div {
                display: flex;
                align-items: baseline;
                padding: 1ex;
                border-bottom: 1px solid rgba(0, 0, 0, 0.5);

                > label {
                    width: 9em;
                    text-align: right;
                    font-weight: bold;
                    align-self: start;

                    &::after {
                        content: ": ";
                    }
                }

                > aside {
                    white-space: nowrap;
                    width: 8em;
                    align-self: center;
                }

                > .data {
                    flex: 1;
                    margin: 0 1ex;
                    overflow-x: hidden;
                    text-overflow: ellipsis;
                    width: min-content;
                }

                &:last-child {
                    border-bottom: none;
                }
            }
        }

        > aside:empty {
            display: none;
        }
    }
</style>

<article transition:fly="{{ x: 10 }}">
    <header>
        <slot name="header" />
    </header>
    <aside>
        <slot name="subheader" />
    </aside>
    <section>
        {#each data as { fusion, type, label, value, key }, index (uniqueKey(key, type, value))}
            <div transition:slide>
                <label>
                    <slot name="label" label="label">{label}</slot>
                </label>
                <div class="data">
                    <slot name="data" value="value" type="type">
                        <FieldRender type="{type}" value="{value}" />
                    </slot>
                </div>
                <aside>
                    <slot
                        name="buttons"
                        fusion="{fusion}"
                        value="{value}"
                        label="{label}"
                        type="{type}"
                        key="{key}"
                    />
                </aside>
            </div>
        {:else}
            <slot name="empty" />
        {/each}
    </section>
</article>
