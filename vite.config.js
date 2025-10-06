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
                "public/vendor/jquery/jquery.min.js",
                'public/vendor/bootstrap/js/bootstrap.bundle.min.js',
                'public/vendor/jquery-easing/jquery.easing.min.js',
                ...utilEntries,
            ],
            refresh: true,
        }),
    ],
});
