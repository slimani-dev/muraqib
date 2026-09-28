import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defaultAllowedOrigins, defineConfig, loadEnv } from 'vite';

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    // Optional: a Cloudflare Tunnel domain in front of the local site (e.g.
    // https://muraqib.example.com). The tunnel must forward `/vite/*` straight
    // to this dev server (127.0.0.1:5173) and everything else to Valet/PHP.
    // Both entry points then work at once:
    //  - https://muraqib.test loads assets from 127.0.0.1:5173/vite directly (fast);
    //  - the tunnel domain loads them from its own /vite path, so a public page
    //    never reaches a private address (blocked by Local Network Access).
    // Laravel picks the right URL per request (AppServiceProvider::configureVite).
    // No `origin`/`hmr.host` is pinned, so the HMR client connects back to
    // whichever host served it. Only applies to `vite dev`; builds are unaffected.
    const tunnelOrigin = env.VITE_DEV_TUNNEL_URL?.replace(/\/$/, '');

    const tunnelServer =
        command === 'serve' && tunnelOrigin
            ? {
                  base: '/vite/',
                  server: {
                      port: 5173,
                      strictPort: true,
                      cors: {
                          origin: [
                              defaultAllowedOrigins,
                              tunnelOrigin,
                              ...(env.APP_URL ? [env.APP_URL] : []),
                              /^https?:\/\/.*\.test(:\d+)?$/,
                          ],
                      },
                      // Requests arrive with the tunnel's Host header, which Vite's
                      // DNS-rebinding protection would otherwise reject.
                      allowedHosts: [new URL(tunnelOrigin).hostname],
                  },
              }
            : {};

    return {
        ...(tunnelServer.base ? { base: tunnelServer.base } : {}),
        build: {
            rolldownOptions: {
                onLog(level, log) {
                    if (
                        log.code === 'INVALID_ANNOTATION' &&
                        log.message.includes('@vueuse')
                    ) {
                        return;
                    }
                    console.warn(`[${level}] ${log.message}`);
                },
            },
        },
        server: {
            ...tunnelServer.server,
            watch: {
                ignored: [
                    '**/.agents/**',
                    '**/.claude/**',
                    '**/.junie/**',
                    '**/vendor/**',
                    '**/storage/**',
                ],
            },
        },
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.ts',
                    'resources/css/filament/admin/theme.css',
                ],
                refresh: true,
            }),
            inertia(),
            tailwindcss(),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            wayfinder({
                formVariants: true,
            }),
        ],
    };
});
