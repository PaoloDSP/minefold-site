<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StreamStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/evenements', [EventController::class, 'index'])->name('events.index');
Route::get('/evenements/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/api/stream-status', StreamStatusController::class)->middleware('throttle:60,1')->name('stream.status');

// Anciennes adresses WordPress
Route::get('/lanparty', [EventController::class, 'legacyLan']);
Route::redirect('/calendrier', '/#programmes', 301);
