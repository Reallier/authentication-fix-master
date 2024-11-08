<link
        rel="modulepreload"
        as="script"
        crossorigin
        href="http://localhost:3000/_nuxt/@vite/client"
/>
<link
        rel="modulepreload"
        as="script"
        crossorigin
        href="http://localhost:3000/_nuxt{$devPath}/src-clientarea/node_modules/.pnpm/nuxt@3.2.0/node_modules/nuxt/dist/app/entry.mjs"
/>
{*<div>*}
{*    {$dirname}*}
{*</div>*}
{*准备虚拟 DOM 节点*}
<div id="__nuxt" class="main-content"/>
{literal}
    <script>
        window.__NUXT__ = {
            serverRendered: false,
            config: {
                public: {},
                app: {
                    baseURL: "/",
                    buildAssetsDir: "/_nuxt/",
                    cdnURL: ""
                }
            },
            data: {},
            state: {}
        };
    </script>
{/literal}
<script type="module" src="http://localhost:3000/_nuxt/@vite/client"></script>
<script type="module"
        src="http://localhost:3000/_nuxt{$devPath}/src-clientarea/node_modules/.pnpm/nuxt@3.2.0/node_modules/nuxt/dist/app/entry.mjs"></script>