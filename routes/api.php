<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeetingController;

Route::post('/meetings', [MeetingController::class, 'store']);
Route::post('/meetings/search', [MeetingController::class, 'search']);
Route::get('/meetings', [MeetingController::class, 'index']);
Route::post('/meetings/check', [MeetingController::class, 'check']);
Route::post('/meetings/get-jadwal', [MeetingController::class, 'getJadwal']);
Route::post('/update-meeting', [MeetingController::class, 'update']);
Route::post('/delete-meeting', [MeetingController::class, 'delete']);
