<link rel="modulepreload" as="script" crossorigin
      href="{$assetsUrl}/clientarea-dist/dist/client/_nuxt/{$mainJSFile}">
<link rel="preload" as="style"
      href="{$assetsUrl}/clientarea-dist/dist/client/_nuxt/{$mainCSSFile}">
<link rel="stylesheet" href="{$assetsUrl}/clientarea-dist/dist/client/_nuxt/{$mainCSSFile}">
<div id="__nuxt" class="col-md-9 pull-md-right main-content"></div>
{literal}
    <script>
        window.__NUXT__ = {
            serverRendered: false,
            config: {
                public: {},
                app: {
                    baseURL: "/",
                    {/literal}
                    buildAssetsDir: "{$assetsUrl}/clientarea-dist/dist/client/_nuxt/",
                    {literal}
                    cdnURL: ""
                }
            },
            data: {},
            state: {}
        };
    </script>
{/literal}
<script type="module" src="{$assetsUrl}/clientarea-dist/dist/client/_nuxt/{$mainJSFile}" crossorigin></script>



