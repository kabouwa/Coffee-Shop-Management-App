<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): JsonResponse
    {
        $period = $request->string('period', 'day')->toString();

        if (! in_array($period, ['day', 'week', 'month'], true)) {
            $period = 'day';
        }

        return response()->json([
            'data' => $dashboard->forUser($request->user(), $period),
        ]);
    }
}
