{{-- resources/views/cweb/cases/product.blade.php --}}
@extends('cweb.layout')

@section('header')
@php
    $currentLocale = app()->getLocale();
    $nextLocale = $currentLocale === 'ja' ? 'en' : 'ja';

    // ✅ 現在のクエリ（product_group / product_code 等）を維持して locale だけ切り替え
    $switchLocaleParams = array_merge(request()->query(), [
        'tab'    => 'product',
        'locale' => $nextLocale,
    ]);
@endphp

<header class="cweb-header">
    <div class="cweb-header-inner">
        <div class="cweb-header-left">
            <a href="{{ route('cweb.cases.index') }}" class="cweb-brand-link">C-WEB</a>

            <a href="{{ route('cweb.cases.create') }}" class="btn btn-accent">
                {{ __('cweb.actions.register') }}
            </a>
        </div>

        <div class="cweb-header-right">
            <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>

            {{-- ✅ 言語トグル：トップページ同様（リンクで切替） --}}
            <div class="cweb-header-lang">
                <a class="cweb-header-lang-toggle"
                   href="{{ route('cweb.cases.index', $switchLocaleParams) }}">
                    {{ $currentLocale === 'ja' ? 'EN' : '日本語' }}
                </a>
            </div>

            @auth
                <button type="button" class="cweb-header-user-toggle" id="cweb-user-toggle">
                    {{ auth()->user()->name }}
                </button>
            @endauth
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const userToggle = document.getElementById('cweb-user-toggle');
    if (userToggle) {
        userToggle.addEventListener('click', () => userToggle.classList.toggle('is-active'));
    }
});
</script>
@endsection


@section('content')
@php
    $tab = $tab ?? 'product';
    $selectedGroup = $productGroup ?? '';
    $selectedCode  = $productCode ?? '';
    $hasGroup = $selectedGroup !== '';
    $hasCode  = $selectedCode  !== '';
@endphp

{{-- ▼ タブ --}}
<div class="cweb-tabs">
    <a href="{{ route('cweb.cases.index', ['tab' => 'all']) }}"
       class="cweb-tab-link">
        {{ __('cweb.tabs.all') }}
    </a>

    <a href="{{ route('cweb.cases.index', ['tab' => 'mine']) }}"
       class="cweb-tab-link">
        {{ __('cweb.tabs.mine') }}
    </a>

    <a href="{{ route('cweb.cases.index', ['tab' => 'product']) }}"
       class="cweb-tab-link is-active">
        {{ __('cweb.tabs.product') }}
    </a>
</div>

{{-- ▼ 製品選択フォーム（選択した瞬間に submit） --}}
<form method="GET" action="{{ route('cweb.cases.index') }}" class="cweb-product-filter-form">
    <input type="hidden" name="tab" value="product">

    <div class="cweb-product-filter-row">
        {{-- ① 製品選択▼（黒文字＋青バー） --}}
        <div class="cweb-product-filter-block">
            <div class="ui inline floating dropdown cweb-product-dropdown" id="product-group-dropdown">
                <input type="hidden" name="product_group" value="{{ $selectedGroup }}">

                <div class="cweb-product-dropdown-tab">
                    <span class="text">
                        {{ $selectedGroup !== '' ? $selectedGroup : __('cweb.product.select_group') }}
                    </span>
                    <span class="cweb-product-dropdown-caret">▼</span>
                </div>

                <div class="menu transition" tabindex="-1">
                    <div class="ui icon search input">
                        <i class="search icon"></i>
                        <input type="text" placeholder="{{ __('cweb.search.keyword_placeholder') }}" autocomplete="off">
                    </div>
                    <div class="scrolling menu">
                        @foreach($groupOptions as $groupLabel => $codes)
                            <div class="item" data-value="{{ $groupLabel }}">
                                {{ $groupLabel }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ② 2個目の▼（デフォルトは空欄＋▼だけ。選択後に 103▼ など） --}}
        @php
            $codesForGroup = $selectedGroup && isset($groupOptions[$selectedGroup])
                ? $groupOptions[$selectedGroup]
                : [];
            $disableSub = !$hasGroup || $selectedGroup === 'その他';
        @endphp

        @if(!$disableSub)
            <div class="cweb-product-filter-block">
                <div class="ui inline floating dropdown cweb-product-dropdown" id="product-code-dropdown">
                    <input type="hidden" name="product_code" value="{{ $selectedCode }}">

                    <div class="cweb-product-dropdown-tab">
                        <span class="text">
                            @if($selectedCode !== '')
                                {{ $selectedCode }}
                            @else
                                {{-- デフォルトは空欄（スペース） --}}
                                &nbsp;
                            @endif
                        </span>
                        <span class="cweb-product-dropdown-caret">▼</span>
                    </div>

                    <div class="menu transition" tabindex="-1">
                        <div class="ui icon search input">
                            <i class="search icon"></i>
                            <input type="text" placeholder="{{ __('cweb.search.keyword_placeholder') }}" autocomplete="off">
                        </div>
                        <div class="scrolling menu">
                            <div class="item" data-value="">{{ __('cweb.product.all_in_group') }}</div>
                            @foreach($codesForGroup as $code)
                                <div class="item" data-value="{{ $code }}">
                                    {{ $code }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 表示ボタンは無し（選択時にsubmit） --}}
    </div>
