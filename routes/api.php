<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ZoomController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/list-meetings', [ZoomController::class, 'listMeetings']);
    Route::post('/create-meeting', [ZoomController::class, 'createMeeting']);
    Route::post('/meetings/{meetingId}/data', [ZoomController::class, 'getMeetingData']);
   // Route::get('/meetings/{meetingId}/details', [ZoomController::class, 'getMeetingDetails']);
  //  Route::post('/meetings/{meetingId}/signature', [ZoomController::class, 'getSignature']);
    Route::post('/logout', [AuthController::class, 'logout']);
});