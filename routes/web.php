<?php

use App\Http\Controllers\HomeownerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Upload'));
Route::post('/parse', [HomeownerController::class, 'parse'])->name('parse');
