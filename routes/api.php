<?php

use App\Http\Controllers\API\UnitTypeController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    ##USER
    Route::get('user', [UserController::class, 'fetch']);

    ##UNIT TYPE
    Route::get('unittype', [UnitTypeController::class, 'fetch']);
    Route::post('unittype', [UnitTypeController::class, 'add']);
    Route::patch('unittype/{id}', [UnitTypeController::class, 'edit']);
    Route::delete('unittype/{unitType}', [UnitTypeController::class, 'delete']);
});