import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/landing.css',
                'resources/js/landing.js',
                'resources/css/ui.css',
                'resources/js/ui.js',
            ],
            refresh: true,
        }),
    ],
});
