import { locale, translations, getBrowserLocale } from "svelte-intl"

import App from "./App"
import Loader from "./Loader.svelte"
import "../../assets/app.css"
import "tippy.js/themes/light.css"
import "tippy.js/dist/tippy.css"

translations.update(window.app.languages.reduce((result, item) => {
    result[item] = []
    return result
}, {}))
let browserLocale = getBrowserLocale("en")
fetch(window.app.url.translations.replace("__placeholder", browserLocale))
    .then(response => response.json())
    .then(data => {
        let trans = {}
        trans[browserLocale] = data
        translations.update(trans)
        locale.set(browserLocale)

        new App({
            target: document.querySelector("#app-root"),
        })
        loader.$destroy()
    })
let loader = new Loader({
    target: document.querySelector("#app-root"),
})