</form>

{{-- ▼ 内容：製品選択後に表示 --}}
@if($hasGroup)
    @php
        $standardCount = $contractSummary['standard'] ?? 0;
        $pcnCount      = $contractSummary['pcn']      ?? 0;
        $otherCount    = $contractSummary['other']    ?? 0;
    @endphp

    <div class="cweb-product-layout">
        {{-- 左カラム --}}
        <div class="cweb-product-col-left">
            {{-- 契約登録数（ピンク） --}}
            <div class="cweb-product-card cweb-product-card-pink">
                <div class="cweb-product-card-header">
                    {{ __('cweb.product.cards.contract_count') }}
                </div>
                <div class="cweb-product-card-body">
                    <div>
                        {{ __('cweb.product.contract_breakdown.standard') }}：
                        {{ $standardCount }}{{ __('cweb.product.contract_breakdown.suffix') }}
                    </div>
                    <div>
                        {{ __('cweb.product.contract_breakdown.pcn') }}：
                        {{ $pcnCount }}{{ __('cweb.product.contract_breakdown.suffix') }}
                    </div>
                    <div>
                        {{ __('cweb.product.contract_breakdown.other') }}：
                        {{ $otherCount }}{{ __('cweb.product.contract_breakdown.suffix') }}
                    </div>
                </div>
            </div>

            {{-- その他要求（青）※高さは黄色の箱の下まで伸ばす --}}
            <div class="cweb-product-card cweb-product-card-blue">
                <div class="cweb-product-card-header">
                    {{ __('cweb.product.cards.other_requests') }}
                </div>
                <div class="cweb-product-card-body cweb-product-card-body-flex">
                    {{ __('cweb.product.coming_soon') }}
                </div>
            </div>
        </div>

        {{-- 右カラム：PCN管理対象（黄色） --}}
        <div class="cweb-product-col-right">
            <div class="cweb-product-card cweb-product-card-yellow">
                <div class="cweb-product-card-header">
                    {{ __('cweb.product.cards.pcn_targets') }}
                </div>

                <div class="cweb-product-card-body cweb-product-card-body-pcn">
                    @foreach($pcnSummary as $key => $item)
                        @php
                            $count    = $item['count']      ?? 0;
                            $months   = $item['max_months'] ?? null;
                            $customer = $item['customer']   ?? null;
                            $cases    = $item['cases']      ?? [];
                        @endphp

                        <div class="cweb-pcn-row">
                            {{-- 1行にまとめる：ラベル＋▼＋最長通知期間＋顧客 --}}
                            <div class="cweb-pcn-row-line">
                                <div class="cweb-pcn-row-label">
           {{ __('cweb.pcn.categories.' . $key) }}：
            {{ $count }}{{ __('cweb.product.contract_breakdown.suffix') }}
                                    <button type="button"
                                            class="cweb-pcn-toggle"
                                            data-pcn-key="{{ $key }}">
                                        ▼
                                    </button>
                                </div>

                                <div class="cweb-pcn-row-meta">
                                    <span>
                                        {{ __('cweb.product.pcn.max_notice') }}：
                                        {{ $months ? ($months . __('cweb.product.pcn.months_suffix')) : '-' }}
                                    </span>
                                    <span>
                                        {{ __('cweb.product.pcn.customer') }}：{{ $customer ?: '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- ▼ 詳細（管理番号/通知期間/顧客） --}}
                            <div class="cweb-pcn-detail" data-pcn-key="{{ $key }}">
                                @if(!empty($cases))
                                    @foreach($cases as $pcnCase)
                                        @php
                                            // id の取り方（配列 / オブジェクト / case_id 両対応）
                                            $caseId       = $pcnCase['id'] ?? $pcnCase['case_id'] ?? ($pcnCase->id ?? null);
                                            $manageNo     = $pcnCase['manage_no'] ?? ($pcnCase->manage_no ?? null);
                                            $monthsBefore = $pcnCase['months_before'] ?? ($pcnCase->months_before ?? '?');
                                            $customerName = $pcnCase['customer_name'] ?? ($pcnCase->customer_name ?? '-');

                                            // id があれば edit へのURL、なければダミー
                                            $manageUrl = $caseId
                                                ? route('cweb.cases.show', $caseId)
                                                : 'javascript:void(0)';
                                        @endphp

                                        <div class="cweb-pcn-detail-row">
                                            <div>
                                                {{ __('cweb.product.pcn.manage_no') }}：
                                                @if($manageNo)
                                                    <a href="{{ $manageUrl }}" class="cweb-pcn-manage-no-link">
                                                        {{ $manageNo }}
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </div>
                                            <div>
                                                {{ __('cweb.product.pcn.notice') }}：
                                                {{ $monthsBefore . __('cweb.product.pcn.months_suffix') }}
                                            </div>
                                            <div>
                                                {{ __('cweb.product.pcn.customer') }}：{{ $customerName }}
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="cweb-pcn-detail-row cweb-pcn-detail-empty">
                                        {{ __('cweb.product.pcn.no_cases') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
@else
    <div style="margin-top:24px;font-size:13px;color:#6b7280;">
        {{ __('cweb.product.help_select_to_show') }}<br>
    </div>
@endif


<script>
document.addEventListener('DOMContentLoaded', function () {
    // ------- PCN 詳細（▼で開閉） -------
    document.querySelectorAll('.cweb-pcn-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.pcnKey;
            const target = document.querySelector('.cweb-pcn-detail[data-pcn-key="'+key+'"]');
            if (target) target.classList.toggle('is-open');
        });
    });

    // ------- 共通：検索付きドロップダウン + 選択した瞬間に submit -------
    function setupSearchDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;

        const form        = dropdown.closest('form');
        const hiddenInput = dropdown.querySelector('input[type="hidden"]');
        const textEl      = dropdown.querySelector('.cweb-product-dropdown-tab .text');
        const menu        = dropdown.querySelector('.menu.transition');
        const searchInput = dropdown.querySelector('.ui.icon.search input');
        const items       = dropdown.querySelectorAll('.scrolling.menu .item');

        const openMenu = () => {
            menu.classList.add('visible','transition');
            menu.style.display = 'block';
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                items.forEach(i => i.style.display = 'block');
            }
        };
        const closeMenu = () => {
            menu.classList.remove('visible','transition');
            menu.style.display = 'none';
        };

        dropdown.addEventListener('click', function (e) {
            if (e.target.closest('.menu')) return;
            const isVisible = menu.classList.contains('visible');
            if (isVisible) closeMenu(); else openMenu();
        });

        // アイテム選択 → 値セット → 即 submit
        items.forEach(item => {
            item.addEventListener('click', function () {
                const value = item.getAttribute('data-value') || '';
                const label = item.textContent.trim();

                hiddenInput.value = value;

                if (dropdownId === 'product-group-dropdown' && !value) {
                    textEl.textContent = @json(__('cweb.product.select_group'));
                } else if (dropdownId === 'product-code-dropdown' && !value) {
                    textEl.innerHTML = '&nbsp;';
                } else {
                    textEl.textContent = label;
                }

                closeMenu();
                if (form) form.submit();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const q = this.value.toLowerCase();
                items.forEach(item => {
                    const label = item.textContent.toLowerCase();
                    const value = (item.getAttribute('data-value') || '').toLowerCase();
                    const match = label.includes(q) || value.includes(q);
                    item.style.display = match ? 'block' : 'none';
                });
            });
        }

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) closeMenu();
        });
    }

    setupSearchDropdown('product-group-dropdown');
    setupSearchDropdown('product-code-dropdown');
});
</script>


