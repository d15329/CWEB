@extends('cweb.layout')

{{-- 🔹 ヘッダーは header セクションに --}}
@section('header')
<header class="cweb-header">
    <div class="cweb-header-inner">
<div class="cweb-header-left">
    <a href="{{ route('cweb.cases.index') }}" class="cweb-brand-link">
    C-WEB
</a>

    <a href="{{ route('cweb.cases.create') }}" class="btn btn-accent">
        新規登録
    </a>
</div>
<div class="cweb-header-right">
    <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>

    {{-- 言語トグル：日本語 / EN を 1 つにまとめる --}}
    <div class="cweb-header-lang">
        <button type="button"
                class="cweb-header-lang-toggle"
                data-lang="ja-en">
            日本語 / EN
        </button>
    </div>

    @auth
        {{-- ユーザー名も押したら選択状態が分かるように --}}
        <button type="button" class="cweb-header-user-toggle">
            {{ auth()->user()->name }}
        </button>
    @endauth
</div>
    </div>
</header>

<script>
function showSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');

    if (!overlay || !modal) return;

    // Dimmer を Semantic 風に表示
    overlay.classList.add('visible', 'active');
    overlay.style.display = 'flex';
    overlay.style.opacity = '1';

    // モーダルを中央に表示（create と同じ）
    modal.classList.add('visible', 'active');
    modal.style.display = 'block';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}

function closeSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');

    if (!overlay || !modal) return;

    overlay.classList.remove('visible', 'active');
    overlay.style.opacity = '0';
    overlay.style.display = 'none';

    modal.classList.remove('visible', 'active');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
}

// セッション ok があるときだけ自動表示
@if (session('ok'))
document.addEventListener('DOMContentLoaded', function () {
    showSuccessModal();
 const langButtons = document.querySelectorAll('.cweb-header-lang-toggle');
    if (langButtons.length) {
        langButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // 一旦全部 OFF
                langButtons.forEach(b => b.classList.remove('is-active'));
                // 押されたボタンだけ ON
                btn.classList.add('is-active');

                // TODO: 実際の言語切替処理はここに書く（将来対応）
                // const lang = btn.dataset.lang; // 'ja' or 'en'
            });
        });
    }

    // ▼ ユーザー名：押すと ON/OFF が分かるようにトグル
    const userToggle = document.querySelector('.cweb-header-user-toggle');
    if (userToggle) {
        userToggle.addEventListener('click', () => {
            userToggle.classList.toggle('is-active');

            // TODO: 将来的にはこのタイミングで
            // ユーザーメニューのドロップダウンを開くなど
        });
    }
});
@endif

function showSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');

    if (!overlay || !modal) return;

    overlay.classList.add('visible', 'active');
    overlay.style.display = 'flex';
    overlay.style.opacity = '1';

    modal.classList.add('visible', 'active');
    modal.style.display = 'block';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}

function closeSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');

    if (!overlay || !modal) return;

    overlay.classList.remove('visible', 'active');
    overlay.style.opacity = '0';
    overlay.style.display = 'none';

    modal.classList.remove('visible', 'active');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
}

// セッション ok があるときだけ自動表示
@if (session('ok'))
document.addEventListener('DOMContentLoaded', function () {
    showSuccessModal();
});
@endif

// ▼ ヘッダーの「▼」で絞り込みメニューを開閉
document.addEventListener('click', function (e) {
    const toggle = e.target.closest('.cweb-filter-toggle');
    if (toggle) {
        const targetId = toggle.dataset.target;
        const menus = document.querySelectorAll('.cweb-filter-menu');

        menus.forEach(m => {
            if (m.id !== targetId) {
                m.classList.remove('is-open');
            }
        });

        const menu = document.getElementById(targetId);
        if (menu) {
            menu.classList.toggle('is-open');
        }
        return;
    }

    // メニューの外をクリックしたら閉じる
    if (!e.target.closest('.cweb-filter-menu')) {
        document.querySelectorAll('.cweb-filter-menu').forEach(m => {
            m.classList.remove('is-open');
        });
    }
});



</script>
@endsection





@section('content')


