import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/despensas-index.js',
                'resources/js/listas-index.js',
            ],
            refresh: true,
        }),
    ],
});
