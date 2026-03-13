<?php

use App\Http\Controllers\ODSPageController;
use Illuminate\Support\Facades\Route;

// ODS standalone page (server-rendered, not SPA)
Route::get('/observations-ods', [ODSPageController::class, 'index'])->name('ods-observations');

// SPA catch-all: serve the main Vue.js application
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|storage|observations-ods|admin|livewire).*$');
