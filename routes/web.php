<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CwebCaseController;
use App\Http\Controllers\CwebAuthController;
use App\Http\Controllers\CwebCaseCommentController;
use App\Http\Controllers\CwebMailDraftController;

Route::get('/cases/{case}/mail-draft', [CwebMailDraftController::class, 'open'])
    ->name('cases.mail_draft');

// ルート直下は /ja/cweb に飛ばす（日本語優先）
Route::redirect('/', '/ja/cweb');

/**
 * ★重要：Laravel標準の「login」名前ルートは locale なしで用意する
 * authミドルウェアが未ログイン時に route('login') に飛ばすため
 */
Route::get('/login', function () {
    return redirect('/ja/cweb/login'); // まずは ja 固定でOK（後で改善可）
})->name('login');

// logout も標準名を残すなら locale なしに（必要なければ消してOK）
Route::post('/logout', [CwebAuthController::class, 'logout'])->name('logout');


Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => 'ja|en'],
    'middleware' => 'setlocale',
], function () {

    // CWEBのログイン（※SSOに置き換えるまで仮）
    Route::get('/cweb/login', [CwebAuthController::class, 'showLoginForm'])->name('cweb.login');
    Route::post('/cweb/login', [CwebAuthController::class, 'login'])->name('cweb.login.post');
    Route::post('/cweb/logout', [CwebAuthController::class, 'logout'])->name('cweb.logout');

    // ★ここは削除：{locale}/login を name('login') にしない
    // Route::get('/login', ...)->name('login');
    // Route::post('/login', ...);

    // C-WEB（要ログイン）
    Route::middleware('auth')->prefix('cweb')->name('cweb.')->group(function () {

        Route::get('/', [CwebCaseController::class, 'index'])->name('cases.index');
        Route::get('/cases', [CwebCaseController::class, 'index']);

        Route::get('/cases/create', [CwebCaseController::class, 'create'])->name('cases.create');
        Route::post('/cases', [CwebCaseController::class, 'store'])->name('cases.store');

        Route::get('/cases/{case}', [CwebCaseController::class, 'show'])->name('cases.show');
        Route::get('/cases/{case}/edit', [CwebCaseController::class, 'edit'])->name('cases.edit');
        Route::put('/cases/{case}', [CwebCaseController::class, 'update'])->name('cases.update');

                    Route::get('cases/{case}/abolish', [\App\Http\Controllers\CwebCaseController::class, 'abolishForm'])
                ->name('cases.abolish.form');

            // ★ 廃止：実行（POST）
            Route::post('cases/{case}/abolish', [\App\Http\Controllers\CwebCaseController::class, 'abolish'])
                ->name('cases.abolish');

        Route::post('/cases/{case}/comments', [CwebCaseCommentController::class, 'store'])
            ->name('cases.comments.store');

        Route::get('/products/summary', [CwebCaseController::class, 'productSummary'])
            ->name('products.summary');



    });
});
// 言語切替（/lang/ja or /lang/en）
Route::get('/lang/{lang}', function ($lang) {
    if (!in_array($lang, ['ja', 'en'], true)) abort(404);

    // 直前URLを「/ja/...」→「/en/...」に差し替えて戻す
    $prev = url()->previous();
    $path = parse_url($prev, PHP_URL_PATH) ?? '/';

    // 先頭が /ja or /en なら置換、なければ先頭に付ける
    if (preg_match('#^/(ja|en)(/.*)?$#', $path)) {
        $newPath = preg_replace('#^/(ja|en)#', '/'.$lang, $path);
    } else {
        $newPath = '/'.$lang.$path;
    }

    // query も維持
    $query = parse_url($prev, PHP_URL_QUERY);
    $to = $newPath . ($query ? '?'.$query : '');

    // セッションにも保存（念のため）
    session(['locale' => $lang]);

    return redirect($to);
})->name('lang.switch');

