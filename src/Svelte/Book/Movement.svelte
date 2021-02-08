<script>
    import moment from "moment"
    import { _ } from "svelte-intl"

    import Pagination from "~/Pagination.svelte"
    import Hydra from "~/Pagination/Hydra.svelte"
    import { typeJson, typePatchJson } from "~/utils/headers"

    /**
     * @typedef {Object} Movement
     * @property {string} @id - The API id of the movement
     * @property {string} person - The name of the person
     * @property {string|Date} startAt - When the movement start
     * @property {string|Date|null} endAt - When the movement finish
     */

    /**
     * @type {string} - API id of the book
     * @prop
     */
    export let iriId
    /**
     * @type {Number} - Id of the book
     * @prop
     */
    export let bookId
    /** @type {string} - Name of the person for the new movement */
    let person = ""

    /** @type {Hydra} - The pagination wrapper */
    let hydraPagination
    /**
     * @type {function} - Indicate that a movement is finish
     * @event HTMLButtonElement:click
     * @param {string} movementId - Movement id
     * @param {boolean} [noUpdate=false] - Flag to prevent updating movement
     */
    const isBack = (movementId, noUpdate) => {
        if (noUpdate === undefined) {
            noUpdate = false
        }

        if (!noUpdate) {
            hydraPagination.forceLoading()
        }
        fetch(window.app.url.movementUpdate.replace("__placeholder", movementId), {
            method: "PATCH",
            body: JSON.stringify({"endAt": moment().format()}),
            headers: typePatchJson()
        }).then(() => !noUpdate && updateMovements())
    }

    /**
     * @type {function} - Update the movements list of the current book
     */
    const updateMovements = () => {
        hydraPagination.reload()
    }

    /**
     * @type {function} - Create a new movement (set as finish all unfinished movements)
     */
    const newMovement = () => {
        hydraPagination.forceLoading()

        bookIsBackPromise()
            .then(() => fetch(window.app.url.movementAdd, {
                method: "POST",
                body: JSON.stringify({
                    type: 0,
                    startAt: moment().format(),
                    person: person,
                    book: iriId,
                }),
                headers: typeJson()
            }))
            .then(() => {
                updateMovements()
                person = ""
            })
    }

    /**
     * @type {function} - End all movements
     * @return {Promise<Response>}
     */
    const bookIsBackPromise = () => {
        return fetch(window.app.url.bookIsBack.replace("__placeholder", bookId), {
            method: "PATCH",
            headers: typePatchJson()
        })
    }
</script>

<style lang="scss">
table {
    border: 1px solid rgba(0, 0, 0, 0.2);
    display: table;
    width: calc(100% - 2ex);
    margin: 1em 1ex;
    border-spacing: 0;

    tr td {
        border-collapse: collapse;
        padding: 0.5ex;
        margin: 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.2);
        vertical-align: middle;

        &:nth-child(2),
        &:nth-child(3) {
            text-align: center;
            position: relative;
        }

        &:nth-child(3) {
            padding-left: 1ex;
        }

        &:nth-child(3)::before {
            position: absolute;
            top: 0.5ex;
            left: -0.5ch;
            content: "\2192";
        }

        &.loader {
            text-align: center;
            border-bottom: none;
        }
    }

    thead tr th {
        border-collapse: collapse;
        text-align: center;
        font-weight: bold;
        background: rgba(0, 0, 0, 0.15);
        white-space: nowrap;
    }

    tbody[slot="empty"] tr td {
        padding: 1ex;
        font-size: 1.25rem;
        font-weight: lighter;
        font-style: italic;
    }

    tfoot tr {
        background-color: rgba(0%, 100%, 25%, 0.25);

        /* stylelint-disable no-descending-specificity */
        td {
            border-bottom: none;
            padding: 0;

            div {
                display: flex;
                align-items: center;
            }

            input {
                border: none;
                padding: 1ex;
                font-size: inherit;
                background-color: rgba(100%, 100%, 100%, 0.2);
                box-shadow: inset -2px 0 4px rgba(0%, 0%, 0%, 0.5);
                flex: auto;
                box-sizing: border-box;
            }

            button {
                margin: 0 1ex;
                flex: 1;
            }
        }
        /* stylelint-enable no-descending-specificity */
    }
}

@media (prefers-color-scheme: dark) {
    table {
        border: 1px solid rgba(100%, 100%, 100%, 0.2);

        td {
            border-bottom: 1px solid rgba(100%, 100%, 100%, 0.2);
        }

        thead tr th {
            background: rgba(100%, 100%, 100%, 0.15);
        }
    }
}
</style>

<table>
    <Hydra bind:this={hydraPagination} baseUrl="{window.app.url.bookMovements.replace('__placeholder', bookId)}">
        <thead slot="before">
            <tr>
                <th>{$_("book.movement.who")}</th>
                <th>{$_("book.movement.from_date")}</th>
                <th>{$_("book.movement.to_date")}</th>
            </tr>
        </thead>
        <tr slot="item" let:item>
            <td>{item.person}</td>
            <td>
                {moment(item.startAt).format("LL")}
            </td>
            <td>
                {#if item.endAt != null}
                    {moment(item.endAt).format("LL")}
                {:else if window.app.roles.edit}
                    <button class="color small" on:click={() => isBack(item.movementId)}>{$_("book.movement.return")}</button>
                {:else}
                    <em>{$_("book.movement.not_returned")}</em>
                {/if}
            </td>
        </tr>
        <tbody slot="empty">
            <tr>
                <td colspan="3">{$_("book.movement.empty")}</td>
            </tr>
        </tbody>
        <tfoot slot="after">
            {#if window.app.roles.edit}
                <tr>
                    <td colspan="3">
                        <div>
                            <input bind:value={person}/>
                            <button class="color primary" disabled={person === ""} on:click|preventDefault={newMovement}>{$_("book.movement.new")}</button>
                        </div>
                    </td>
                </tr>
            {/if}
        </tfoot>
        <tr slot="pagination" let:page let:size let:total let:onChange>
            <td colspan="3">
                <Pagination page={page} size={size} total={total} on:change-page={onChange} />
            </td>
        </tr>
    </Hydra>
</table>