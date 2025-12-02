@extends('cweb.layout')

@section('header')
<header class="cweb-header">
    <div class="cweb-header-inner">

        <div class="cweb-header-left">
            <a href="{{ route('cweb.cases.index') }}" class="cweb-brand-link">
                C-WEB
            </a>

            <!-- 管理番号はそのまま残す場合 -->
            <div style="font-weight:700;margin-left:12px;">
                {{ $nextManagementNo }}
            </div>
        </div>

        <div class="cweb-header-right">
            <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>
            <span style="margin:0 12px;">日本語 / EN</span>
            @auth
                <span>{{ auth()->user()->name }}</span>
            @endauth
        </div>

    </div>
</header>
@endsection

@section('content')

<style>
/* Will分配の名前表示を必ず var(--text) で */
[id^="will-emp-display-"] {
    color: var(--text) !important;
    font-weight: 700;
}


.cweb-search-group{
    margin: 0;        /* ← これが重要 */
    padding: 0;       /* 必要なら */
    display: flex;
    align-items: center;
    gap: 8px; /* input と ボタンの間の余白 */
}

.cweb-search-group input{
    padding: .5em .75em;
    border: 1px solid rgba(34,36,38,.15);
    border-radius: .285rem;
    font-size: 1em;
}

/* 大きめで目立つ検索ボタン */
.cweb-search-btn{
    background: #2185d0;
    color: #fff;
    border: none;

    /* ▼ 横幅を少しだけ小さく */
    padding: .55em 0.7em;

    border-radius: .285rem;
    cursor: pointer;

    /* ▼ 文字を完全中央に配置 */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 1px;

    font-size: 0.8em;
    font-weight: 600;
}

.cweb-search-btn:hover{
    background: #1678c2;
}

/* =========================
   モーダル本体（.ui.modal 系）
   ========================= */

/* =========================
   モーダル本体（.ui.modal 系）
   ========================= */

.ui.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);  /* ★ 最初は少し小さく */
    opacity: 0;                                    /* ★ 最初は透明 */

    /* ★ display は常に block にしておく */
    display: block;
    pointer-events: none;                          /* ★ 非表示時はクリックさせない */

    z-index: 1001;

    text-align: left;
    background: #fff;
    border: none;
    box-shadow:
        1px 3px 3px 0 rgba(0, 0, 0, .2),
        1px 3px 15px 2px rgba(0, 0, 0, .2);
    flex: 0 0 auto;
    border-radius: .28571429rem;
    -webkit-user-select: text;
    -moz-user-select: text;
    user-select: text;
    outline: 0;
    font-size: 1rem;
    padding: 1.2rem 1.3rem 1rem;
    box-sizing: border-box;

    /* ★ アニメーション */
    transition: transform .22s ease-out, opacity .22s ease-out;
    will-change: transform, opacity;
}


/* サイズ：large ＋ 通常モーダルの幅 */
@media only screen and (min-width: 768px) {
    .ui.modal:not(.fullscreen),
    .ui.large.modal {
        width: 88%;
        margin: 0;
        max-width: 900px;  /* 好きな最大幅にしてOK */
    }
}

/* 開いているとき（visible + active が付いた状態） */
/* 開いているとき（visible + active が付いた状態） */
.ui.modal.visible.active {
    /* display はそのまま block のまま */
    transform: translate(-50%, -50%) scale(1);  /* ★ 通常サイズ */
    opacity: 1;                                 /* ★ 不透明に */
    pointer-events: auto;                       /* ★ クリック可能に */
}

/* モーダル内のスクロール領域 */
.ui.modal > .scrolling.content {
    max-height: calc(80vh - 110px);
    overflow-y: auto;
}

/* ヘッダーとフッター */
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
/* モーダルタイトル下の青い横線 */
.ui.modal > .header.title_boader{
    font-size: 30px;                /* ★タイトルを少し大きく */
    font-weight: 700;
    color: #1b1c1d;
    margin-bottom: .75rem;
    padding-bottom: .4rem;
    border-bottom: 2px solid #2185d0;  /* ★青線：色と太さをここに統一 */
}
/* SearchResult / Selected のラベル下の青い横線 */
/* SearchResult / Selected ラベル下の青線 */
.emp_l_s{
    height: 1px;               /* ★タイトルと同じ太さ */
    background: #2185d0;       /* ★同じ青 */
    margin: .2rem 0 .6rem;
}

.scrolling.content > *:first-child{
    margin-top: 0 !important;
}

/* =========================
   Dimmer（背景の黒いオーバーレイ）
   ========================= */

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
    animation-fill-mode: both;
    animation-duration: .5s;
    transition: all .5s linear;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    will-change: opacity;
    z-index: 1000;
}

/* 表示状態 */
.ui.dimmer.visible.active {
    display: flex;
    opacity: 1;
}

/* =========================
   ボタン（.ui.button 系）
   ========================= */

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

/* OK（.positive）＝ 緑ボタン */
.ui.positive.button {
    background: #21ba45;
    color: #fff;
}
.ui.positive.button:hover {
    background: #16ab39;
    color: #fff;
}

/* Cancel はデフォのグレーのまま */
.ui.button.cancel {
    /* 必要ならここで別色指定も可能 */
}

/* =========================
   入力ボックス（.ui.icon.input）まわり
   ========================= */

/* アイコン付き入力全体（これは今のままでOK） */
.ui.icon.input{
    position: relative;
    display: inline-block;
}

.ui.icon.input > input{
    padding-right: 2.4em;
    border: 1px solid rgba(34,36,38,.15);
    border-radius: .28571429rem;
    font-size: 1em;
    line-height: 1.21428571em;
    padding: .5em .75em;
}

/* ▼ 青い丸ボタンを明示的に復活させる */
.ui.icon.input > i.inverted.circular.search.link.icon.blue{
    position: absolute;              /* input の中の右端に固定 */
    right: .6em;
    top: 50%;
    transform: translateY(-50%);

    background: #2185d0;             /* 青い背景（Semantic UI の青） */
    border-radius: 999px;            /* 丸ボタンにする */

    width: 1.8em;
    height: 1.8em;

    display: flex;
    align-items: center;
    justify-content: center;

    color: transparent;              /* 元のフォントアイコンは見えなくしておく */
}

/* ▼ 白い円（ルーペ部分） */
.ui.icon.input > i.inverted.circular.search.link.icon.blue::before{
    content: "";
    display: block;
    width: 11px;                     /* 少し小さめの円 */
    height: 11px;
    border: 2px solid #fff;          /* 白い線の円 */
    border-radius: 50%;
}