<style>
.cweb-product-filter-form{
    margin-top:16px;
}
.cweb-product-filter-row{
    display:flex;
    flex-wrap:wrap;
    gap:24px;
    align-items:flex-start;
}
.cweb-product-filter-block{
    flex:0 0 auto;
}

/* ▼ 「製品選択▼」「2個目の▼」のタブ風ラベル */
.cweb-product-dropdown{
    cursor:pointer;
    position:relative;
    display:inline-block;
}
.cweb-product-dropdown-tab{
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding-bottom:4px;
    font-size:14px;
    font-weight:700;
    color:#111827;
    border-bottom:3px solid #0ea5e9;
}
.cweb-product-dropdown-caret{
    font-size:11px;
}
.cweb-product-dropdown .menu.transition{
    left:0;
    right:auto;
    margin-top:.5em;
    min-width:100%;
    background:#fff;
}
.cweb-product-dropdown .scrolling.menu{
    max-height:200px;
}
.cweb-product-dropdown .ui.icon.search.input{
    width:auto;
    display:flex;
    margin:1.14285714rem .78571429rem;
    min-width:10rem;
}
.cweb-product-dropdown .ui.icon.search.input input{
    width:100%;
}

/* ▼ レイアウト：左33％・右残り */
.cweb-product-layout{
    margin-top:24px;
    display:flex;
    gap:16px;
    align-items:stretch;
}
.cweb-product-col-left{
    flex:0 0 33%;
    max-width:380px;
    display:flex;
    flex-direction:column;
    gap:12px;
}
.cweb-product-col-right{
    flex:1 1 auto;
    display:flex;
    flex-direction:column;
}

