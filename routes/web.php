<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => response()->json(status: 404));
Route::get('mi', fn() => Artisan::call('migrate'));
