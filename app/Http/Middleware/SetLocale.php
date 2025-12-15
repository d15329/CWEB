<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // 1) URLの {locale} があればそれを最優先
        $routeLocale = $request->route('locale');

        // 2) なければ session → 最後に ja
        $locale = $routeLocale ?: session('locale', 'ja');

        // 3) 不正値は ja に丸める
        if (!in_array($locale, ['ja', 'en'], true)) {
            $locale = 'ja';
        }

        // 4) 適用＆保存
        app()->setLocale($locale);
        session(['locale' => $locale]);

        // 5) ★重要：{locale} ルートの時だけ defaults を効かせる
        //    （これで /lang/{lang} や /login など locale無しルートへ副作用が出にくい）
        if ($routeLocale !== null) {
            URL::defaults(['locale' => $locale]);
        }

        return $next($request);
    }
}
