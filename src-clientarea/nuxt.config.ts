// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
	css: ["bootstrap/dist/css/bootstrap.min.css"],//追加
	app: {
		// baseURL: "/clientarea.php",
	},
	dev: true,
	buildDir: "../clientarea-dist",
	// debug: true,
	ssr: false,
	vite: {
		define: {
			"process.env.DEBUG": true,
		},
		base: "/",
		mode: "development",
		build: {
			ssr: false,
			// 在 outDir 中生成 manifest.json
			manifest: "manifest.json",
			outDir: "vite-dist",
			rollupOptions: {
				// 覆盖默认的 .html 入口
				// input: "main.js"
			},
		},
	},
	router:{
		options:{
			hashMode: true
		}
	},
	nitro: {
		preset: "nitro-dev",
		logLevel: 5,
		// entry: "server.mjs",
	},
});
