<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Netdata;
use App\Services\NetdataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetdataApiController extends Controller
{
    public function index(Request $request, NetdataService $netdataService): JsonResponse
    {
        $timeframes = $request->query('timeframes', []);

        $servers = Netdata::with(['access', 'ingressRule'])
            ->where('status', 'active')
            ->get();

        $data = $servers->map(function ($server) use ($netdataService, $timeframes) {
            $timeframe = $timeframes[$server->id] ?? '1h';
            $stats = $netdataService->getStats($server, $timeframe);

            return [
                'id' => $server->id,
                'name' => $server->name,
                'status' => $server->status,
                'type' => $server->type ?? 'Server', // if we have type in future
                'stats' => $stats,
            ];
        });

        return response()->json([
            'servers' => $data,
        ]);
    }
}
