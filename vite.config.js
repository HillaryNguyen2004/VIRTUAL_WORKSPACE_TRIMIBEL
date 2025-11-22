import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import fg from "fast-glob";

const utilEntries = fg.sync("resources/utils/**/*.js");

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                // "resources/js/vendor/jquery.js",
                // "resources/js/vendor/bootstrap.js",
                // "resources/js/vendor/jquery-easing.js",
                ...utilEntries,
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
            protocol: 'ws'
        },
        cors: {
            origin: [
                'https://unperceptible-genevie-surmisedly.ngrok-free.dev',
                'https://do-an-chuyen-nganh-rho.vercel.app',
                'http://localhost:8000',
                'http://127.0.0.1:8000'
            ],
            credentials: true
        }
    },
});