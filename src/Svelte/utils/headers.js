export const typeJson = headers => appendHeader(headers, "Content-type", "application/json")
export const typePatchJson = headers => appendHeader(headers, "Content-type", "application/merge-patch+json")
export const headerRawCover = headers => appendHeader(headers, "Livres-Raw-Cover", "1")

const appendHeader = (headers, name, value) => {
    if (headers === undefined) {
        headers = new Headers()
    }
    headers.append(name, value)
    return headers
}