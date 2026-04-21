<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeetingController;

Route::post('/meetings', [MeetingController::class, 'store']);
Route::post('/meetings/search', [MeetingController::class, 'search']);
Route::get('/meetings', [MeetingController::class, 'index']);
Route::post('/meetings/check', [MeetingController::class, 'check']);
Route::post('/update-meeting', [MeetingController::class, 'update']);
Route::post('/delete-meeting', [MeetingController::class, 'delete']);

Route::get('/zoom-accounts', [MeetingController::class, 'getZoomAccounts']);
