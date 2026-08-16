import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.tsx',
                'resources/css/app.css',
                'resources/js/embed.ts',
                'resources/css/embed.css',
            ],
            ssr: 'resources/js/ssr.tsx',
            // The legacy gulp pipeline still owns public/build until the
            // embed widget is ported; Vite gets its own directory.
            buildDirectory: 'assets',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
});
