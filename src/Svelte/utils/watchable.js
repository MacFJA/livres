import { beforeUpdate } from "svelte"
import { readable } from "svelte/store"

export function watchable(valueGetter) {
    return readable(valueGetter(), set => {
        beforeUpdate(() => {
            set(valueGetter())
        })
    })
}