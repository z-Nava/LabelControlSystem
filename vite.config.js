import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/pages/label-room-dashboard.js',
                'resources/js/pages/master-requests-create.js',
                'resources/js/pages/master-requests-show.js',
                'resources/js/pages/master-print-create.js',
                'resources/js/pages/master-print-template.js',
                'resources/js/pages/oracle-jobs-import.js',
                'resources/js/pages/label-requests-create.js',
                'resources/js/pages/label-print-center.js',
                'resources/js/pages/label-reworks-show.js',
                'resources/js/pages/sku-template-configurations-form.js',
                'resources/js/pages/dummy-requests-create.js',
                'resources/js/pages/dummy-requests-show.js',
                'resources/js/pages/dummy-qr-templates-create.js',
                'resources/js/pages/dummy-print-center.js',
                'resources/js/pages/dummy-reprints-show.js',
                'resources/img/favicon.png',
                'resources/img/logoIndex.png',
            ],
            assets: ['resources/img/**'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