{{-- タブ切り替え --}}
@php
    // コントローラから渡ってきた tab を前提にタイトルを切り替え
    $pageTitle = match ($tab ?? 'all') {
        'mine'    => 'あなたが関わる案件',
        'product' => '製品ごとの要求内容一覧',
        default   => 'すべての案件',
    };
@endphp

<div class="cweb-tabs">
    <a href="{{ route('cweb.cases.index', ['tab' => 'all']) }}"
       class="cweb-tab-link {{ $tab === 'all' ? 'is-active' : '' }}">
        すべて
    </a>

    <a href="{{ route('cweb.cases.index', ['tab' => 'mine']) }}"
       class="cweb-tab-link {{ $tab === 'mine' ? 'is-active' : '' }}">
        あなたが関わる案件
    </a>

    <a href="{{ route('cweb.cases.index', ['tab' => 'product']) }}"
       class="cweb-tab-link {{ $tab === 'product' ? 'is-active' : '' }}">
        製品ごとの要求内容一覧
    </a>
</div>


{{-- ④ 検索ボックス + ボタン行 --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin:12px 0 16px;">
    <form method="GET"
          action="{{ route('cweb.cases.index') }}"
          style="display:flex;align-items:center;flex:0 0 auto; max-width:260px; width:100%;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <input type="text"
               name="keyword"
               value="{{ request('keyword') }}"
               placeholder="Search…"
               style="flex:1 1 auto;
                      padding:10px;
                      border-radius:6px;
                      border:1px solid #9ca3af;
                      box-sizing:border-box;">

<button type="submit"
        style="margin-left:8px;
               padding:8px 18px;
               min-width:64px;
               white-space:nowrap;
               flex:0 0 auto;
               border-radius:6px;
               border:none;
               cursor:pointer;
               background:#2563eb;
               color:#fff;
               font-weight:600;
               font-size:13px;">
    検索
</button>
    </form>

    <button type="button"
    onclick="openCategoryImage()"
            style="margin-left:16px;
                   padding:8px 14px;
                   border-radius:8px;
                   border:none;
                   cursor:pointer;
                   background:linear-gradient(90deg,#1a237e,#7030a0);
                   color:#fff;font-weight:600;font-size:13px;">
        カテゴリーの定義及び管理費紹介
    </button>
</div>



{{-- ⑥ テーブル：タイトル行だけ濃いグレー枠で囲う --}}
{{-- 先頭でソート情報を取得しておく --}}
@php
    $tab       = $tab ?? 'all';
    $sort      = request('sort');
    $direction = request('direction', 'asc');
    $toggleDir = $direction === 'asc' ? 'desc' : 'asc';
@endphp

<div style="background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
        <tr style="background:#f3f4f6;">
            {{-- 管理番号（ソート付） --}}
<th style="padding:8px 10px;text-align:center;font-weight:700;
           border:1px solid #9ca3af;">
    管理番号
</th>

 <th style="padding:8px 10px;text-align:center;font-weight:700;
               border:1px solid #9ca3af;">
        <div class="cweb-filter-wrap">
            <span>ステータス</span>
            <button type="button"
                    class="cweb-filter-toggle"
                    data-target="status-filter-menu">
                ▼
            </button>

            <div id="status-filter-menu" class="cweb-filter-menu">
                <form method="GET" action="{{ route('cweb.cases.index') }}">
                    {{-- 既存条件を維持 --}}
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                    <input type="hidden" name="category" value="{{ request('category') }}">

                    <div style="margin-bottom:6px;font-size:12px;">絞り込み条件</div>
                    <select name="status"
                            style="width:100%;padding:4px 6px;font-size:12px;">
                        <option value="">（すべて）</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>アクティブ</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>廃止</option>
                    </select>

                    <div style="margin-top:8px;text-align:right;font-size:12px;">
                        <button type="submit"
                                style="padding:3px 8px;border-radius:4px;border:none;background:#2563eb;color:#fff;cursor:pointer;">
                            絞り込み
                        </button>
                        <a href="{{ route('cweb.cases.index', array_merge(request()->except(['status','page']), ['tab' => $tab])) }}"
                           style="margin-left:6px;font-size:11px;color:#6b7280;text-decoration:underline;">
                            解除
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </th>

            {{-- カテゴリー（ソート付） --}}
 {{-- カテゴリー（▼で絞り込み） --}}
    <th style="padding:8px 10px;text-align:center;font-weight:700;
               border:1px solid #9ca3af;">
        <div class="cweb-filter-wrap">
            <span>カテゴリー</span>
            <button type="button"
                    class="cweb-filter-toggle"
                    data-target="category-filter-menu">
                ▼
            </button>

            <div id="category-filter-menu" class="cweb-filter-menu">
                <form method="GET" action="{{ route('cweb.cases.index') }}">
                    {{-- 既存条件を維持 --}}
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">

                    <div style="margin-bottom:6px;font-size:12px;">絞り込み条件</div>
                    <select name="category"
                            style="width:100%;padding:4px 6px;font-size:12px;">
                        <option value="">（すべて）</option>
                        <option value="standard" {{ request('category') === 'standard' ? 'selected' : '' }}>標準管理</option>
                        <option value="pcn"      {{ request('category') === 'pcn' ? 'selected' : '' }}>PCN</option>
                        <option value="other"    {{ request('category') === 'other' ? 'selected' : '' }}>その他要求</option>
                    </select>

                    <div style="margin-top:8px;text-align:right;font-size:12px;">
                        <button type="submit"
                                style="padding:3px 8px;border-radius:4px;border:none;background:#2563eb;color:#fff;cursor:pointer;">
                            絞り込み
                        </button>
                        <a href="{{ route('cweb.cases.index', array_merge(request()->except(['category','page']), ['tab' => $tab])) }}"
                           style="margin-left:6px;font-size:11px;color:#6b7280;text-decoration:underline;">
                            解除
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </th>
<th style="padding:8px 10px;text-align:center;font-weight:700;
           border:1px solid #9ca3af;">
    <div class="cweb-filter-wrap">
        <span>対象製品</span>
        <button type="button"
                class="cweb-filter-toggle"
                data-target="product-filter-menu">
            ▼
        </button>

        <div id="product-filter-menu" class="cweb-filter-menu">
            <form method="GET" action="{{ route('cweb.cases.index') }}">
                {{-- 既存条件を維持 --}}
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="category" value="{{ request('category') }}">

                <div style="margin-bottom:6px;font-size:12px;">対象製品</div>
                <select name="product_group"
                        style="width:100%;padding:4px 6px;font-size:12px;margin-bottom:8px;">
                    <option value="">（すべて）</option>
                    @foreach ($productGroups as $group)
                        <option value="{{ $group }}"
                            {{ request('product_group') === $group ? 'selected' : '' }}>
                            {{ $group }}
                        </option>
                    @endforeach
                </select>

                <div style="margin-bottom:6px;font-size:12px;">詳細カテゴリ ※任意</div>
                <select name="product_code"
                        style="width:100%;padding:4px 6px;font-size:12px;">
                    <option value="">（すべて）</option>
                    @foreach ($productCodes as $code)
                        <option value="{{ $code }}"
                            {{ request('product_code') === $code ? 'selected' : '' }}>
                            {{ $code }}
                        </option>
                    @endforeach
                </select>

                <div style="margin-top:8px;text-align:right;font-size:12px;">
                    <button type="submit"
                            style="padding:3px 8px;border-radius:4px;border:none;background:#2563eb;color:#fff;cursor:pointer;">
                        絞り込み
                    </button>
                    <a href="{{ route('cweb.cases.index', array_merge(
                            request()->except(['product_group','product_code','page']),
                            ['tab' => $tab]
                        )) }}"
                       style="margin-left:6px;font-size:11px;color:#6b7280;text-decoration:underline;">
                        解除
                    </a>
                </div>
            </form>
        </div>
    </div>
