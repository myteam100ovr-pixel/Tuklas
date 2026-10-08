import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/landing.css',
                'resources/js/landing.js',
                'resources/css/ui.css',
                'resources/css/auth.css',
                'resources/js/ui.js',
            ],
            refresh: true,
        }),
    ],
});
