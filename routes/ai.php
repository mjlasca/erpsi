<?php

use App\Mcp\Servers\QueryServer;
use Laravel\Mcp\Facades\Mcp;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Mcp::oauthRoutes();
});
Mcp::web('/mpcerp', QueryServer::class)->middleware(['auth:api'])->name('mpcerp');