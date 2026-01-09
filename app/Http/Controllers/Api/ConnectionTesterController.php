<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Portainer\PortainerClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConnectionTesterController extends Controller
{
    public function test(Request $request, PortainerClient $client)
    {
        $request->validate([
            'url' => 'required|url',
            'key' => 'required|string',
        ]);

        try {
            $endpoints = $client->withCredentials(
                $request->input('url'), 
                $request->input('key')
            )->getEndpoints();

            return response()->json([
                'status' => 'success',
                'message' => 'Connection Successful',
                'data' => [
                    'endpoints_count' => count($endpoints),
                    'endpoints' => $endpoints
                ]
            ]);
        } catch (\Exception $e) {
            // We return a 400 or 422 depending on if we want to treat it as validation error
            // or just a failed operation. A 400 Bad Request seems appropriate for "this config doesn't work".
            return response()->json([
                'status' => 'error',
                'message' => 'Connection Failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
