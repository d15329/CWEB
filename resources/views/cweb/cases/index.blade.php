@extends('cweb.layout')

{{-- 🔹 ヘッダーは header セクションに --}}
@section('header')
@php
    $currentLocale = app()->getLocale();
    $nextLocale = $currentLocale === 'ja' ? 'en' : 'ja';

    // 現在のクエリ（tab/keyword/filter等）を維持して locale だけ切り替える
    $switchLocaleParams = array_merge(request()->query(), ['locale' => $nextLocale]);
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

            {{-- 言語トグル：リンクで /ja ⇔ /en --}}
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

{{-- ✅ header内のscriptは「JSだけ」 --}}
<script>
function openCategoryImage() {
    const url = "{{ asset('images/images_C.png') }}";
    window.open(url, '_blank');
}

// ▼ 絞り込みメニュー開閉
document.addEventListener('click', function (e) {
    const toggle = e.target.closest('.cweb-filter-toggle');
    if (toggle) {
        const targetId = toggle.dataset.target;
        const menus = document.querySelectorAll('.cweb-filter-menu');

        menus.forEach(m => {
            if (m.id !== targetId) m.classList.remove('is-open');
        });

        const menu = document.getElementById(targetId);
        if (menu) menu.classList.toggle('is-open');
        return;
    }

    if (!e.target.closest('.cweb-filter-menu')) {
        document.querySelectorAll('.cweb-filter-menu').forEach(m => m.classList.remove('is-open'));
    }
});

// ▼ ユーザー名：押すとON/OFFが分かるように（見た目用）
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
    $tab = $tab ?? 'all';
@endphp

{{-- ✅ フラッシュメッセージ（登録/更新/廃止など） --}}
@if (session('ok'))
    <div id="flash-ok"
         style="
            margin: 12px 24px 8px;
            padding: 10px 14px;
            border-radius: 10px;
            background: rgba(34,197,94,.14);
            border: 1px solid rgba(34,197,94,.35);
            color: var(--text);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
         ">
        <div style="font-weight:700;">
            {{ session('ok') }}
        </div>

        <button type="button"
                onclick="document.getElementById('flash-ok')?.remove()"
                aria-label="close"
                style="border:none;background:transparent;color:var(--text);font-size:18px;line-height:1;cursor:pointer;opacity:.7;">
            ×
        </button>
    </div>
@endif

{{-- タブ切り替え --}}
<div class="cweb-tabs">
    <a href="{{ route('cweb.cases.index', ['tab' => 'all']) }}"
       class="cweb-tab-link {{ $tab === 'all' ? 'is-active' : '' }}">
        {{ __('cweb.tabs.all') }}
    </a>

    <a href="{{ route('cweb.cases.index', ['tab' => 'mine']) }}"
       class="cweb-tab-link {{ $tab === 'mine' ? 'is-active' : '' }}">
        {{ __('cweb.tabs.mine') }}
    </a>

    <a href="{{ route('cweb.cases.index', ['tab' => 'product']) }}"
       class="cweb-tab-link {{ $tab === 'product' ? 'is-active' : '' }}">
        {{ __('cweb.tabs.product') }}
    </a>
</div>

