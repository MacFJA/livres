<script>
    import { draw, blur } from "svelte/transition"

    /**
     * @type {string} - The error title
     * @prop
     */
    export let title
    /**
     * @type {string} - The error description
     * @prop
     */
    export let reason = ""
</script>

<style>
    svg {
        display: block;
        width: 210px;
        height: 210px;
        margin: 1rem auto;
    }

    p {
        text-align: center;
    }

    .lead {
        font-size: 1.2rem;
        margin-top: 3em;
    }

    .details {
        font-size: 0.8rem;
        opacity: 0.7;
        text-align: justify;
        white-space: pre-line;
    }
</style>

<svg xmlns="http://www.w3.org/2000/svg">
    <!-- Created with Method Draw - http://github.com/duopixel/Method-Draw/ -->
    <ellipse
        in:blur="{{ duration: 1200 }}"
        fill="none"
        stroke="#F00"
        stroke-width="10"
        cx="105"
        cy="105"
        rx="100"
        ry="100"
    ></ellipse>
    <path
        in:draw
        fill="none"
        stroke-width="10"
        d="m105,35l60,110l-120,0l60,-110l0,70"
        stroke="#F80"
        stroke-linecap="round"
    ></path>
    <ellipse
        in:draw
        fill="none"
        stroke="#F80"
        stroke-width="8"
        stroke-opacity="null"
        fill-opacity="null"
        cx="105"
        cy="125"
        rx="8"
        ry="8"
    ></ellipse>
</svg>
<p class="lead">{title}</p>
<p class="details">{reason}</p>
