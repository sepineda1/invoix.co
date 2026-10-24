import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

const inDocker = process.env.VITE_USE_POLLING === 'true';
const dockerServer = inDocker
    ? {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: process.env.VITE_DEV_ORIGIN || 'http://localhost:5173',
        cors: true,
        hmr: { host: 'localhost' },
    }
    : {};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Nunito Sans', {
                    weights: [400, 600, 700, 800],
                }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
        tailwindcss(),
    ],
    server: {
        ...dockerServer,
        watch: {
            ignored: ['**/storage/framework/views/**'],
            usePolling: inDocker,
        },
    },
});
