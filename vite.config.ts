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
        },blob/master/vite.config.ts
    },
    ssr: {
        noExternal: true,
    },
    build: {
        rollupOptions: {
            output: {
                // Rollup's default splitting turns every module shared between
                // two pages into its own micro-chunk, producing a waterfall of
                // sub-kilobyte requests. Merge anything below ~100 kB into its
                // importer.
                experimentalMinChunkSize: 100_000,
                // Lucide ships one file per icon; icons shared between pages
                // would otherwise emit as 0.2 kB chunks each.
                manualChunks(id) {
                    if (id.includes('lucide-react')) return 'icons';
                },
            },
        },
    },
});