/* ▼ 棒（円の外側右下にくっつく） */
.ui.icon.input > i.inverted.circular.search.link.icon.blue::after{
    content: "";
    position: absolute;
    width: 7px;                      /* 長めの棒 */
    height: 2px;
    background: #fff;
    border-radius: 1px;

    /* 円の外側右下にくっつくような位置調整 */
    right: 4px;
    bottom: 4px;
    transform-origin: left center;
    transform: rotate(45deg);
}




/* =========================
   Grid（.ui.two.column.grid）ざっくり
   ========================= */

.ui.grid {
    display: flex;
    flex-direction: column;
    margin-top: 1rem;
}
.ui.grid .row {
    display: flex;
    width: 100%;
}
.ui.grid .column {
    flex: 1 0 0;
    padding-right: 1rem;
}
.ui.grid .column:last-child {
    padding-right: 0;
}

    /* 送信バー（前回のまま使う想定） */

    /* ★ ヘッダーと登録ボタンの隙間をなくす調整 */
.cweb-header{
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

    form{
        margin-top:0;
         padding-bottom: 0
    }
.cweb-submit-bar{
    position: fixed;          /* ← ここを fixed にして完全固定 */
    top: 45px;                /* ← ヘッダーの高さに合わせて調整（必要なら 56px とかに変更） */
    left: 0;
    right: 0;

    z-index: 45;              /* ヘッダーより少し低く / コンテンツよりは高く */
    background: var(--bg);    /* 画面の背景と同じ色でなじませる */
    padding: 8px 24px;        /* コンテンツ左右の余白に合わせて調整 */

    display: flex;
    justify-content: flex-start;
    align-items: center;
}
    .cweb-submit-button{
        background:#f97316;
        color:#fff;
        border:none;
        padding:10px 32px;
        border-radius:999px;
        font-weight:700;
        font-size:14px;
        box-shadow:0 4px 8px rgba(0,0,0,0.35);
        cursor:pointer;
    }
    .cweb-submit-button:hover{
        opacity:0.9;
        transform:translateY(-1px);
    }
    .cweb-submit-button:active{
        transform:translateY(0);
        box-shadow:0 2px 4px rgba(0,0,0,0.25);
    }


    .scrolling.content{
        overflow-y: auto;
        max-height: calc(80vh - 110px); /* ヘッダー/ボタン分を引いておくイメージ */
        padding-top: 0 !important;
    }

    /* ヘッダーの文字等（既存の .header.title_boader はそのまま使う前提） */
    .cweb-modal-inner .header{
        font-weight: 700;
        margin-bottom: .75rem;
    }

    /* ====== フッター（OK / Cancel ボタン行） ====== */

    .cweb-modal-inner .actions{
        margin-top: 1rem;
        padding-top: .75rem;
        border-top: 1px solid rgba(34,36,38,.15);
        text-align: right;
    }

    /* Semantic UI の .ui.button 風 */
    .cweb-modal-inner .actions .ui.button{
        display: inline-block;
        min-height: 0;
        padding: .6em 1.1em;
        margin-left: .4em;
        font-size: .85714286rem;
        font-weight: 700;
        border-radius: .28571429rem;
        border: none;
        background: #e0e1e2;
        color: rgba(0,0,0,.6);
        cursor: pointer;
        line-height: 1em;
    }
    .cweb-modal-inner .actions .ui.button:hover{
        background: #cacbcd;
    }

    /* OKボタン（.positive） → 緑 */
    .cweb-modal-inner .actions .ui.positive.button{
        background: #21ba45;
        color: #fff;
    }
    .cweb-modal-inner .actions .ui.positive.button:hover{
        background: #16ab39;
        color:#fff;
    }

    /* Cancel はグレーのまま（必要なら .cancel に別色も付けられる） */
    .cweb-modal-inner .actions .ui.button.cancel{
        /* デフォのグレーでよければ何も書かなくてOK */
    }
    @media only screen and (min-width: 768px){
        /* .ui.large.modal / .ui.modal:not(.fullscreen) の width:88% に寄せる */
        .cweb-modal-inner{
            width: 88%;
            max-width: none;
            margin: 0;
        }
    }

    .picker-title{
        font-size:16px;
        font-weight:700;
        color:var(--text);
        padding-bottom:6px;
        border-bottom:2px solid #3b82f6; /* 青線 */
        margin-bottom:4px;
    }
    .picker-search-row{
        display:flex;
        align-items:center;
        gap:8px;
        margin-bottom:4px;
    }
    .picker-search-input{
        flex:1;
        padding:6px 8px;
        border-radius:4px;
        border:1px solid #9ca3af;
        font-size:12px;
    }
    .picker-search-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:6px 10px;
        border-radius:4px;
        border:none;
        background:#0ea5e9;
        color:#fff;
        cursor:pointer;
        font-size:12px;
        font-weight:600;
        min-width:34px;
    }
    .picker-search-btn-icon{
        width:14px;
        height:14px;
        border-radius:999px;
        border:2px solid #fff;
        position:relative;
    }
    .picker-search-btn-icon::after{
        content:"";
        position:absolute;
        width:7px;
        height:2px;
        border-radius:999px;
        background:#fff;
        right:-5px;
        bottom:-2px;
        transform:rotate(35deg);
    }

    .picker-header-row{
        display:flex;
        gap:12px;
        font-size:12px;
        font-weight:700;
        color:var(--text);
        margin-top:4px;
    }
    .picker-col-header{
        flex:1;
    }
    .picker-body-row{
        display:flex;
        gap:12px;
        flex:1;
        min-height:180px;
        max-height:45vh;
    }
    .picker-list{
        flex:1;
        border:1px solid #e5e7eb;
        border-radius:4px;
        overflow:auto;
        background:var(--bg);
        font-size:12px;
    }
    .picker-item{
        padding:4px 8px;
        border-bottom:1px solid #e5e7eb;
        cursor:pointer;
        display:flex;
        justify-content:space-between;
        gap:6px;
    }
    .picker-item:hover{
        background:rgba(37,99,235,0.08);
    }
    .picker-item-main{
        font-weight:700;
        color:var(--text);
    }
    .picker-item-sub{
        font-size:11px;
        color:#6b7280;
    }

    .picker-footer{
        display:flex;
        justify-content:flex-end;
        gap:8px;
        margin-top:6px;
    }
    .picker-btn-ok{
        background:#16a34a;
        color:#fff;
        border:none;
        padding:6px 18px;
        border-radius:999px;
        font-weight:700;
        font-size:12px;
        cursor:pointer;
    }
    .picker-btn-cancel{
        background:#e5e7eb;
        color:#374151;
        border:none;
        padding:6px 18px;
        border-radius:999px;
        font-weight:700;
        font-size:12px;
        cursor:pointer;
    }
