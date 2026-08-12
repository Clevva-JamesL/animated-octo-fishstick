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
    // ngrok dials 127.0.0.1; Vite otherwise binds IPv6-only (::1) on macOS.
    host: '127.0.0.1',
    // Allow tunnel Host headers (Vite blocks unknown hosts by default).
    allowedHosts: [
      '.ngrok-free.dev',
      '.ngrok-free.app',
      '.ngrok.io',
      '.trycloudflare.com',
    ],
    // Free ngrok = one domain. Proxy API so one tunnel covers Vite + Laravel.
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/up': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
