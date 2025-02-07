<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReservationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//User routes
Route::prefix('user')->group(function() {
    Route::post('/adduser', [UserController::class, 'addUser']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/getusers', [UserController::class, 'getUsers']);
    Route::post('/deleteuser', [UserController::class, 'deleteUser']);
    Route::post('/viewcache', [UserController::class, 'viewCache']);
});

//Reservation routes
Route::prefix('reservation')->group(function() {
    Route::post('/addreservation', [ReservationController::class, 'createReservation']);
});