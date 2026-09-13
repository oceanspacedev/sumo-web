import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/pwa.js',
                'resources/js/scanqr.js',
                'resources/js/productqr.js',
            ],
            refresh: true,
        }),
    ],
});
