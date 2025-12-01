<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::redirect('/', '/cweb');
use App\Http\Controllers\CwebCaseController;
use App\Http\Controllers\CwebAuthController; // 仮ログイン用（後で作る）

// 仮ログイン（社員番号のみ） ※後でSSOに差し替え
Route::get('/cweb/login', [CwebAuthController::class, 'showLoginForm'])->name('cweb.login');
Route::post('/cweb/login', [CwebAuthController::class, 'login'])->name('cweb.login.post');
Route::post('/cweb/logout', [CwebAuthController::class, 'logout'])->name('cweb.logout');

Route::get('/login', [CwebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [CwebAuthController::class, 'login']);
Route::post('/logout', [CwebAuthController::class, 'logout'])->name('logout');

// C-WEB メイン（要ログイン）
Route::middleware('auth')->prefix('cweb')->name('cweb.')->group(function () {
    Route::get('/', [CwebCaseController::class, 'index'])->name('cases.index');
    Route::get('/cases', [CwebCaseController::class, 'index']);
    Route::get('/cases/create', [CwebCaseController::class, 'create'])->name('cases.create');
    Route::post('/cases', [CwebCaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}', [CwebCaseController::class, 'show'])->name('cases.show');
    Route::get('/cases/{case}/edit', [CwebCaseController::class, 'edit'])->name('cases.edit');
    Route::put('/cases/{case}', [CwebCaseController::class, 'update'])->name('cases.update');
    Route::post('/cases/{case}/abolish', [CwebCaseController::class, 'abolish'])->name('cases.abolish');

    // コメント投稿
    Route::post('/cases/{case}/comments', [CwebCaseController::class, 'storeComment'])->name('cases.comments.store');

    // 製品ごと要求内容一覧
    Route::get('/products/summary', [CwebCaseController::class, 'productSummary'])->name('products.summary');
});

use App\Http\Controllers\CwebCaseCommentController;

Route::middleware('auth')->prefix('cweb')->name('cweb.')->group(function () {
    // 既存の cases.* ルートたち…

    Route::post('/cases/{case}/comments', [CwebCaseCommentController::class, 'store'])
        ->name('cases.comments.store');
});
