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
            <span>日本語 / EN</span>
            @auth
                <span>{{ auth()->user()->name }}</span>
            @endauth
        </div>
    </div>
</header>
@endsection


@section('content')
@if(session('ok'))
    <div style="margin-bottom:8px;color:#16a34a">{{ session('ok') }}</div>
@endif



{{-- タブ切り替え --}}
@php
    $tab = $tab ?? 'all';
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
          style="flex:0 0 auto; max-width:260px; width:100%;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div style="position:relative;width:100%;">
            <input type="text"
                   name="keyword"
                   value="{{ request('keyword') }}"
                   placeholder="Search…"
                   style="width:100%;
                          padding:10px 40px 10px 10px;  /* ← 高さちょいUP */
                          border-radius:6px;
                          border:1px solid #9ca3af;
                          box-sizing:border-box;">
            <span class="search-icon-main"
                  style="position:absolute;right:8px;top:50%;transform:translateY(-50%);">
            </span>
        </div>
    </form>

    <button type="button"
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
<div style="background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
        <tr style="background:#f3f4f6;">
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                管理番号
            </th>
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                ステータス
                <span class="filter-icon"></span>
            </th>
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                カテゴリー
                <span class="filter-icon"></span>
            </th>
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                対象製品
                <span class="filter-icon"></span>
            </th>
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                顧客名
            </th>
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                営業窓口
            </th>
            <th style="padding:8px 10px;text-align:left;font-weight:700;
                       border:1px solid #9ca3af;">
                費用負担
            </th>
            <th style="padding:8px 10px;text-align:right;font-weight:700;
                       border:1px solid #9ca3af;">
                月額費用
            </th>
        </tr>
        </thead>
        <tbody>
        @forelse($cases as $case)
            @php
                // カテゴリー表示
                $categories = [];
                if (!empty($case->categories)) {
                    $c = is_array($case->categories)
                        ? $case->categories
                        : json_decode($case->categories, true);
                    if (is_array($c)) {
                        foreach ($c as $v) {
                            if ($v === 'standard') $categories[] = '標準管理';
                            elseif ($v === 'pcn') $categories[] = 'PCN';
                            elseif ($v === 'other') $categories[] = 'その他要求';
                        }
                    }
                }
                $categoryLabel = $categories ? implode(' / ', $categories) : '-';

                $statusLabel = match($case->status ?? '') {
                    'active'  => 'アクティブ',
                    'closed'  => '廃止',
                    default   => '不明',
                };

                $productLabel = trim(($case->product_main ?? '').' '.($case->product_sub ?? ''));
            @endphp
            <tr>
                {{-- 案件行：左右の境目なし（td には border を入れない） --}}
                <td style="padding:6px 10px;color:#2563eb;font-weight:700;">
                    <a href="#"
                       style="color:#2563eb;text-decoration:none;">
                        {{ $case->manage_no }}
                    </a>
                </td>
                <td style="padding:6px 10px;color:#111827;">
                    {{ $statusLabel }}
                </td>
                <td style="padding:6px 10px;color:#111827;">
                    {{ $categoryLabel }}
                </td>
                <td style="padding:6px 10px;color:#111827;">
                    {{ $productLabel ?: '-' }}
                </td>
                <td style="padding:6px 10px;color:#111827;">
                    {{ $case->customer_name }}
                </td>
                <td style="padding:6px 10px;color:#111827;">
                    {{ $case->sales_employee_number ?? '' }}
                </td>
                <td style="padding:6px 10px;color:#111827;">
                    {{ $case->cost_owner_code ?? '' }}
                </td>
                <td style="padding:6px 10px;color:#111827;text-align:right;">
                    {{ $case->will_monthly ? number_format($case->will_monthly) : '-' }}
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

    {{-- 一番下だけ横線 --}}
    <div style="border-top:1px solid #e5e7eb;margin-top:4px;padding:6px 10px;">
        {{ $cases->links() }}
    </div>
</div>
@endsection
