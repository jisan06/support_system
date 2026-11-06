import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import fs from 'fs';

// Create a shim that uses the globally loaded Bootstrap (from bootstrap.bundle.min.js)
const bootstrapShimPath = path.resolve(__dirname, 'resources/js/bootstrap-shim.js');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/css/media.css',
                'resources/css/media-picker.css',
                'resources/css/role.css',
                'resources/css/theme-customizer.css',
                'resources/js/app.js',
            ],
            refresh: false,
        }),
    ],
    resolve: {
        alias: {
            'bootstrap': bootstrapShimPath,
        },
    },
    server: {
        watch: {
            ignored: ['**/resources/views/**'],
        },
    },
});
