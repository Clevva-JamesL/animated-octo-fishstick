import { resolve } from 'node:path'
import { defineConfig } from 'vite'

export default defineConfig({
  // Relative asset paths required for Twitch CDN hosting.
  base: './',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        viewer: resolve(__dirname, 'viewer.html'),
        config: resolve(__dirname, 'config.html'),
        live_config: resolve(__dirname, 'live_config.html'),
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
  },
})
