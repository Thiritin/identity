import { defineConfig } from 'vite';
import path from 'node:path';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import i18n from 'laravel-vue-i18n/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    isCustomElement: (tag) => tag === 'altcha-widget',
                },
            },
        }),
        tailwindcss(),
        i18n({ langPath: 'resources/lang' }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
