import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

// Shared alias so components can use `@/…` imports in either build.
const srcAlias = { '@': path.resolve(__dirname, 'frontend/src') };

/**
 * The block cart/checkout script is built separately (`vite build --mode cart`)
 * because it needs a different output shape than the admin app: a self-contained
 * IIFE with the WordPress/WooCommerce packages externalized to their runtime
 * globals — versus the app's ESM + code-splitting + bundled deps.
 */
const cartExternals = {
  '@wordpress/element': 'wp.element',
  '@wordpress/plugins': 'wp.plugins',
  '@wordpress/i18n': 'wp.i18n',
  '@woocommerce/blocks-checkout': 'wc.blocksCheckout',
};

const cartConfig = {
  build: {
    outDir: 'assets/build',
    // Keep the admin app build (main.js/main.css) that already ran.
    emptyOutDir: false,
    lib: {
      entry: path.resolve(__dirname, 'frontend/src/cart-blocks/index.js'),
      formats: ['iife'],
      name: 'bogofyCart',
      fileName: () => 'cart-blocks.js',
    },
    rollupOptions: {
      external: Object.keys(cartExternals),
      output: { globals: cartExternals },
    },
  },
  resolve: { alias: { ...srcAlias } },
};

const appConfig = {
  plugins: [react()],
  root: 'frontend',
  base: '/wp-content/plugins/bogofy/assets/build/',
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
  resolve: {
    alias: {
      ...srcAlias,
      // Use WordPress's runtime i18n instead of bundling @wordpress/i18n.
      '@wordpress/i18n': path.resolve(__dirname, 'frontend/src/utils/i18n-shim.js'),
    },
  },
};

export default defineConfig(({ mode }) => (mode === 'cart' ? cartConfig : appConfig));
