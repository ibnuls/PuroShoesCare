<?php

use App\Http\Controllers\DeliveryProofController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\ReportController;
use App\Livewire\Booking;
use App\Livewire\Dashboard;
use App\Livewire\Management;
use App\Livewire\PublicPage;
use App\Livewire\StaffLogin;
use App\Livewire\Tracking;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPage::class)->name('home');
foreach (['services' => 'layanan', 'locations' => 'lokasi', 'about' => 'tentang', 'contact' => 'kontak', 'guide' => 'panduan'] as $page => $url) {
    Route::get('/'.$url, PublicPage::class)->defaults('page', $page)->name($page);
}
Route::get('/book', Booking::class)->name('book');
Route::get('/book/{code}/checkout', Tracking::class)->defaults('stage', 'checkout')->where('code', '[A-Za-z0-9-]{1,40}')->middleware('throttle:60,1')->name('book.checkout');
Route::get('/book/{code}/selesai', Tracking::class)->defaults('stage', 'success')->where('code', '[A-Za-z0-9-]{1,40}')->middleware('throttle:60,1')->name('book.success');
Route::get('/panduan.pdf', GuideController::class)->middleware('throttle:20,1')->name('guide.pdf');
Route::get('/track/{code}/delivery-proof', DeliveryProofController::class)
    ->where('code', '[A-Za-z0-9-]{1,40}')
    ->middleware('throttle:60,1')
    ->name('track.delivery-proof');
Route::get('/track/{code?}', Tracking::class)->where('code', '[A-Za-z0-9-]{1,40}')->middleware('throttle:60,1')->name('track');
Route::get('/poster/{tariff}', PosterController::class)->name('poster');
Route::get('/staff/login', StaffLogin::class)->middleware('guest')->name('login');
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/staff/logout', LogoutController::class)->name('logout');
    Route::get('/staff', Dashboard::class)->name('staff.dashboard');
    Route::get('/staff/orders/{id}', Dashboard::class)->whereNumber('id')->name('staff.order');
    Route::get('/staff/manage/{tab}', Management::class)->where('tab', 'catalog|branches|qris|accounts|permissions|promos|reports|knowledge')->name('staff.manage');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.private');
    Route::get('/staff/export/orders', ReportController::class)->name('staff.export');
    Route::get('/staff/export/orders.pdf', [ReportController::class, 'pdf'])->name('staff.export.pdf');
});
