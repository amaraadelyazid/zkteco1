<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('test', [
    \App\Http\Controllers\API\EmployeeController::class,
    'index'
])->name('test.index');