</style>


    <form method="POST" action="{{ route('cweb.cases.store') }}">
        @csrf

        {{-- 登録ボタン（スクロールしても上に固定） --}}
        <div class="cweb-submit-bar">
            <button type="submit"
                    class="btn btn-accent cweb-submit-button">
                登録
            </button>
        </div>

        {{-- 1列11行テーブル --}}
        <div style="margin-top:60px;background:#0b1029;border-radius:8px;padding:0;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
@php
    $rowStyle = '';

    // 1列目：幅半分（10%）、真ん中寄せ、左に空白、仕切りはグレー
        $labelCell = implode('', [
            'padding:10px 10px 10px 32px;',
            'width:18%;',
            'vertical-align:middle;',
            'color:#000;',
            'background:#e5e7eb;',
            'border-right:1px solid #d1d5db;',
            'border-bottom:none;',     // ← ★ここを none にする
            'box-sizing:border-box;',
            'font-weight:700;',
        ]);


    // 2列目：ボディ背景と同じ色、下線なし、縦方向も中央寄せ
    $inputCell = 'padding:10px 10px;background:var(--bg);border-bottom:none;vertical-align:middle;';
@endphp



{{-- 1行目：営業窓口（必須） --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">
        <span style="color:red;">＊</span>営業窓口
    </td>
    <td style="{{ $inputCell }}">

@php
    $salesNo   = old('sales_employee_number');
    $salesName = old('sales_employee_name');
@endphp

<span id="sales-emp-display"
      style="display:{{ ($salesNo || $salesName) ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
    @if($salesNo || $salesName)
        {{ $salesNo }} / {{ $salesName }}
    @endif
</span>

        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

            {{-- 保存用フィールド --}}
            <input type="hidden"
                   name="sales_employee_number"
                   id="sales-emp-no"
                   value="{{ old('sales_employee_number') }}">
            <input type="hidden"
                   name="sales_employee_name"
                   id="sales-emp-name"
                   value="{{ old('sales_employee_name') }}">

            <!-- {{-- ▼ 選択済みの表示（未選択なら完全に非表示＝幅0） --}}
            <span id="sales-emp-display"
                  style="display:{{ $salesName ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
                {{ $salesName }}
            </span> -->

            {{-- ▼ 選択ボタン（最初は左端にぴったり） --}}
            <button type="button"
                    class="btn"
                    style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                    onclick="openPopupA()">
                選択
            </button>
        </div>

        @error('sales_employee_number')
            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
        @enderror
    </td>
</tr>


                {{-- 2行目：情報共有者 --}}
                <tr style="{{ $rowStyle }}">
                    <td style="{{ $labelCell }}">情報共有者</td>
                    <td style="{{ $inputCell }}">
                        <div id="shared-hidden-container">
                            @foreach((array)old('shared_employee_numbers', []) as $empNo)
                                <input type="hidden" name="shared_employee_numbers[]" value="{{ $empNo }}">
                            @endforeach
                        </div>

                        <div id="shared-display" style="margin-bottom:4px;">
                            @foreach((array)old('shared_employee_labels', []) as $label)
                                <span style="display:inline-block;background:#1f2937;color:#e5e7eb;padding:2px 6px;border-radius:999px;font-size:11px;margin-right:4px;margin-bottom:2px;">
                                    {{ $label }}
                                </span>
                            @endforeach
                        </div>

                        <button type="button"
                                class="btn"
                                style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                                onclick="openPopupB()">
                            選択
                        </button>

                        <div style="margin-top:4px;font-size:11px;color:var(--text);">
                            各製品の技術/製造担当は自動で情報共有されます
                        </div>
                    </td>
                </tr>

{{-- 3行目：費用負担先（必須） --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">
        <span style="color:red;">＊</span>費用負担先
    </td>
    <td style="{{ $inputCell }}">

        @php
            $costOwnerName = old('cost_owner_name');
        @endphp

        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

            {{-- 保存用フィールド --}}
            <input type="hidden"
                   name="cost_owner_code"
                   id="cost-owner-code"
                   value="{{ old('cost_owner_code') }}">
            <input type="hidden"
                   name="cost_owner_name"
                   id="cost-owner-name"
                   value="{{ old('cost_owner_name') }}">

            {{-- ▼ 選択済みの表示（未選択なら完全に非表示＝幅0になる） --}}
            <span id="cost-owner-display"
                  style="display:{{ $costOwnerName ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
                {{ $costOwnerName }}
            </span>

            {{-- ▼ 選択ボタン（最初は左端にぴったり） --}}
            <button type="button"
                    class="btn"
                    style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                    onclick="openPopupC()">
                選択
            </button>
        </div>

        @error('cost_owner_code')
            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
        @enderror
    </td>
</tr>


                {{-- 4行目：顧客名（必須） --}}
                <tr style="{{ $rowStyle }}">
                    <td style="{{ $labelCell }}">
                        <span style="color:red;">＊</span>顧客名
                    </td>
                    <td style="{{ $inputCell }}">
                        <input type="text"
                               name="customer_name"
                               value="{{ old('customer_name') }}"
                               style="width:220px;padding:6px 8px;border-radius:4px;border:1px solid #9ca3af;">
                        @error('customer_name')
                            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>

                {{-- 5行目：カテゴリー（必須・複数可） --}}
                <tr style="{{ $rowStyle }}">
                    <td style="{{ $labelCell }}">
                        <span style="color:red;">＊</span>カテゴリー
                    </td>
                    <td style="{{ $inputCell }}">
                        @php
                            $oldCategories = (array)old('categories', []);
                        @endphp
                        <label style="color:var(--text);">
                            <input type="checkbox" name="categories[]" value="standard"
                                   {{ in_array('standard', $oldCategories, true) ? 'checked' : '' }}>
                            標準管理
                        </label>
                        <label style="margin-left:12px;color:var(--text);">
                            <input type="checkbox" name="categories[]" value="pcn"
                                   {{ in_array('pcn', $oldCategories, true) ? 'checked' : '' }}>
                            PCN
                        </label>
                        <label style="margin-left:12px;ccolor:var(--text);">
                            <input type="checkbox" name="categories[]" value="other"
                                   {{ in_array('other', $oldCategories, true) ? 'checked' : '' }}>
                            その他要求
                        </label>

                        @error('categories')
                            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>

                {{-- 6行目：対象製品（必須・プルダウン2つ） --}}
                <tr style="{{ $rowStyle }}">
                    <td style="{{ $labelCell }}">
                        <span style="color:red;">＊</span>対象製品
                    </td>
                    <td style="{{ $inputCell }}">
                        @php
                            $oldMain = old('product_main', '');
                            $oldSub  = old('product_sub', '');
                        @endphp

                        <select name="product_main"
                                id="product-main"
                                style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                            <option value="" {{ $oldMain === '' ? 'selected' : '' }} style="color:#9ca3af;">
                                選択
                            </option>
                            <option value="HogoMax-内製品"   {{ $oldMain === 'HogoMax-内製品' ? 'selected' : '' }}>HogoMax-内製品</option>
                            <option value="HogoMax-OEM品"    {{ $oldMain === 'HogoMax-OEM品' ? 'selected' : '' }}>HogoMax-OEM品</option>
                            <option value="StayClean-内製品" {{ $oldMain === 'StayClean-内製品' ? 'selected' : '' }}>StayClean-内製品</option>
                            <option value="StayClean-OEM品"  {{ $oldMain === 'StayClean-OEM品' ? 'selected' : '' }}>StayClean-OEM品</option>
                            <option value="ResiFlat内製品"   {{ $oldMain === 'ResiFlat内製品' ? 'selected' : '' }}>ResiFlat内製品</option>
                            <option value="その他"           {{ $oldMain === 'その他' ? 'selected' : '' }}>その他</option>
                        </select>

                        <select name="product_sub"
                                id="product-sub"
                                style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;margin-left:8px;">
                            <option value="" {{ $oldSub === '' ? 'selected' : '' }} style="color:#9ca3af;">
                                選択
                            </option>
                            {{-- JSで候補差し込み --}}
                        </select>

                        @error('product_main')
                            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                        @enderror
                        @error('product_sub')
                            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>

                {{-- 7行目：PCN管理項目 --}}
                <tr style="{{ $rowStyle }}">
                    <td style="{{ $labelCell }}">PCN管理項目</td>
                    <td style="{{ $inputCell }}">
                        <div id="pcn-rows">
                            @php
                                $pcnOld = old('pcn_items', [
                                    ['category' => null, 'title' => null, 'months_before' => null],
                                ]);
                            @endphp

                            @foreach($pcnOld as $i => $item)
                                <div class="pcn-row" style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                                    <select name="pcn_items[{{ $i }}][category]"
                                            style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                                        <option value="">選択</option>
                                        <option value="spec"        {{ ($item['category'] ?? '') === 'spec' ? 'selected' : '' }}>仕様書内容</option>
                                        <option value="man"         {{ ($item['category'] ?? '') === 'man' ? 'selected' : '' }}>人（Man）</option>
                                        <option value="machine"     {{ ($item['category'] ?? '') === 'machine' ? 'selected' : '' }}>機械（Machine）</option>
                                        <option value="material"    {{ ($item['category'] ?? '') === 'material' ? 'selected' : '' }}>材料（Material）</option>
                                        <option value="method"      {{ ($item['category'] ?? '') === 'method' ? 'selected' : '' }}>方法（Method）</option>
                                        <option value="measurement" {{ ($item['category'] ?? '') === 'measurement' ? 'selected' : '' }}>測定（Measurement）</option>
                                        <option value="environment" {{ ($item['category'] ?? '') === 'environment' ? 'selected' : '' }}>環境（Environment）</option>
                                        <option value="other"       {{ ($item['category'] ?? '') === 'other' ? 'selected' : '' }}>その他</option>
                                    </select>

                                    <input type="text"
                                           name="pcn_items[{{ $i }}][title]"
                                           value="{{ $item['title'] ?? '' }}"
                                           placeholder="ラベル変更など"
                                           style="width:200px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">

                                    <input type="number"
                                           name="pcn_items[{{ $i }}][months_before]"
                                           value="{{ $item['months_before'] ?? '' }}"
                                           min="0"
                                           style="width:50px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                                    <span style="color:var(--text);">ヵ月前連絡</span>

                                    <button type="button"
                                            onclick="removePcnRow(this)"
                                            style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                                        削除
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <button type="button"
                                onclick="addPcnRow()"
                                style="margin-top:4px;background:#b91c1c;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:12px;">
                            ＋ 行追加
                        </button>
                    </td>
                </tr>

{{-- 8行目：その他要求 --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">その他要求</td>
    <td style="{{ $inputCell }}">
        <div id="other-rows">
            @php
                $otherOld = old('other_requirements', [
                    ['content' => null, 'responsible_employee_number' => null, 'responsible_label' => null],
                ]);
            @endphp

            @foreach($otherOld as $i => $row)
                @php
                    $respLabel = $row['responsible_label'] ?? '';
                @endphp

                <div class="other-row" style="margin-bottom:8px;">
                    <textarea name="other_requirements[{{ $i }}][content]"
                              placeholder="要求内容"
                              style="width:30%;height:40px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;resize:none;">{{ $row['content'] ?? '' }}</textarea>

                    <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">

                        {{-- ラベル --}}
                        <span style="font-weight:700;color:var(--text);">対応者：</span>

                        {{-- 保存用 --}}
                        <input type="hidden"
                               name="other_requirements[{{ $i }}][responsible_employee_number]"
                               id="other-resp-no-{{ $i }}"
                               value="{{ $row['responsible_employee_number'] ?? '' }}">

                        <input type="hidden"
                               name="other_requirements[{{ $i }}][responsible_label]"
                               id="other-resp-label-{{ $i }}"
                               value="{{ $respLabel }}">

                        {{-- 選択済み表示（未選択なら完全に非表示＝幅0） --}}
                        <span id="other-resp-display-{{ $i }}"
                              style="display:{{ $respLabel ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
                            {{ $respLabel }}
                        </span>

                        {{-- 選択ボタン（左寄せ） --}}
                        <button type="button"
                                class="btn"
                                style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                                onclick="openPopupAForOther({{ $i }})">
                            選択
                        </button>

                        <button type="button"
                                onclick="removeOtherRow(this)"
                                style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                            削除
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button"
                onclick="addOtherRow()"
                style="margin-top:4px;background:#b91c1c;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:12px;">
            ＋ 行追加
        </button>
    </td>
</tr>

{{-- 9行目：Will --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">Will</td>
    <td style="{{ $inputCell }}">
        <span style="color:var(--text);font-weight:700;">登録費：</span>
        <input type="number"
               name="will_initial"
               value="{{ old('will_initial') }}"
               min="0"
               style="width:120px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
        <span style="color:var(--text);font-weight:700;">will</span>

        &nbsp;&nbsp;
        <span style="color:var(--text);font-weight:700;">月額：</span>
        <input type="number"
               name="will_monthly"
               value="{{ old('will_monthly') }}"
               min="0"
               style="width:120px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
        <span style="color:var(--text);font-weight:700;">will</span>
    </td>
</tr>

{{-- 10行目：月額管理費の分配 --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">月額管理費の分配</td>
    <td style="{{ $inputCell }}">
              @if($errors->has('will_allocations'))
            <div style="color:#dc2626; font-size:14px; margin:0 0 4px;">
                {{ $errors->first('will_allocations') }}
            </div>
        @endif
        <div id="will-rows">
            @php
                $allocOld = old('will_allocations', [
                    ['employee_number' => null, 'employee_name' => null, 'percentage' => null],
                ]);
            @endphp

            @foreach($allocOld as $i => $alloc)
                <div class="will-row"
                     style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">

                    {{-- 保存用 --}}
                    <input type="hidden"
                           name="will_allocations[{{ $i }}][employee_number]"
                           id="will-emp-no-{{ $i }}"
                           value="{{ $alloc['employee_number'] ?? '' }}">
                    <input type="hidden"
                           name="will_allocations[{{ $i }}][employee_name]"
                           id="will-emp-name-{{ $i }}"
                           value="{{ $alloc['employee_name'] ?? '' }}">

                    {{-- 選択ボタン（左寄せ） --}}
                    <button type="button"
                            class="btn"
                            style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                            onclick="openPopupAForWill({{ $i }})">
                        選択
                    </button>

                    {{-- 選択済み表示 --}}
                    <span id="will-emp-display-{{ $i }}"
                          style="min-width:220px;display:inline-block;color:var(--text);font-weight:700;">
                        @if(!empty($alloc['employee_number']) || !empty($alloc['employee_name']))
                            {{ ($alloc['employee_number'] ?? '') }} {{ ($alloc['employee_name'] ?? '') }}
                        @endif
                    </span>

                    <input type="number"
                           name="will_allocations[{{ $i }}][percentage]"
                           value="{{ $alloc['percentage'] ?? '' }}"
                           min="0" max="100"
                           style="width:80px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                    <span style="color:var(--text);font-weight:700;">%</span>

                    <button type="button"
                            onclick="removeWillRow(this)"
                            style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                        削除
                    </button>
                </div>
            @endforeach
        </div>

        <button type="button"
                onclick="addWillRow()"
                style="margin-top:4px;background:#b91c1c;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:12px;">
            ＋ 行追加
        </button>
    </td>
</tr>


                {{-- 11行目：関連Q-WEB --}}
                <tr style="{{ $rowStyle }}">
                    <td style="{{ $labelCell }}">関連Q-WEB</td>
                    <td style="{{ $inputCell }}">
                        <textarea name="related_qweb"
                                  rows="2"
                                  style="width:40%;height:40px;padding:6px 8px;border-radius:4px;border:1px solid #9ca3af;resize:none;">{{ old('related_qweb') }}</textarea>
                    </td>
                </tr>
            </table>
        </div>
       </form>

<div id="success-modal-overlay" class="ui dimmer" style="display:none;"></div>

<div id="success-modal" class="ui small modal" style="display:block; opacity:0; pointer-events:none;">
    <div class="header">完了</div>
    <div class="content" style="text-align:center; font-size:16px; padding:20px;">
        登録しました
    </div>
    <div class="actions" style="text-align:center;">
        <button type="button" class="ui blue button" onclick="closeSuccessModal()">OK</button>
    </div>
</div>




{{-- ▼ 社員検索モーダルのオーバーレイ --}}
<div class="ui dimmer modals page" id="emp-modal-overlay"></div>


{{-- ▼ 社員検索モーダル本体 --}}
<div class="ui large modal transition front" id="empsearch">
    <div class="header title_boader" id="emplbltype1">依頼者を選択（ダブルクリックで追加/削除）</div>
    <div class="header title_boader" id="emplbltype2" style="display: none;">共有したい人を選択（ダブルクリックで追加/削除）</div>
    <div class="header title_boader" id="emplbltype3" style="display: none;">担当者を選択（ダブルクリックで追加/削除）</div>
    <div class="header title_boader" id="emplbltype4" style="display: none;">担当技術者を選択（ダブルクリックで追加/削除）</div>

    <input type="hidden" id="empworkmode" value="0">

    <div class="scrolling content" style="min-height: 300px">
<div class="cweb-search-group">
    <input type="text" placeholder="keyword..." data-content="メンバーを検索" id="empkeyword" autocomplete="off">

    <button class="cweb-search-btn" id="empicon">
        <i class="search icon"></i>
        検索
    </button>
</div>

        <div class="ui two column grid emplist">
            <div class="row" style="margin-top: 1rem">
                <div class="column">
                    <label class="emp_f_s">SearchResult</label>
                    <div class="emp_l_s"></div>
                    <div class="ui middle divided selection list" id="EmpSearchResult"></div>
                </div>
                <div class="column">
                    <label class="emp_f_s">Selected</label>
                    <div class="emp_l_s"></div>
                    <div class="ui middle divided selection list" id="empselectedlist"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        <div class="ui button positive ok" id="emp-ok-btn">OK</div>
        <div class="ui button cancel" id="emp-cancel-btn">Cancel</div>
    </div>
</div>


<script>

let empContext = null;
    // mode: 'A' = 営業窓口, 'B' = 情報共有者, 'C' = 費用負担先
let tempSelectedEmps = [];    // A/B/その他要求/Will用
let tempSelectedCost = null;  // C 用

// ★ 追加：今どの行を編集しているか
let currentOtherIndex = null;
let currentWillIndex  = null;

    // ▼ PCN行追加・削除
    function addPcnRow() {
        const container = document.getElementById('pcn-rows');
        const index = container.querySelectorAll('.pcn-row').length;
        const div = document.createElement('div');
        div.className = 'pcn-row';
        div.style = 'display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;';

        div.innerHTML = `
            <select name="pcn_items[${index}][category]"
                    style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                <option value="">選択</option>
                <option value="spec">仕様書内容</option>
                <option value="man">人（Man）</option>
                <option value="machine">機械（Machine）</option>
                <option value="material">材料（Material）</option>
                <option value="method">方法（Method）</option>
                <option value="measurement">測定（Measurement）</option>
                <option value="environment">環境（Environment）</option>
                <option value="other">その他</option>
            </select>
            <input type="text" name="pcn_items[${index}][title]"
                   placeholder="ラベル変更など"
                   style="width:200px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
            <input type="number" name="pcn_items[${index}][months_before]"
                   min="0"
                   style="width:50px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
            <span style="color:var(--text);">ヵ月前連絡</span>
            <button type="button"
                    onclick="removePcnRow(this)"
                    style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                削除
            </button>
        `;
        container.appendChild(div);
    }
    function removePcnRow(btn) {
        const row = btn.closest('.pcn-row');
        if (row) row.remove();
    }

    // ▼ その他要求行追加・削除
    function addOtherRow() {
        const container = document.getElementById('other-rows');
        const index = container.querySelectorAll('.other-row').length;
        const div = document.createElement('div');
        div.className = 'other-row';
        div.style = 'margin-bottom:8px;';

        div.innerHTML = `
            <textarea name="other_requirements[${index}][content]"
                      placeholder="要求内容"
                      style="width:30%;height:40px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;resize:none;"></textarea>

            <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">

                <span style="font-weight:700;color:var(--text);">対応者：</span>

                <input type="hidden"
                       name="other_requirements[${index}][responsible_employee_number]"
                       id="other-resp-no-${index}">

                <input type="hidden"
                       name="other_requirements[${index}][responsible_label]"
                       id="other-resp-label-${index}">

                <span id="other-resp-display-${index}"
                      style="display:none;color:var(--text);font-weight:700;"></span>

                <button type="button"
                        class="btn"
                        style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                        onclick="openPopupAForOther(${index})">
                    選択
                </button>

                <button type="button"
                        onclick="removeOtherRow(this)"
                        style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                    削除
                </button>
            </div>
        `;
        container.appendChild(div);
    }

    function removeOtherRow(btn) {
        const row = btn.closest('.other-row');
        if (row) row.remove();
    }

    // ▼ Will分配行追加・削除
    function addWillRow() {
        const container = document.getElementById('will-rows');
        const index = container.querySelectorAll('.will-row').length;
        const div = document.createElement('div');
        div.className = 'will-row';
        div.style = 'display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;';

        div.innerHTML = `
            <input type="hidden"
                   name="will_allocations[${index}][employee_number]"
                   id="will-emp-no-${index}">
            <input type="hidden"
                   name="will_allocations[${index}][employee_name]"
                   id="will-emp-name-${index}">
            <button type="button"
                    class="btn"
                    style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                    onclick="openPopupAForWill(${index})">
                選択
            </button>
            <span id="will-emp-display-${index}"
                style="min-width:220px;display:inline-block;color:var(--text);font-weight:700;">
            </span>

            <input type="number"
                   name="will_allocations[${index}][percentage]"
                   min="0" max="100"
                   style="width:80px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
            <span style="color:var(--text);">%</span>
            <button type="button"
                    onclick="removeWillRow(this)"
                    style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                削除
            </button>
        `;
        container.appendChild(div);
    }
    function removeWillRow(btn) {
        const row = btn.closest('.will-row');
        if (row) row.remove();
    }

    // ▼ 対象製品プルダウン連動
    const productOptions = {
        'HogoMax-内製品':   ['102','103','104','105','106','107','108','152','153','201','202','203','204'],
        'HogoMax-OEM品':    ['002','003'],
        'StayClean-内製品': ['201','301','401'],
        'StayClean-OEM品':  ['-A','-F','-R'],
        'ResiFlat内製品':   ['103'],
        'その他':           []
    };

    function refreshProductSubOptions(selectedMain, selectedSub) {
        const subSelect = document.getElementById('product-sub');
        if (!subSelect) return;

        while (subSelect.firstChild) {
            subSelect.removeChild(subSelect.firstChild);
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '選択';
        placeholder.style.color = '#9ca3af';
        subSelect.appendChild(placeholder);

        const codes = productOptions[selectedMain] || [];
        if (codes.length === 0) {
            subSelect.disabled = true;
            subSelect.value = '';
            return;
        }

        subSelect.disabled = false;

        codes.forEach(code => {
            const opt = document.createElement('option');
            opt.value = code;
            opt.textContent = code;
            subSelect.appendChild(opt);
        });

        if (selectedSub && codes.includes(selectedSub)) {
            subSelect.value = selectedSub;
        } else {
            subSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const mainSelect = document.getElementById('product-main');
        const subSelect  = document.getElementById('product-sub');
        if (!mainSelect || !subSelect) return;

        const initialMain = mainSelect.value;
        const initialSub  = "{{ old('product_sub', '') }}";

        refreshProductSubOptions(initialMain, initialSub);

        mainSelect.addEventListener('change', function () {
            refreshProductSubOptions(this.value, '');
        });
    });

    // =========================
    // ここから ポップアップA〜C 用
    // =========================

    // ▼ ダミーマスタ（★実際は社内システムから取得に差し替え想定）
    const EMP_MASTER = [
        { no: '15329', name: '藤崎 隼也', dept: 'アプリ大学', en: 'Shyunya Fujisaki' },
        { no: '10001', name: '山田 太郎', dept: '営業一課',   en: 'Taro Yamada' },
        { no: '10002', name: '佐藤 花子', dept: '営業二課',   en: 'Hanako Sato' },
    ];

    const COST_OWNERS = [
        { code: 'C001', name: '営業本部' },
        { code: 'C002', name: 'デバイス事業部' },
        { code: 'C003', name: '管理部門' },
    ];

    // mode: 'A' = 営業窓口, 'B' = 情報共有者, 'C' = 費用負担先
    // let empMode = null;
    // let tempSelectedEmps = [];    // A/B 用
    // let tempSelectedCost = null;  // C 用

    document.addEventListener('DOMContentLoaded', () => {
        const keywordInput = document.getElementById('empkeyword');
        const searchIcon   = document.getElementById('empicon');
        const okBtn        = document.getElementById('emp-ok-btn');
        const cancelBtn    = document.getElementById('emp-cancel-btn');

        if (keywordInput) {
            keywordInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    rebuildEmpLists();
                }
            });
        }
        if (searchIcon) {
            searchIcon.addEventListener('click', rebuildEmpLists);
        }
        if (okBtn) {
            okBtn.addEventListener('click', applyEmpSelection);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeEmpModal);
        }
    });

    // ========== モーダル開閉 ==========

// ========== モーダル開閉 ==========

// ▼ 共通の社員/費用負担先ポップアップを開く
//   context: 'sales' | 'shared' | 'cost' | 'other' | 'will'
// ▼ 共通の社員/費用負担先ポップアップを開く
//   context: 'sales' | 'shared' | 'cost' | 'other' | 'will'
function openEmpModal(context) {
    empContext = context;

    // 見出しを全部消す
    const headerIds = ['emplbltype1', 'emplbltype2', 'emplbltype3', 'emplbltype4'];
    headerIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // 見出し切り替え（ここは元のままでOK）
    if (context === 'sales') {
        const h = document.getElementById('emplbltype1');
        if (h) {
            h.style.display = 'block';
            h.textContent = '営業窓口としたい人を選択（ダブルクリックで追加/削除）';
        }
    } else if (context === 'shared') {
        const h = document.getElementById('emplbltype2');
        if (h) {
            h.style.display = 'block';
            h.textContent = '共有したい人を選択（ダブルクリックで追加/削除）';
        }
    } else if (context === 'cost') {
        const h = document.getElementById('emplbltype3');
        if (h) {
            h.style.display = 'block';
            h.textContent = '費用負担先を選択（ダブルクリックで追加/削除）';
        }
    } else if (context === 'other') {
        const h = document.getElementById('emplbltype1');
        if (h) {
            h.style.display = 'block';
            h.textContent = 'その他要求の対応者を選択（ダブルクリックで追加/削除）';
        }
    } else if (context === 'will') {
        const h = document.getElementById('emplbltype1');
        if (h) {
            h.style.display = 'block';
            h.textContent = '月額管理費の分配担当者を選択（ダブルクリックで追加/削除）';
        }
    }

    // キーワード初期化
    const kw = document.getElementById('empkeyword');
    if (kw) kw.value = '';

    // 一時選択を hidden から復元
    initTempSelectionFromHidden();

    // リスト再描画
    rebuildEmpLists();

    // オーバーレイ＆モーダル表示（★ここを強制表示に）
    const overlay = document.getElementById('emp-modal-overlay'); // .ui.dimmer
    const modal   = document.getElementById('empsearch');         // .ui.modal

    if (overlay) {
        overlay.classList.add('visible', 'active');
        overlay.style.display = 'flex';
        overlay.style.opacity = '1';
    }
    if (modal) {
        modal.classList.add('visible', 'active');
        modal.style.display = 'block';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
    }
}

function closeEmpModal() {
    const overlay = document.getElementById('emp-modal-overlay');
    const modal   = document.getElementById('empsearch');

    if (overlay) {
        overlay.classList.remove('visible', 'active');
        overlay.style.opacity = '0';
        overlay.style.display = 'none';
    }
    if (modal) {
        modal.classList.remove('visible', 'active');
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        // display:block のままでもいいけど、気持ち悪ければ none にしてOK
        // modal.style.display = 'none';
    }

    tempSelectedEmps = [];
    tempSelectedCost = null;
    currentOtherIndex = null;
    currentWillIndex  = null;
}




    // A: 営業窓口
// A: 営業窓口
function openPopupA() {
    currentOtherIndex = null;
    currentWillIndex  = null;
    openEmpModal('sales');
}
// B: 情報共有者
function openPopupB() {
    currentOtherIndex = null;
    currentWillIndex  = null;
    openEmpModal('shared');
}
// C: 費用負担先
function openPopupC() {
    currentOtherIndex = null;
    currentWillIndex  = null;
    openEmpModal('cost');
}


    // ========== hidden から初期選択読み込み ==========

function initTempSelectionFromHidden() {
    tempSelectedEmps = [];
    tempSelectedCost = null;

    if (empContext === 'sales') {
        const noEl   = document.getElementById('sales-emp-no');
        const nameEl = document.getElementById('sales-emp-name');
        const no     = noEl?.value || '';
        const name   = nameEl?.value || '';

        if (no && name) {
            const emp = EMP_MASTER.find(e => e.no === no) || { no, name, dept: '', en: '' };
            tempSelectedEmps = [{ ...emp }];
        }

    } else if (empContext === 'shared') {
        const hiddenContainer = document.getElementById('shared-hidden-container');
        if (hiddenContainer) {
            const inputs = hiddenContainer.querySelectorAll('input[name="shared_employee_numbers[]"]');
            inputs.forEach(input => {
                const no = input.value;
                const emp = EMP_MASTER.find(e => e.no === no);
                if (emp) {
                    tempSelectedEmps.push({ ...emp });
                }
            });
        }

    } else if (empContext === 'cost') {
        const codeEl = document.getElementById('cost-owner-code');
        const nameEl = document.getElementById('cost-owner-name');
        const code   = codeEl?.value || '';
        const name   = nameEl?.value || '';

        if (code && name) {
            tempSelectedCost = { code, name };
        }

    } else if (empContext === 'other') {
        if (currentOtherIndex !== null) {
            const noEl    = document.getElementById('other-resp-no-' + currentOtherIndex);
            const labelEl = document.getElementById('other-resp-label-' + currentOtherIndex);
            const no      = noEl?.value || '';
            const label   = labelEl?.value || '';

            if (no) {
                const emp = EMP_MASTER.find(e => e.no === no) || { no, name: label, dept: '', en: '' };
                tempSelectedEmps = [{ ...emp }];
            }
        }

    } else if (empContext === 'will') {
        if (currentWillIndex !== null) {
            const noEl   = document.getElementById('will-emp-no-' + currentWillIndex);
            const nameEl = document.getElementById('will-emp-name-' + currentWillIndex);
            const no     = noEl?.value || '';
            const name   = nameEl?.value || '';

            if (no) {
                const emp = EMP_MASTER.find(e => e.no === no) || { no, name, dept: '', en: '' };
                tempSelectedEmps = [{ ...emp }];
            }
        }
    }
}

    // ========== リスト描画 ==========

function rebuildEmpLists() {
    const kwInput = document.getElementById('empkeyword');
    const keyword = (kwInput?.value || '').trim().toLowerCase();
    const resultList   = document.getElementById('EmpSearchResult');
    const selectedList = document.getElementById('empselectedlist');
    if (!resultList || !selectedList) return;

    resultList.innerHTML   = '';
    selectedList.innerHTML = '';

    // ▼ 社員を使うケース（A系）
    if (empContext === 'sales' || empContext === 'shared' || empContext === 'other' || empContext === 'will') {

        EMP_MASTER
            .filter(emp => {
                if (!keyword) return true;
                return (
                    emp.no.toLowerCase().includes(keyword) ||
                    emp.name.toLowerCase().includes(keyword) ||
                    (emp.en || '').toLowerCase().includes(keyword) ||
                    emp.dept.toLowerCase().includes(keyword)
                );
            })
            .forEach(emp => {
                const item = createEmpItem(emp);
                item.addEventListener('dblclick', () => toggleEmpSelect(emp.no));
                resultList.appendChild(item);
            });

        tempSelectedEmps.forEach(emp => {
            const item = createEmpItem(emp);
            item.addEventListener('dblclick', () => toggleEmpSelect(emp.no));
            selectedList.appendChild(item);
        });

    // ▼ 費用負担先（C系）
    } else if (empContext === 'cost') {

        COST_OWNERS
            .filter(co => {
                if (!keyword) return true;
                return (
                    co.code.toLowerCase().includes(keyword) ||
                    co.name.toLowerCase().includes(keyword)
                );
            })
            .forEach(co => {
                const item = createCostItem(co);
                item.addEventListener('dblclick', () => toggleCostSelect(co.code));
                resultList.appendChild(item);
            });

        if (tempSelectedCost) {
            const item = createCostItem(tempSelectedCost);
            item.addEventListener('dblclick', () => toggleCostSelect(tempSelectedCost.code));
            selectedList.appendChild(item);
        }
    }
}

    function createEmpItem(emp) {
        const div = document.createElement('div');
        div.className = 'item';
        const content = document.createElement('div');
        content.className = 'content';
        const header = document.createElement('div');
        header.className = 'header';
        header.innerHTML = `<i class="user circle outline icon"></i>${emp.no} / ${emp.name} / ${emp.dept} / ${emp.en || ''}`;
        content.appendChild(header);
        div.appendChild(content);
        return div;
    }

    function createCostItem(co) {
        const div = document.createElement('div');
        div.className = 'item';
        const content = document.createElement('div');
        content.className = 'content';
        const header = document.createElement('div');
        header.className = 'header';
        header.textContent = `${co.code} / ${co.name}`;
        content.appendChild(header);
        div.appendChild(content);
        return div;
    }

    // ========== ダブルクリックで追加/削除 ==========

function toggleEmpSelect(no) {
    // sales / other / will は 1件だけ選択
    if (empContext === 'sales' || empContext === 'other' || empContext === 'will') {
        const current = tempSelectedEmps[0];
        if (current && current.no === no) {
            tempSelectedEmps = [];
        } else {
            const emp = EMP_MASTER.find(e => e.no === no);
            if (emp) tempSelectedEmps = [{ ...emp }];
        }

    // shared は複数選択
    } else if (empContext === 'shared') {
        const idx = tempSelectedEmps.findIndex(e => e.no === no);
        if (idx >= 0) {
            tempSelectedEmps.splice(idx, 1);
        } else {
            const emp = EMP_MASTER.find(e => e.no === no);
            if (emp) tempSelectedEmps.push({ ...emp });
        }
    }

    rebuildEmpLists();
}
    function toggleCostSelect(code) {
        if (tempSelectedCost && tempSelectedCost.code === code) {
            tempSelectedCost = null; // もう一度 → 削除
        } else {
            const co = COST_OWNERS.find(c => c.code === code);
            if (co) tempSelectedCost = { ...co };
        }
        rebuildEmpLists();
    }

    // ========== OK で反映・Cancel で破棄 ==========

function applyEmpSelection() {
    if (empContext === 'sales') {
        const hiddenNo   = document.getElementById('sales-emp-no');
        const hiddenName = document.getElementById('sales-emp-name');
        const display    = document.getElementById('sales-emp-display');

        if (tempSelectedEmps.length > 0) {
            const emp = tempSelectedEmps[0];
            hiddenNo.value   = emp.no;
            hiddenName.value = emp.name;

            // ★ 表示は「社員番号 / 名前」
            display.textContent = emp.no + ' / ' + emp.name;
            display.style.display = 'inline-block';
        } else {
            hiddenNo.value   = '';
            hiddenName.value = '';
            display.textContent = '';
            display.style.display = 'none';
        }

    } else if (empContext === 'shared') {
        const hiddenContainer  = document.getElementById('shared-hidden-container');
        const displayContainer = document.getElementById('shared-display');
        if (hiddenContainer && displayContainer) {
            hiddenContainer.innerHTML  = '';
            displayContainer.innerHTML = '';

            tempSelectedEmps.forEach(emp => {
                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'shared_employee_numbers[]';
                hidden.value = emp.no;
                hiddenContainer.appendChild(hidden);

                const chip = document.createElement('span');
                chip.style.cssText = 'display:inline-block;background:#1f2937;color:#e5e7eb;padding:2px 6px;border-radius:999px;font-size:11px;margin-right:4px;margin-bottom:2px;';
                chip.textContent = emp.name;
                displayContainer.appendChild(chip);
            });
        }

    } else if (empContext === 'cost') {
        const hiddenCode = document.getElementById('cost-owner-code');
        const hiddenName = document.getElementById('cost-owner-name');
        const display    = document.getElementById('cost-owner-display');

        if (tempSelectedCost) {
            hiddenCode.value = tempSelectedCost.code;
            hiddenName.value = tempSelectedCost.name;
            display.textContent = tempSelectedCost.name;
            display.style.display = 'inline-block';
        } else {
            hiddenCode.value = '';
            hiddenName.value = '';
            display.textContent = '';
            display.style.display = 'none';
        }

    } else if (empContext === 'other') {
        if (currentOtherIndex !== null) {
            const no    = tempSelectedEmps[0]?.no || '';
            const name  = tempSelectedEmps[0]?.name || '';
            const noEl  = document.getElementById('other-resp-no-' + currentOtherIndex);
            const lblEl = document.getElementById('other-resp-label-' + currentOtherIndex);
            const disp  = document.getElementById('other-resp-display-' + currentOtherIndex);

            if (noEl && lblEl && disp) {
                noEl.value  = no;
                lblEl.value = name;
                disp.textContent = name;
                disp.style.display = no ? 'inline-block' : 'none';
            }
        }

    } else if (empContext === 'will') {
        if (currentWillIndex !== null) {
            const no    = tempSelectedEmps[0]?.no || '';
            const name  = tempSelectedEmps[0]?.name || '';
            const noEl   = document.getElementById('will-emp-no-' + currentWillIndex);
            const nameEl = document.getElementById('will-emp-name-' + currentWillIndex);
            const disp   = document.getElementById('will-emp-display-' + currentWillIndex);

            if (noEl && nameEl && disp) {
                noEl.value   = no;
                nameEl.value = name;
                disp.textContent = (no || name) ? (no + ' ' + name) : '';
                disp.style.display = (no || name) ? 'inline-block' : 'none';
            }
        }
    }

    closeEmpModal();
}


// ▼ その他要求：ポップアップA（社員）を使う
function openPopupAForOther(i) {
    currentOtherIndex = i;
    currentWillIndex  = null;
    openEmpModal('other');
}

// ▼ Will分配：ポップアップA（社員）を使う
function openPopupAForWill(i) {
    currentWillIndex  = i;
    currentOtherIndex = null;
    openEmpModal('will');
}


    // ▼ その他要求：外から値をセットしたい時用（今は未使用でもOK）
    function setOtherResponsible(index, empNo, label) {
        document.getElementById('other-resp-no-' + index).value = empNo;
        document.getElementById('other-resp-label-' + index).value = label;

        const span = document.getElementById('other-resp-display-' + index);
        span.textContent = label;
        span.style.display = 'inline-block';
    }


// ▼ カテゴリは1つだけチェック可能にする

// ===== 登録完了モーダル表示用 =====
function showSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');

    if (!overlay || !modal) return;

    // Dimmer
    overlay.classList.add('visible', 'active');
    overlay.style.display = 'flex';
    overlay.style.opacity = '1';

    // Modal
    modal.classList.add('visible', 'active');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}

// OK 押下時
function closeSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');

    if (overlay) {
        overlay.classList.remove('visible', 'active');
        overlay.style.opacity = '0';
        overlay.style.display = 'none';
    }
    if (modal) {
        modal.classList.remove('visible', 'active');
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
    }

    // ★ OK で案件一覧に戻したい場合はここで遷移
    window.location.href = "{{ route('cweb.cases.index') }}";
}


</script>

@endsection
