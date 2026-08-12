import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  root: 'frontend',
  base: '/wp-content/plugins/buy-one-get-one/assets/build/',
  build: {
    outDir: '../assets/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'frontend/src/main.jsx'),
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
  server: {
    port: 3001,
    strictPort: false,
    cors: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'frontend/src'),
      // Use WordPress's runtime i18n instead of bundling @wordpress/i18n.
      '@wordpress/i18n': path.resolve(__dirname, 'frontend/src/utils/i18n-shim.js'),
    },
  },
});