/* ▼ カード共通 */
.cweb-product-card{
    border:1px solid #d1d5db;
    border-radius:8px;
    overflow:hidden;
    background:#fff;
}
.cweb-product-card-header{
    padding:8px 12px;
    font-weight:700;
    font-size:13px;
}
.cweb-product-card-body{
    padding:10px 12px;
    font-size:13px;
}

/* 青いカードを下まで伸ばしたいので中身をflex化 */
.cweb-product-card-blue{
    flex:1 1 auto;
    display:flex;
    flex-direction:column;
}
.cweb-product-card-body-flex{
    flex:1 1 auto;
}

/* 右側の黄色カードも伸ばして左右の底を揃える */
.cweb-product-card-yellow{
    flex:1 1 auto;
}

/* 色違いヘッダー */
.cweb-product-card-pink .cweb-product-card-header{
    background:#fce7f3;
    border-bottom:1px solid #d1d5db;
}
.cweb-product-card-blue .cweb-product-card-header{
    background:#dbeafe;
    border-bottom:1px solid #d1d5db;
}
.cweb-product-card-yellow .cweb-product-card-header{
    background:#fef3c7;
    border-bottom:1px solid #d1d5db;
}

/* ▼ PCN管理対象の中身 */
.cweb-product-card-body-pcn{
    padding:0;
}
.cweb-pcn-row{
    border-top:1px solid #e5e7eb;
}
.cweb-pcn-row:first-child{
    border-top:none;
}

/* 1行にまとめた行：ラベル列 + メタ列の2カラム固定 */
.cweb-pcn-row-line{
    padding:8px 12px;
    display:grid;
    grid-template-columns: 220px 1fr;
    column-gap:24px;
    align-items:start;
}

/* 左のラベル */
.cweb-pcn-row-label{
    display:flex;
    align-items:center;
    gap:4px;
}

/* 右のメタ */
.cweb-pcn-row-meta{
    display:flex;
    flex-wrap:wrap;
    column-gap:32px;
    row-gap:4px;
    font-size:12px;
    color:#4b5563;
}
.cweb-pcn-row-meta span{
    min-width:140px;
}

.cweb-pcn-toggle{
    border:none;
    background:transparent;
    cursor:pointer;
    font-size:11px;
    padding:0 2px;
}

/* 詳細部 */
.cweb-pcn-detail{
    display:none;
    padding:8px 12px 10px;
    background:#f9fafb;
    border-top:1px dashed #d1d5db;
}
.cweb-pcn-detail.is-open{
    display:block;
}
.cweb-pcn-detail-row{
    font-size:12px;
    display:flex;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:4px;
}
.cweb-pcn-detail-row:last-child{
    margin-bottom:0;
}
.cweb-pcn-detail-empty{
    color:#9ca3af;
}

.cweb-pcn-detail-row a.cweb-pcn-manage-no-link{
    text-decoration:underline;
    cursor:pointer;
}

/* ダークモード対応 */
@media (prefers-color-scheme: dark){
    .cweb-product-card{
        border-color:#4b5563;
        background:#020617;
    }
    .cweb-product-card-body{
        color:#e5e7eb;
    }
    .cweb-product-card-blue .cweb-product-card-header{
        background:#1d3557;
    }
    .cweb-product-card-yellow .cweb-product-card-header{
        background:#78350f;
    }
    .cweb-pcn-row{
        border-top-color:#374151;
    }
    .cweb-pcn-detail{
        background:#020617;
        border-top-color:#374151;
    }
    .cweb-product-dropdown .menu.transition{
        background:#020617;
        border-color:#4b5563;
        color:#e5e7eb;
    }
    .cweb-product-dropdown .scrolling.menu .item{
        color:#e5e7eb;
    }
}
</style>
@endsection
