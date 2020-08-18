<script>
    import { slide } from "svelte/transition"
</script>

<style lang="scss">
    @keyframes appearIn {
        0% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }

    g.book {
        animation-name: appearIn;
        animation-duration: 1.2s;
        animation-timing-function: ease;
        animation-iteration-count: infinite;
        animation-direction: alternate;
        animation-fill-mode: none;
        opacity: 0;
    }

    @for $index from 1 through 10 {
        g[data-index="#{$index}"] {
            animation-delay: #{($index - 1) / 10}s;
        }
    }

    div {
        text-align: center;
    }

    svg {
        width: 50%;
        max-height: 70vh;
    }
</style>

<div>
    <!--
    Based on https://www.flaticon.com/free-icon/bookshelf_167708
        ~> Icons made by [Freepik](http://www.freepik.com/) from [www.flaticon.com](https://www.flaticon.com/)
    -->
    <svg
        in:slide
        version="1.1"
        xmlns="http://www.w3.org/2000/svg"
        x="0px"
        y="0px"
        viewBox="0 0 512 512"
        width="50%"
    >
        <g class="book" data-index="1">
            <path
                style="fill: #00c37a;"
                d="M72,132.219H48c-4.418,0-8,3.582-8,8v200h40v-200C80,135.801,76.418,132.219,72,132.219z"
            ></path>
            <path
                style="fill: #00a077;"
                d="M40,340.219h20v-208H46.998c-3.865,0-6.998,3.133-6.998,6.998V340.219z"
            ></path>
        </g>
        <g class="book" data-index="2">
            <path
                style="fill: #ff9100;"
                d="M104,108.219H88c-4.418,0-8,3.582-8,8v223.125c0,0.483,0.392,0.875,0.875,0.875H112v-224
                C112,111.801,108.418,108.219,104,108.219z"
            ></path>
            <path
                style="fill: #ff4f19;"
                d="M80,340.219h16v-232h-8c-4.418,0-8,3.582-8,8V340.219z"
            ></path>
        </g>
        <g class="book" data-index="3">
            <path
                style="fill: #e3001e;"
                d="M168.219,84.219H120c-4.418,0-8,3.582-8,8v248h64.219v-248
                C176.219,87.801,172.637,84.219,168.219,84.219z"
            ></path>
            <path
                style="fill: #ff4f19;"
                d="M168.219,84.219H144v256h32.219v-248C176.219,87.801,172.637,84.219,168.219,84.219z"
            ></path>
            <rect
                x="112"
                y="108.219"
                style="fill: #5d647f;"
                width="32"
                height="16"
            ></rect>
            <rect
                x="144"
                y="108.219"
                style="fill: #868491;"
                width="32"
                height="16"
            ></rect>
            <rect
                x="112"
                y="300.219"
                style="fill: #5d647f;"
                width="32"
                height="16"
            ></rect>
            <rect
                x="144"
                y="300.219"
                style="fill: #868491;"
                width="32"
                height="16"
            ></rect>
        </g>
        <g class="book" data-index="4">
            <path
                style="fill: #4bb9ec;"
                d="M216,100.219h-32c-4.418,0-8,3.582-8,8v232h48v-232C224,103.801,220.418,100.219,216,100.219z"
            ></path>
            <path
                style="fill: #009bca;"
                d="M176,108.219v232h24v-240h-16C179.582,100.219,176,103.801,176,108.219z"
            ></path>
        </g>
        <g class="book" data-index="5">
            <path
                style="fill: #5d647f;"
                d="M248,84.219h-16c-4.418,0-8,3.582-8,8v248h32v-248C256,87.801,252.418,84.219,248,84.219z"
            ></path>
            <path
                style="fill: #5c546a;"
                d="M224,340.219h16v-256h-8c-4.418,0-8,3.582-8,8V340.219z"
            ></path>
        </g>
        <g class="book" data-index="6">
            <path
                style="fill: #00c37a;"
                d="M280,116.219h-16c-4.418,0-8,3.582-8,8v216h32v-216C288,119.801,284.418,116.219,280,116.219z"
            ></path>
            <path
                style="fill: #00a077;"
                d="M256,340.219h16v-224h-8c-4.418,0-8,3.582-8,8V340.219z"
            ></path>
            <rect
                x="256"
                y="140.219"
                style="fill: #5d647f;"
                width="16"
                height="16"
            ></rect>
            <rect
                x="256"
                y="292.219"
                style="fill: #5d647f;"
                width="16"
                height="16"
            ></rect>
            <rect
                x="272.219"
                y="140.219"
                style="fill: #00a077;"
                width="16"
                height="16"
            ></rect>
            <rect
                x="272.219"
                y="292.219"
                style="fill: #00a077;"
                width="16"
                height="16"
            ></rect>
        </g>
        <g class="book" data-index="7">
            <path
                style="fill: #ff9100;"
                d="M336,60.219h-40c-4.418,0-8,3.582-8,8v272h56v-272C344,63.801,340.418,60.219,336,60.219z"
            ></path>
            <path
                style="fill: #ff4f19;"
                d="M288,340.219h28v-280h-20c-4.418,0-8,3.582-8,8V340.219z"
            ></path>
        </g>
        <g class="book" data-index="8">
            <path
                style="fill: #4bb9ec;"
                d="M368,84.219h-16c-4.418,0-8,3.582-8,8v248h32v-248C376,87.801,372.418,84.219,368,84.219z"
            ></path>
            <path
                style="fill: #009bca;"
                d="M360,84.219h-8c-4.418,0-8,3.582-8,8v248h16V84.219z"
            ></path>
        </g>
        <g class="book" data-index="9">
            <path
                style="fill: #ff4f19;"
                d="M424,116.219h-40c-4.418,0-8,3.582-8,8v216h56v-216C432,119.801,428.418,116.219,424,116.219z"
            ></path>
            <path
                style="fill: #e3001e;"
                d="M404,116.219h-20c-4.418,0-8,3.582-8,8v216h28V116.219z"
            ></path>
            <rect
                x="376"
                y="140.219"
                style="fill: #ff9100;"
                width="28"
                height="16"
            ></rect>
            <rect
                x="376"
                y="292.219"
                style="fill: #ff9100;"
                width="28"
                height="16"
            ></rect>
            <rect
                x="404"
                y="140.219"
                style="fill: #ffcd00;"
                width="28"
                height="16"
            ></rect>
            <rect
                x="404"
                y="292.219"
                style="fill: #ffcd00;"
                width="28"
                height="16"
            ></rect>
        </g>
        <g class="book" data-index="10">
            <path
                style="fill: #5d647f;"
                d="M464,100.219h-24c-4.418,0-8,3.582-8,8v232h40v-232C472,103.801,468.418,100.219,464,100.219z"
            ></path>
            <path
                style="fill: #5c546a;"
                d="M452.22,100.219H440c-4.418,0-8,3.582-8,8v232h20.22V100.219z"
            ></path>
        </g>

        <g id="bracket">
            <path
                style="fill: #868491;"
                d="M80,452.219c-2.977,0-5.781-1.668-7.156-4.422l-40-80c-1.977-3.953-0.375-8.758,3.578-10.734
                c3.945-1.969,8.758-0.375,10.734,3.578L72,410.332v-46.113c0-4.418,3.578-8,8-8s8,3.582,8,8v80c0,3.711-2.555,6.934-6.164,7.785
                C81.227,452.149,80.609,452.219,80,452.219z"
            ></path>
            <path
                style="fill: #868491;"
                d="M432.219,452.219c-0.609,0-1.227-0.07-1.836-0.215c-3.609-0.852-6.164-4.074-6.164-7.785v-80
                c0-4.418,3.578-8,8-8s8,3.582,8,8v46.113l24.844-49.691c1.984-3.953,6.781-5.547,10.734-3.578
                c3.953,1.977,5.555,6.781,3.578,10.734l-40,80C438,450.551,435.195,452.219,432.219,452.219z"
            ></path>
        </g>
        <g id="wood">
            <path
                style="fill: #e6be8a;"
                d="M504.438,372.219H8c-4.418,0-8-3.582-8-8v-16c0-4.418,3.582-8,8-8h496.438c4.418,0,8,3.582,8,8v16
                C512.438,368.637,508.856,372.219,504.438,372.219z"
            ></path>
            <path
                style="fill: #c9a07c;"
                d="M0,356.219v8c0,4.418,3.582,8,8,8h496.438c4.418,0,8-3.582,8-8v-8H0z"
            ></path>
        </g>
    </svg>
</div>
