<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * While `vite dev` runs, requests arriving through the dev tunnel domain load
 * Vite's assets from the tunnel's `/vite` path. Every other host (e.g.
 * muraqib.test) keeps using the local dev server from `public/hot` directly.
 */
class UseViteDevTunnel
{
    public function handle(Request $request, Closure $next): Response
    {
        $tunnelUrl = config('app.vite_dev_tunnel_url');

        if (filled($tunnelUrl)
            && Vite::isRunningHot()
            && $request->getHost() === parse_url($tunnelUrl, PHP_URL_HOST)) {
            Vite::useHotFile($this->tunnelHotFile(rtrim($tunnelUrl, '/').'/vite'));
        }

        return $next($request);
    }

    /**
     * A second hot file holding the tunnel URL, rewritten only when it changes.
     */
    protected function tunnelHotFile(string $hotUrl): string
    {
        $path = storage_path('framework/vite-tunnel.hot');

        if (! File::exists($path) || File::get($path) !== $hotUrl) {
            File::put($path, $hotUrl);
        }

        return $path;
    }
}