</th>
            <th style="padding:8px 10px;text-align:center;font-weight:700;
                       border:1px solid #9ca3af;">
                顧客名
            </th>
            <th style="padding:8px 10px;text-align:center;font-weight:700;
                       border:1px solid #9ca3af;">
                営業窓口
            </th>
            <th style="padding:8px 10px;text-align:center;font-weight:700;
                       border:1px solid #9ca3af;">
                費用負担
            </th>
            <th style="padding:8px 10px;text-align:center;font-weight:700;
                       border:1px solid #9ca3af;">
                月額費用
            </th>
        </tr>
        </thead>

<tbody>
@forelse($cases as $case)
    @php
        // ▼ カテゴリー表示（booleanフラグ3つから生成）
        $categories = [];
        if ($case->category_standard) {
            $categories[] = '標準管理';
        }
        if ($case->category_pcn) {
            $categories[] = 'PCN';
        }
        if ($case->category_other) {
            $categories[] = 'その他要求';
        }
        $categoryLabel = $categories ? implode(' / ', $categories) : '-';

        // ▼ ステータス表示
        $statusLabel = match($case->status ?? '') {
            'active'  => 'アクティブ',
            'closed'  => '廃止',
            default   => '不明',
        };

        // ▼ 製品グループ + 製品コード
        $productLabel = trim(($case->product_group ?? '').' '.($case->product_code ?? ''));
    @endphp
    <tr>
        {{-- 管理番号 --}}
        <td style="padding:6px 10px;color:#2563eb;font-weight:700;text-align:center;">
            <a href="{{ route('cweb.cases.show', ['case' => $case->id]) }}"
               style="color:#2563eb;text-decoration:none;">
                {{ $case->manage_no }}
            </a>
        </td>

        {{-- ステータス --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            {{ $statusLabel }}
        </td>

        {{-- カテゴリ --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            {{ $categoryLabel }}
        </td>

        {{-- 製品情報（グループ＋コード） --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            {{ $productLabel ?: '-' }}
        </td>

        {{-- 顧客名 --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            {{ $case->customer_name }}
        </td>

        {{-- 営業担当社員番号 --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            @if($case->sales_contact_employee_number)
                {{ $case->sales_contact_employee_number }}
                @if(!empty($case->sales_contact_employee_name))
                    / {{ $case->sales_contact_employee_name }}
                @endif
            @else
                -
            @endif
        </td>

        {{-- コスト負担コード --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            {{ $case->cost_responsible_code ?? '' }}
        </td>

        {{-- 月額Will金額 --}}
        <td style="padding:6px 10px;color:#111827;text-align:center;">
            {{ $case->will_monthly_cost ? number_format($case->will_monthly_cost) : '-' }}
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" style="padding:10px 10px;color:#6b7280;text-align:center;">
            まだ案件がありません。
        </td>
    </tr>
@endforelse
</tbody>
    </table>
</div>
 {{-- ▼ ページネーション：1ページ15件ずつ --}}
@if($cases->hasPages())
    <div class="cweb-pagination-wrapper">
        <div class="ui center aligned floated pagination menu cweb-pagination-menu" role="navigation">

            {{-- 前へ --}}
            @if ($cases->onFirstPage())
                <span class="icon item disabled" aria-disabled="true" aria-label="« Previous">
                    ‹
                </span>
            @else
                <a class="icon item"
                   href="{{ $cases->previousPageUrl() }}"
                   rel="prev"
                   aria-label="« Previous">
                    ‹
                </a>
            @endif

            @php
                $current = $cases->currentPage();
                $last    = $cases->lastPage();

                // 現在ページの前後2ページ分を表示
                $start = max(1, $current - 2);
                $end   = min($last, $current + 2);
            @endphp

            {{-- 先頭側（1 ...） --}}
            @if ($start > 1)
                <a class="item" href="{{ $cases->url(1) }}">1</a>
                @if ($start > 2)
                    <span class="icon item disabled">...</span>
                @endif
            @endif

            {{-- 中央のページ番号 --}}
            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $current)
                    <span class="item active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="item" href="{{ $cases->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            {{-- 末尾側（... n-1 n） --}}
            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="icon item disabled">...</span>
                @endif
                <a class="item" href="{{ $cases->url($last-1) }}">{{ $last-1 }}</a>
                <a class="item" href="{{ $cases->url($last) }}">{{ $last }}</a>
            @endif

            {{-- 次へ --}}
            @if ($cases->hasMorePages())
                <a class="icon item"
                   href="{{ $cases->nextPageUrl() }}"
                   rel="next"
                   aria-label="Next »">
                    ›
                </a>
            @else
                <span class="icon item disabled" aria-disabled="true" aria-label="Next »">
                    ›
                </span>
            @endif

        </div>
    </div>
@endif

{{-- 完了ポップアップ（メインページ用） --}}
<div id="success-modal-overlay" class="ui dimmer" style="display:none;"></div>

<div id="success-modal"
     class="ui small modal"
     style="
        display:block;
        opacity:0;
        pointer-events:none;
        max-width:280px;            /* ★ 横幅を小さく */
        margin:0 auto;              /* 中央寄せ */
        border-radius:12px;         /* 丸み */
     ">
    
    {{-- ★ ヘッダー（完了）削除済み --}}
    
    <div class="content"
         style="
            text-align:center;
            font-size:15px;
            padding:20px 16px;
            border-bottom:none;     /* ★ 仕切り線削除 */
         ">
        {{ session('ok') }}
    </div>

    <div class="actions"
         style="
            text-align:center;
            padding-bottom:16px;
            border-top:none;        /* ★ 仕切り線削除 */
         ">

        {{-- ★ ボタンを緑に統一（今のC-WEBと同じ感じ） --}}
        <button type="button"
                class="ui green button"
                style="
                    background:#22c55e;   /* 緑（C-WEBのフォルダボタン色） */
                    color:#fff;
                    padding:8px 28px;
                    font-weight:700;
                    border-radius:999px;
                    border:none;
                    box-shadow:0 4px 8px rgba(0,0,0,0.25);
                "
                onclick="closeSuccessModal()">
            OK
        </button>

    </div>
</div>

<style>
/* ===== モーダル本体（create と同じ） ===== */
.ui.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    opacity: 0;
    display: block;
    pointer-events: none;
    z-index: 1001;

    text-align: left;
    background: #fff;
    border: none;
    box-shadow:
        1px 3px 3px 0 rgba(0, 0, 0, .2),
        1px 3px 15px 2px rgba(0, 0, 0, .2);
    flex: 0 0 auto;
    border-radius: .28571429rem;
    user-select: text;
    outline: 0;
    font-size: 1rem;
    padding: 1.2rem 1.3rem 1rem;
    box-sizing: border-box;

    transition: transform .22s ease-out, opacity .22s ease-out;
    will-change: transform, opacity;
}

/* サイズ：large / small 共通（create と同じ） */
@media only screen and (min-width: 768px) {
    .ui.modal:not(.fullscreen),
    .ui.large.modal {
        width: 88%;
        margin: 0;
        max-width: 900px;
    }
}

/* 表示状態（visible + active） */
.ui.modal.visible.active {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
    pointer-events: auto;
}

/* スクロールコンテンツ */
.ui.modal > .scrolling.content {
    max-height: calc(80vh - 110px);
    overflow-y: auto;
}

/* ヘッダー・フッター */
.ui.modal > .header {
    font-weight: 700;
    margin-bottom: .75rem;
}
.ui.modal > .actions {
    margin-top: 1rem;
    padding-top: .75rem;
    border-top: 1px solid rgba(34, 36, 38, .15);
    text-align: right;
}

/* Dimmer（背景の黒オーバーレイ） */
.ui.dimmer {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    text-align: center;
    vertical-align: middle;
    padding: 1em;
    background: rgba(0, 0, 0, .85);
    opacity: 0;
    line-height: 1;
    transition: all .5s linear;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    user-select: none;
    will-change: opacity;
    z-index: 1000;
}
/* 表示状態 */
.ui.dimmer.visible.active {
    display: flex;
    opacity: 1;
}

/* ボタン（create と同じ Semantic 風） */
.ui.button {
    display: inline-block;
    min-height: 0;
    padding: .7em 1.6em;
    margin-left: .4em;
    font-size: .95rem;
    font-weight: 700;
    border-radius: .28571429rem;
    border: none;
    background: #e0e1e2;
    color: rgba(0, 0, 0, .6);
    cursor: pointer;
    line-height: 1em;
}
.ui.button:hover {
    background: #cacbcd;
    color: rgba(0, 0, 0, .8);
}

/* OK ボタン（緑） */
.ui.positive.button {
    background: #21ba45;
    color: #fff;
}
.ui.positive.button:hover {
    background: #16ab39;
    color: #fff;
}

/* ▼ 絞り込みメニュー */
.cweb-filter-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.cweb-filter-toggle {
    border: none;
    background: transparent;
    font-size: 10px;
    cursor: pointer;
    padding: 0 2px;
    line-height: 1;
    color: #374151;
}

.cweb-filter-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 4px;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 8px 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .15);
    z-index: 50;
    min-width: 160px;
    display: none;
}

.cweb-filter-menu.is-open {
    display: block;
}

@media (prefers-color-scheme: dark) {
    .cweb-filter-toggle {
        color: #e5e7eb;
    }
    .cweb-filter-menu {
        background: #111827;
        border-color: #4b5563;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .6);
    }
    .cweb-filter-menu select {
        background:#111827;
        color:#e5e7eb;
        border:1px solid #4b5563;
    }
}

/* ▼ ページネーションを表の外＋中央に */
.cweb-pagination-wrapper {
    margin-top: 16px;              /* 表との間隔 */
    display: flex;
    justify-content: center;       /* 中央寄せ */
}

/* ▼ ページネーションを表の外＋中央に（重複だが現状維持） */
.cweb-pagination-wrapper {
    margin-top: 16px;              /* 表との間隔 */
    display: flex;
    justify-content: center;       /* 横方向中央寄せ */
}

/* Q-WEB 風の枠だけど、位置は wrapper まかせにする */
.cweb-pagination-menu.ui.menu {
    display: inline-flex;
    margin: 0;
    background: #fff;
    border: 1px solid rgba(34, 36, 38, .15);
    box-shadow: 0 1px 2px 0 rgba(34, 36, 38, .15);
    border-radius: .28571429rem;
    min-height: 2.85714286em;
    font-size: 1rem;
    font-family: Lato, system-ui, -apple-system, "Segoe UI", Roboto, Oxygen,
                 Ubuntu, Cantarell, "Helvetica Neue", Arial, "Noto Sans",
                 "Liberation Sans", sans-serif, "Apple Color Emoji",
                 "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
}

/* item 基本 */
.cweb-pagination-menu .item {
    padding: .5em .8em;
    cursor: pointer;
    border-left: 1px solid rgba(34, 36, 38, .15);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 最初の item */
.cweb-pagination-menu .item:first-child {
    border-left: none;
}

/* active */
.cweb-pagination-menu .item.active {
    background: #2185d0;
    color: #fff;
    font-weight: 700;
}

/* disabled */
.cweb-pagination-menu .item.disabled {
    opacity: .4;
    cursor: default;
}

/* hover */
.cweb-pagination-menu .item:not(.active):not(.disabled):hover {
    background: rgba(0, 0, 0, .03);
}

/* ダークモード */
@media (prefers-color-scheme: dark) {
    .cweb-pagination-menu.ui.menu {
        background: #111827;
        border-color: #4b5563;
        box-shadow: 0 1px 3px rgba(0,0,0,.6);
        color: #e5e7eb;
    }
    .cweb-pagination-menu .item {
        border-left-color: #4b5563;
    }
    .cweb-pagination-menu .item.active {
        background: #2563eb;
        color: #fff;
    }
    .cweb-pagination-menu .item:not(.active):not(.disabled):hover {
        background: rgba(255,255,255,.06);
    }
}

/* ===========================
   ▼ ヘッダー（日本語/EN・ユーザー名）
   =========================== */

/* 右側のまとまり（Q-WEB・言語・ユーザー名） */
.cweb-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #e5e7eb;  /* 文字色は元の薄い明るい色 */
}

/* 言語ブロック（「日本語 / EN」） */
.cweb-header-lang {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-left: 8px;
    padding-left: 12px;  /* 左端の縦線ぶんスペース */
}

/* 言語ブロック左に、ヘッダー帯を縦に割る線を引く */
.cweb-header-lang::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;     /* 少し上下にはみ出させて「がっつり」見せる */
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* 日本語 / EN ボタン（1つにまとめたもの） */
.cweb-header-lang-toggle {
    position: relative;
    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    padding: 0 6px;
    line-height: 1.4;
    transition:
        color .15s ease,
        transform .04s ease;
}

/* ユーザー名ボタン */
.cweb-header-user-toggle {
    position: relative;
    margin-left: 8px;
    padding-left: 12px;  /* 左端の縦線ぶんスペース */
    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    line-height: 1.4;
    transition:
        color .15s ease,
        transform .04s ease;
}

/* ユーザー名の左にも、ヘッダー帯を縦に割る線 */
.cweb-header-user-toggle::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* ホバー時：少しだけ明るく */
.cweb-header-lang-toggle:hover,
.cweb-header-user-toggle:hover {
    color: #ffffff;
}

/* クリック時：ちょっと縮む */
.cweb-header-lang-toggle:active,
.cweb-header-user-toggle:active {
    transform: scale(0.97);
}

/* ▼ 反射エフェクト（日本語 / EN とユーザー名共通） */
.cweb-header-lang-toggle::after,
.cweb-header-user-toggle::after {
    content: "";
    position: absolute;
    top: 0;
    left: -120%;
    width: 60%;
    height: 100%;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(255, 255, 255, 0.6),
        transparent
    );
    transform: skewX(-20deg);
    opacity: 0;
    pointer-events: none;
}

/* JS で付けるクラス：反射アニメーションを起動 */
.cweb-header-lang-toggle.is-hover-reflect::after,
.cweb-header-user-toggle.is-hover-reflect::after {
    animation: cweb-header-reflect 0.6s linear;
}

@keyframes cweb-header-reflect {
    0% {
        left: -120%;
        opacity: 0;
    }
    20% {
        opacity: 1;
    }
    100% {
        left: 130%;
        opacity: 0;
    }
}

/* ===========================
   ▼ ヘッダー（日本語/EN・ユーザー名）
   =========================== */

/* 右側のまとまり（Q-WEB・言語・ユーザー名） */
.cweb-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #e5e7eb;  /* ベースの文字色 */
}

/* 言語ブロック（「日本語 / EN」） */
.cweb-header-lang {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-left: 8px;
    padding-left: 12px;  /* 左端の縦線ぶんスペース */
}

/* 言語ブロック左に、ヘッダー帯を縦に割る線を引く */
.cweb-header-lang::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;     /* 少し上下にはみ出させて「がっつり」見せる */
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* 日本語 / EN ボタン（1つにまとめたもの） */
.cweb-header-lang-toggle {
    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    padding: 0 6px;
    line-height: 1.4;
    opacity: 0.75;                  /* ちょい薄め */
    transition:
        opacity .15s ease,
        background-color .15s ease,
        transform .04s ease;
}

/* ユーザー名ボタン */
.cweb-header-user-toggle {
    position: relative;
    margin-left: 8px;
    padding-left: 12px;             /* 左端の縦線ぶんスペース */
    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    line-height: 1.4;
    opacity: 0.75;                  /* ちょい薄め */
    transition:
        opacity .15s ease,
        background-color .15s ease,
        transform .04s ease;
}

/* ユーザー名の左にも、ヘッダー帯を縦に割る線 */
.cweb-header-user-toggle::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* ホバー時：色が濃く・少しだけ背景を足す */
.cweb-header-lang-toggle:hover,
.cweb-header-user-toggle:hover {
    opacity: 1;
    background-color: rgba(255, 255, 255, 0.06);  /* うっすら */
}

/* クリック時：ちょっと縮む */
.cweb-header-lang-toggle:active,
.cweb-header-user-toggle:active {
    transform: scale(0.97);
}

/* ダークモード：線を少し濃くするだけで色味はほぼ同じ */
@media (prefers-color-scheme: dark) {
    .cweb-header-right {
        color: #e5e7eb;
    }
    .cweb-header-lang::before,
    .cweb-header-user-toggle::before {
        background: rgba(75, 85, 99, 0.8);
    }
}


</style>

@endsection

