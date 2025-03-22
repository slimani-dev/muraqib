// https://nuxt.com/docs/api/configuration/nuxt-config

const PORTAINER_BASE_URL = process.env.PORTAINER_BASE_URL
const PORTAINER_X_API_KEY = process.env.PORTAINER_X_API_KEY
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: {enabled: true},
  modules: [
    '@nuxt/image',
    '@nuxt/ui',
    '@nuxt/test-utils',
    '@nuxt/icon',
    '@nuxt/eslint',
    '@nuxt/content',
    '@pinia/nuxt'
  ],
  vite: {
    build: {
      sourcemap: false, // Disable sourcemaps to prevent warnings
    },
    logLevel: 'info', // Reduce noise in logs
  },
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    PORTAINER_BASE_URL,
    PORTAINER_X_API_KEY,
  },
  nitro: {
    experimental: {
      websocket: true
    }
  }
})