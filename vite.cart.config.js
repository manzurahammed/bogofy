import { defineConfig } from 'vite';
import path from 'path';

/**
 * Standalone build for the block cart/checkout script.
 *
 * The WordPress/WooCommerce packages are marked external and mapped to the
 * runtime globals WordPress already prints (wp.element, wp.plugins, wp.i18n,
 * wc.blocksCheckout) — the manual equivalent of
 * @wordpress/dependency-extraction-webpack-plugin. Output is a classic IIFE, so
 * it loads as an ordinary enqueued script alongside those handles.
 */
const externals = {
  '@wordpress/element': 'wp.element',
  '@wordpress/plugins': 'wp.plugins',
  '@wordpress/i18n': 'wp.i18n',
  '@woocommerce/blocks-checkout': 'wc.blocksCheckout',
};

export default defineConfig({
  build: {
    outDir: 'assets/build',
    // Keep the main app build (main.js/main.css) that already ran.
    emptyOutDir: false,
    lib: {
      entry: path.resolve(__dirname, 'frontend/src/cart-blocks/index.js'),
      formats: ['iife'],
      name: 'bogofyCart',
      fileName: () => 'cart-blocks.js',
    },
    rollupOptions: {
      external: Object.keys(externals),
      output: {
        globals: externals,
      },
    },
  },
});