{{-- 検索ボックス + ボタン行 --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin:12px 0 16px;">
    <form method="GET"
          action="{{ route('cweb.cases.index') }}"
          style="display:flex;align-items:center;flex:0 0 auto; max-width:260px; width:100%;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <input type="text"
               name="keyword"
               value="{{ request('keyword') }}"
               placeholder="{{ __('cweb.search.placeholder') }}"
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
            {{ __('cweb.actions.search') }}
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
        {{ __('cweb.actions.open_category_guide') }}
    </button>
</div>

@php
    $sort      = request('sort');
    $direction = request('direction', 'asc');
    $toggleDir = $direction === 'asc' ? 'desc' : 'asc';
@endphp

<div style="background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
        <tr style="background:#f3f4f6;">
            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                {{ __('cweb.table.management_no') }}
            </th>

            {{-- ステータス（▼で絞り込み） --}}
            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                <div class="cweb-filter-wrap">
                    <span>{{ __('cweb.table.status') }}</span>
                    <button type="button" class="cweb-filter-toggle" data-target="status-filter-menu">▼</button>

                    <div id="status-filter-menu" class="cweb-filter-menu">
                        <form method="GET" action="{{ route('cweb.cases.index') }}">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                            <input type="hidden" name="direction" value="{{ request('direction') }}">
                            <input type="hidden" name="category" value="{{ request('category') }}">
                            <input type="hidden" name="product_group" value="{{ request('product_group') }}">
                            <input type="hidden" name="product_code" value="{{ request('product_code') }}">

                            <div style="margin-bottom:6px;font-size:12px;">{{ __('cweb.filter.title') }}</div>
                            <select name="status" style="width:100%;padding:4px 6px;font-size:12px;">
                                <option value="">{{ __('cweb.filter.all') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                    {{ __('cweb.status.active') }}
                                </option>
                                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>
                                    {{ __('cweb.status.closed') }}
                                </option>
                            </select>

                            <div style="margin-top:8px;text-align:right;font-size:12px;">
                                <button type="submit"
                                        style="padding:3px 8px;border-radius:4px;border:none;background:#2563eb;color:#fff;cursor:pointer;">
                                    {{ __('cweb.filter.apply') }}
                                </button>
                                <a href="{{ route('cweb.cases.index', array_merge(request()->except(['status','page']), ['tab' => $tab])) }}"
                                   style="margin-left:6px;font-size:11px;color:#6b7280;text-decoration:underline;">
                                    {{ __('cweb.filter.clear') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </th>

            {{-- カテゴリー（▼で絞り込み） --}}
            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                <div class="cweb-filter-wrap">
                    <span>{{ __('cweb.table.category') }}</span>
                    <button type="button" class="cweb-filter-toggle" data-target="category-filter-menu">▼</button>

                    <div id="category-filter-menu" class="cweb-filter-menu">
                        <form method="GET" action="{{ route('cweb.cases.index') }}">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                            <input type="hidden" name="direction" value="{{ request('direction') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="product_group" value="{{ request('product_group') }}">
                            <input type="hidden" name="product_code" value="{{ request('product_code') }}">

                            <div style="margin-bottom:6px;font-size:12px;">{{ __('cweb.filter.title') }}</div>
                            <select name="category" style="width:100%;padding:4px 6px;font-size:12px;">
                                <option value="">{{ __('cweb.filter.all') }}</option>
                                <option value="standard" {{ request('category') === 'standard' ? 'selected' : '' }}>
                                    {{ __('cweb.categories.standard') }}
                                </option>
                                <option value="pcn" {{ request('category') === 'pcn' ? 'selected' : '' }}>
                                    {{ __('cweb.categories.pcn') }}
                                </option>
                                <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>
                                    {{ __('cweb.categories.other') }}
                                </option>
                            </select>

                            <div style="margin-top:8px;text-align:right;font-size:12px;">
                                <button type="submit"
                                        style="padding:3px 8px;border-radius:4px;border:none;background:#2563eb;color:#fff;cursor:pointer;">
                                    {{ __('cweb.filter.apply') }}
                                </button>
                                <a href="{{ route('cweb.cases.index', array_merge(request()->except(['category','page']), ['tab' => $tab])) }}"
                                   style="margin-left:6px;font-size:11px;color:#6b7280;text-decoration:underline;">
                                    {{ __('cweb.filter.clear') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </th>

            {{-- 対象製品（▼で絞り込み） --}}
            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                <div class="cweb-filter-wrap">
                    <span>{{ __('cweb.table.product') }}</span>
                    <button type="button" class="cweb-filter-toggle" data-target="product-filter-menu">▼</button>

                    <div id="product-filter-menu" class="cweb-filter-menu">
                        <form method="GET" action="{{ route('cweb.cases.index') }}">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                            <input type="hidden" name="direction" value="{{ request('direction') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="category" value="{{ request('category') }}">

                            <div style="margin-bottom:6px;font-size:12px;">{{ __('cweb.filter.product_group') }}</div>
                            <select name="product_group"
                                    style="width:100%;padding:4px 6px;font-size:12px;margin-bottom:8px;">
                                <option value="">{{ __('cweb.filter.all') }}</option>
                                @foreach ($productGroups as $group)
                                    <option value="{{ $group }}" {{ request('product_group') === $group ? 'selected' : '' }}>
                                        {{ $group }}
                                    </option>
                                @endforeach
                            </select>

                            <div style="margin-bottom:6px;font-size:12px;">{{ __('cweb.filter.product_code') }}</div>
                            <select name="product_code" style="width:100%;padding:4px 6px;font-size:12px;">
                                <option value="">{{ __('cweb.filter.all') }}</option>
                                @foreach ($productCodes as $code)
                                    <option value="{{ $code }}" {{ request('product_code') === $code ? 'selected' : '' }}>
                                        {{ $code }}
                                    </option>
                                @endforeach
                            </select>

                            <div style="margin-top:8px;text-align:right;font-size:12px;">
                                <button type="submit"
                                        style="padding:3px 8px;border-radius:4px;border:none;background:#2563eb;color:#fff;cursor:pointer;">
                                    {{ __('cweb.filter.apply') }}
                                </button>
                                <a href="{{ route('cweb.cases.index', array_merge(
                                        request()->except(['product_group','product_code','page']),
                                        ['tab' => $tab]
                                    )) }}"
                                   style="margin-left:6px;font-size:11px;color:#6b7280;text-decoration:underline;">
                                    {{ __('cweb.filter.clear') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </th>

            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                {{ __('cweb.table.customer') }}
            </th>
            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                {{ __('cweb.table.sales_contact') }}
            </th>
            <th style="padding:8px 10px;text-align:center;font-weight:700;border:1px solid #9ca3af;">
                {{ __('cweb.table.monthly_cost') }}
            </th>
        </tr>
        </thead>

        <tbody>
        @forelse($cases as $case)
            @php
                $categories = [];
                if ($case->category_standard) $categories[] = __('cweb.categories.standard');
                if ($case->category_pcn)      $categories[] = __('cweb.categories.pcn');
                if ($case->category_other)    $categories[] = __('cweb.categories.other');
                $categoryLabel = $categories ? implode(' / ', $categories) : '-';

                $statusKey = $case->status ?? 'unknown';
                $statusLabel = __('cweb.status.' . $statusKey);

                $productLabel = trim(($case->product_group ?? '').' '.($case->product_code ?? ''));
            @endphp

            <tr>
                <td style="padding:6px 10px;color:#2563eb;font-weight:700;text-align:center;">
                    <a href="{{ route('cweb.cases.show', ['case' => $case->id]) }}"
                       style="color:#2563eb;text-decoration:none;">
                        {{ $case->manage_no }}
                    </a>
                </td>

                <td style="padding:6px 10px;color:#111827;text-align:center;">
                    {{ $statusLabel }}
                </td>

                <td style="padding:6px 10px;color:#111827;text-align:center;">
                    {{ $categoryLabel }}
                </td>

                <td style="padding:6px 10px;color:#111827;text-align:center;">
                    {{ $productLabel ?: '-' }}
                </td>

                <td style="padding:6px 10px;color:#111827;text-align:center;">
                    {{ $case->customer_name }}
                </td>

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

                <td style="padding:6px 10px;color:#111827;text-align:center;">
                    {{ $case->will_monthly_cost ? number_format($case->will_monthly_cost) : '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="padding:10px 10px;color:#6b7280;text-align:center;">
                    {{ __('cweb.empty.no_cases') }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- ページネーション --}}
@if($cases->hasPages())
    <div class="cweb-pagination-wrapper">
        <div class="ui center aligned floated pagination menu cweb-pagination-menu" role="navigation">
            @if ($cases->onFirstPage())
                <span class="icon item disabled" aria-disabled="true" aria-label="« Previous">‹</span>
            @else
                <a class="icon item" href="{{ $cases->previousPageUrl() }}" rel="prev" aria-label="« Previous">‹</a>
            @endif

            @php
                $current = $cases->currentPage();
                $last    = $cases->lastPage();
                $start = max(1, $current - 2);
                $end   = min($last, $current + 2);
            @endphp

            @if ($start > 1)
                <a class="item" href="{{ $cases->url(1) }}">1</a>
                @if ($start > 2)
                    <span class="icon item disabled">...</span>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $current)
                    <span class="item active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="item" href="{{ $cases->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="icon item disabled">...</span>
                @endif
                <a class="item" href="{{ $cases->url($last-1) }}">{{ $last-1 }}</a>
                <a class="item" href="{{ $cases->url($last) }}">{{ $last }}</a>
            @endif

            @if ($cases->hasMorePages())
                <a class="icon item" href="{{ $cases->nextPageUrl() }}" rel="next" aria-label="Next »">›</a>
            @else
                <span class="icon item disabled" aria-disabled="true" aria-label="Next »">›</span>
            @endif
        </div>
    </div>
@endif

<style>
/* ▼ 絞り込みメニュー */
.cweb-filter-wrap{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:4px;}
.cweb-filter-toggle{border:none;background:transparent;font-size:10px;cursor:pointer;padding:0 2px;line-height:1;color:#374151;}
.cweb-filter-menu{
    position:absolute;top:100%;right:0;margin-top:4px;background:#ffffff;border:1px solid #d1d5db;border-radius:6px;
    padding:8px 10px;box-shadow:0 2px 6px rgba(0,0,0,.15);z-index:50;min-width:160px;display:none;
}
.cweb-filter-menu.is-open{display:block;}

@media (prefers-color-scheme: dark){
    .cweb-filter-toggle{color:#e5e7eb;}
    .cweb-filter-menu{background:#111827;border-color:#4b5563;box-shadow:0 2px 6px rgba(0,0,0,.6);}
    .cweb-filter-menu select{background:#111827;color:#e5e7eb;border:1px solid #4b5563;}
}

/* ▼ ページネーション中央寄せ */
.cweb-pagination-wrapper{margin-top:16px;display:flex;justify-content:center;}
</style>

{{-- ✅ フラッシュ自動消去JS（HTMLは上、JSは下） --}}
@if (session('ok'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('flash-ok');
    if (!el) return;

    setTimeout(() => {
      el.style.transition = 'opacity .25s ease, transform .25s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-4px)';
      setTimeout(() => el.remove(), 280);
    }, 3000);
  });
</script>
@endif

@endsection
