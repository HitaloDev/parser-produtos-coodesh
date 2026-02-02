<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiStatusController extends Controller
{
    public function index()
    {
        $dbConnection = $this->checkDatabaseConnection();

        try {
            $lastCronExecution = Cache::get('last_cron_execution', 'Never executed');
        } catch (\Exception $e) {
            $lastCronExecution = 'Cache unavailable';
        }

        return response()->json([
            'status' => 'online',
            'database' => [
                'read' => $dbConnection,
                'write' => $dbConnection,
            ],
            'last_cron_execution' => $lastCronExecution,
            'uptime_seconds' => defined('LARAVEL_START') ? (int) ((microtime(true) - LARAVEL_START)) : 0,
            'memory_usage' => [
                'current' => $this->formatBytes(memory_get_usage(true)),
                'peak' => $this->formatBytes(memory_get_peak_usage(true)),
            ],
        ]);
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
