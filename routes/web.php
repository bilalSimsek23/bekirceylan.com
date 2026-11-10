<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/turlar', [HomeController::class, 'tours'])->name('tours.index');
Route::get('/turlar/{tour:slug}', [HomeController::class, 'show'])->name('tours.show');
Route::post('/bilgi-talebi', [InquiryController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('inquiries.store');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

// Admin çıktı / yazdırma ekranları (Filament oturumu ile korunur)
Route::middleware(['web', 'auth'])->prefix('admin/cikti')->name('admin.export.')->group(function () {
    Route::get('tur/{tour}/yolcu-listesi', [ExportController::class, 'passengerList'])->name('passengers');
    Route::get('tur/{tour}/yolcu-listesi.csv', [ExportController::class, 'passengerCsv'])->name('passengers.csv');
    Route::get('rezervasyon/{booking}/makbuz', [ExportController::class, 'bookingStatement'])->name('booking');
});
