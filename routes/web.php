<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'CloudERP API',
        'status' => 'running',
        'environment' => app()->environment(),
        'api_version' => 'v1',
        'message' => 'REST API is working successfully'
    ]);
});