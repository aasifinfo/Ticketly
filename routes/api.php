<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScanValidationController;

Route::post('/scan/validate', [ScanValidationController::class, 'validateScan'])
    ->middleware(['scanner.auth'])
    ->name('api.scan.validate');
