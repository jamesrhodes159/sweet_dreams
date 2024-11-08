<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StickerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StaffController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return redirect('/login');
// });

Route::get('thankyou', function(){
    return view('thankyou');
});


Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/', function () {
        return view('pages.home.index');
    });

    Route::get('/forbidden', function () {
        return view('pages.forbidden.forbidden_area');
    });

    Route::controller(SettingController::class)->group(function () {
        Route::get('settings', 'index')->name('admin.settings');
        Route::post('setting/store', 'store')->name('admin.settings.store');
    });

    Route::controller(StickerController::class)->group(function () {
        Route::get('stickers', 'index')->name('admin.stickers');
        Route::get('stickers/create', 'create')->name('admin.sticker.create');
        Route::post('sticker/store', 'store')->name('admin.sticker.store');
        Route::get('sticker/status/{id}', 'updateStatus')->name('admin.sticker.status');
    });



    Route::controller(UserController::class)->group(function () {
        Route::get('users', 'index')->name('admin.users');
        // Route::get('users/create', 'create')->name('admin.user.create');
        // Route::post('user/store', 'store')->name('admin.user.store');
        Route::get('user/status/{id}', 'updateStatus')->name('admin.user.status');
        Route::get('user/verification/{id}', 'verifyUser')->name('admin.user.verification');
    });



    Route::controller(StaffController::class)->group(function () {
        Route::get('staffs', 'index')->name('admin.staffs');
        Route::get('staffs/create', 'create')->name('admin.staff.create');
        Route::post('staff/store', 'store')->name('admin.staff.store');
        Route::get('staff/status/{id}', 'updateStatus')->name('admin.staff.status');
    });
});
Route::get('/', function () {
   return redirect()->to('/admin');
});
Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
