import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/Auth/js/app.js',
                'resources/User/js/app.js',
                'resources/Staff/js/app.js',
                'resources/Admin/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@Shared': '/resources/Shared/js',
            '@Auth': '/resources/Auth/js',
            '@User': '/resources/User/js',
            '@Staff': '/resources/Staff/js',
            '@Admin': '/resources/Admin/js',
        },
    },
});
