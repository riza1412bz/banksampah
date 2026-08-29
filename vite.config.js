import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/setoran-baru.js',
                'resources/js/pages/setoran-index.js',
                'resources/js/pages/kalkulator.js',
                'resources/js/pages/alamat-terstruktur.js',
                'resources/js/pages/jadwal.js',
                'resources/js/pages/harga.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    optimizedFallbacks: false,
                    display: 'swap',
                    preload: true,
                }),
                bunny('Bricolage Grotesque', {
                    weights: [600, 700],
                    optimizedFallbacks: false,
                    display: 'swap',
                    preload: false,
                }),
                bunny('Roboto Mono', {
                    weights: [400, 500],
                    optimizedFallbacks: false,
                    display: 'swap',
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
