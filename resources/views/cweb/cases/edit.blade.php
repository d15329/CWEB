@extends('cweb.layout')

@section('header')
@php
    $currentLocale = request()->route('locale') ?? app()->getLocale();
    $nextLocale = $currentLocale === 'ja' ? 'en' : 'ja';

    // locale は query に混ざってても無視
    $query = request()->except('locale');

    $switchUrl = route('cweb.cases.edit', array_merge(
        $query,
        ['locale' => $nextLocale, 'case' => $case->id]
    ));
@endphp


<header class="cweb-header">
  <div class="cweb-header-inner">
    <div class="cweb-header-left">
      <a href="{{ route('cweb.cases.index', ['locale' => $currentLocale]) }}" class="cweb-brand-link">C-WEB</a>

      <div style="font-weight:700;margin-left:12px;">
        {{ $case->manage_no }}
        <span style="font-size:13px;margin-left:2px;">
          ({{ __('cweb.labels.editing') }})
        </span>
      </div>
    </div>

    <div class="cweb-header-right">
      <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>

      <div class="cweb-header-lang">
        <a href="{{ $switchUrl }}" class="cweb-header-lang-toggle" style="text-decoration:none;">
          {{ $currentLocale === 'ja' ? 'EN' : '日本語' }}
        </a>
        
      </div>

      @auth
        <button type="button" class="cweb-header-user-toggle">
          {{ auth()->user()->name }}
        </button>
        {{-- debug --}}
<div style="font-size:12px;color:#fca5a5;">{{ $switchUrl }}</div>

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
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 8px;
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
    padding: .55em 0.7em;
    border-radius: .285rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 1px;
    font-size: 0.8em;
    font-weight: 600;
}
.cweb-search-btn:hover{ background: #1678c2; }

/* =========================
   モーダル本体（.ui.modal 系）
   ========================= */
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
    -webkit-user-select: text;
    -moz-user-select: text;
    user-select: text;
    outline: 0;
    font-size: 1rem;
    padding: 1.2rem 1.3rem 1rem;
    box-sizing: border-box;
    transition: transform .22s ease-out, opacity .22s ease-out;
    will-change: transform, opacity;
}

@media only screen and (min-width: 768px) {
    .ui.modal:not(.fullscreen),
    .ui.large.modal {
        width: 88%;
        margin: 0;
        max-width: 900px;
    }
}

.ui.modal.visible.active {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
    pointer-events: auto;
}

.ui.modal > .scrolling.content {
    max-height: calc(80vh - 110px);
    overflow-y: auto;
}

.ui.modal > .header { font-weight: 700; margin-bottom: .75rem; }
.ui.modal > .actions {
    margin-top: 1rem;
    padding-top: .75rem;
    border-top: 1px solid rgba(34, 36, 38, .15);
    text-align: right;
}
.ui.modal > .header.title_boader{
    font-size: 30px;
    font-weight: 700;
    color: #1b1c1d;
    margin-bottom: .75rem;
    padding-bottom: .4rem;
    border-bottom: 2px solid #2185d0;
}
.emp_l_s{
    height: 1px;
    background: #2185d0;
    margin: .2rem 0 .6rem;
}
.scrolling.content > *:first-child{ margin-top: 0 !important; }

/* =========================
   Dimmer（背景の黒いオーバーレイ）
   ========================= */
.ui.dimmer {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
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
.ui.button:hover { background: #cacbcd; color: rgba(0, 0, 0, .8); }
.ui.positive.button { background: #21ba45; color: #fff; }
.ui.positive.button:hover { background: #16ab39; color: #fff; }

/* =========================
   入力ボックス（.ui.icon.input）まわり
   ========================= */
.ui.icon.input{ position: relative; display: inline-block; }
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
    position: absolute;
    right: .6em;
    top: 50%;
    transform: translateY(-50%);
    background: #2185d0;
    border-radius: 999px;
    width: 1.8em;
    height: 1.8em;
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
}
.ui.icon.input > i.inverted.circular.search.link.icon.blue::before{
    content: "";
    display: block;
    width: 11px;
    height: 11px;
    border: 2px solid #fff;
    border-radius: 50%;
}
.ui.icon.input > i.inverted.circular.search.link.icon.blue::after{
    content: "";
    position: absolute;
    width: 7px;
    height: 2px;
    background: #fff;
    border-radius: 1px;
    right: 4px;
    bottom: 4px;
    transform-origin: left center;
    transform: rotate(45deg);
}

/* =========================
   Grid（.ui.two.column.grid）
   ========================= */
.ui.grid { display: flex; flex-direction: column; margin-top: 1rem; }
.ui.grid .row { display: flex; width: 100%; }
.ui.grid .column { flex: 1 0 0; padding-right: 1rem; }
.ui.grid .column:last-child { padding-right: 0; }

/* ★ ヘッダーと登録ボタンの隙間をなくす調整 */
.cweb-header{ margin-bottom: 0 !important; padding-bottom: 0 !important; }
form{ margin-top:0; padding-bottom: 0 }

.cweb-submit-bar{
    position: fixed;
    top: 45px;
    left: 0;
    right: 0;
    z-index: 45;
    background: var(--bg);
    padding: 8px 24px;
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
.cweb-submit-button:hover{ opacity:0.9; transform:translateY(-1px); }
.cweb-submit-button:active{ transform:translateY(0); box-shadow:0 2px 4px rgba(0,0,0,0.25); }

.scrolling.content{
    overflow-y: auto;
    max-height: calc(80vh - 110px);
    padding-top: 0 !important;
}

/* 右側（Q-WEB・言語・ユーザー名） */
.cweb-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #e5e7eb;
}

/* 言語ブロック */
.cweb-header-lang {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-left: 8px;
    padding-left: 12px;
}
.cweb-header-lang::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* 日本語/EN ボタン */
.cweb-header-lang-toggle {
    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    padding: 0 6px;
    line-height: 1.4;
    opacity: 0.75;
    transition: opacity .15s ease, background-color .15s ease, transform .04s ease;
}

/* ユーザー名ボタン */
.cweb-header-user-toggle {
    position: relative;
    margin-left: 8px;
    padding-left: 12px;
    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    line-height: 1.4;
    opacity: 0.75;
    transition: opacity .15s ease, background-color .15s ease, transform .04s ease;
}
.cweb-header-user-toggle::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

.cweb-header-lang-toggle:hover,
.cweb-header-user-toggle:hover {
    opacity: 1;
    background-color: rgba(255, 255, 255, 0.06);
}
.cweb-header-lang-toggle:active,
.cweb-header-user-toggle:active { transform: scale(0.97); }

@media (prefers-color-scheme: dark) {
    .cweb-header-right { color: #e5e7eb; }
    .cweb-header-lang::before,
    .cweb-header-user-toggle::before { background: rgba(75, 85, 99, 0.8); }
}

/* ★ヘッダー崩れ対策：左右を分離して右側を右寄せ固定 */
.cweb-header-inner{
  display:flex;
  align-items:center;
  justify-content:space-between;
  width:100%;
  gap:12px;
}
.cweb-header-left{
  display:flex;
  align-items:center;
  gap:12px;
  min-width:0;
}
.cweb-header-right{
  display:flex;
  align-items:center;
  gap:12px;
  margin-left:auto;
}
.cweb-header-left span{ opacity: 1; }

</style>


@php
    // ▼ 営業窓口（社員番号 + 名前）
    $salesEmployeeNumber = $case->sales_contact_employee_number ?? null;
    $salesEmployeeName   = optional(
        \App\Models\User::where('employee_number', $salesEmployeeNumber)->first()
    )->name;

    // ▼ 情報共有者（sharedUsers リレーションから role=shared のみ）
    $sharedUsers = collect($case->sharedUsers ?? [])
        ->filter(fn($row) => ($row->role ?? null) === 'shared')
        ->values();

    // ▼ PCN / その他要求 / Will 分配
    $pcnItems        = collect($case->pcnItems ?? []);
    $otherReqs       = collect($case->otherRequirements ?? []);
    $willAllocations = collect($case->willAllocations ?? []);

    // ▼ その他要求対応者用：社員番号 → User マップ
    $otherEmpNumbers = $otherReqs->pluck('responsible_employee_number')
                                 ->filter()
                                 ->unique()
                                 ->values()
                                 ->all();
    $otherEmployees = $otherEmpNumbers
        ? \App\Models\User::whereIn('employee_number', $otherEmpNumbers)->get()->keyBy('employee_number')
        : collect();

    // ▼ カテゴリー（チェックON状態の初期値）
    $defaultCategories = [];
    if ($case->category_standard ?? false) $defaultCategories[] = 'standard';
    if ($case->category_pcn ?? false)      $defaultCategories[] = 'pcn';
    if ($case->category_other ?? false)    $defaultCategories[] = 'other';
@endphp

<form method="POST" action="{{ route('cweb.cases.update', ['locale' => request()->route('locale'), 'case' => $case->id]) }}">


    @csrf
    @method('PUT')

    {{-- 更新ボタン（スクロールしても上に固定） --}}
    <div class="cweb-submit-bar">
        <button type="submit" class="btn btn-accent cweb-submit-button">
            {{ __('cweb.actions.update') }}
        </button>
    </div>

@php
    // 1列目・2列目のセル共通スタイル
    $rowStyle = '';

    $labelCell = implode('', [
        'padding:10px 10px 10px 32px;',
        'width:18%;',
        'vertical-align:middle;',
        'color:#000;',
        'background:#e5e7eb;',
        'border-right:1px solid #d1d5db;',
        'border-bottom:none;',
        'box-sizing:border-box;',
        'font-weight:700;',
    ]);

    $inputCell = 'padding:10px 10px;background:var(--bg);border-bottom:none;vertical-align:middle;';
@endphp

    {{-- 本体テーブル --}}
    <div style="margin-top:60px;background:#0b1029;border-radius:8px;padding:0;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">

            {{-- 1行目：営業窓口（必須） --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">
                    <span style="color:red;">＊</span>{{ __('cweb.form.sales_contact') }}
                </td>
                <td style="{{ $inputCell }}">

@php
    $salesNo   = old('sales_employee_number', $salesEmployeeNumber);
    $salesName = old('sales_employee_name', $salesEmployeeName);
@endphp

<span id="sales-emp-display"
      style="display:{{ ($salesNo || $salesName) ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
    @if($salesNo || $salesName)
        {{ $salesNo }} / {{ $salesName }}
    @endif
</span>

                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

                        {{-- 保存用フィールド --}}
                        <input type="hidden" name="sales_employee_number" id="sales-emp-no" value="{{ $salesNo }}">
                        <input type="hidden" name="sales_employee_name"   id="sales-emp-name" value="{{ $salesName }}">

                        {{-- 選択ボタン --}}
                        <button type="button"
                                class="btn"
                                style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                                onclick="openPopupA()">
                            {{ __('cweb.actions.select') }}
                        </button>
                    </div>

                    @error('sales_employee_number')
                        <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </td>
            </tr>

            {{-- 2行目：情報共有者 --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">{{ __('cweb.form.shared_users') }}</td>
                <td style="{{ $inputCell }}">

                    {{-- hidden（社員番号） --}}
                    <div id="shared-hidden-container">
                        @php
                            $oldSharedNos = (array)old('shared_employee_numbers', []);

                            if (!empty($oldSharedNos)) {
                                $sharedForDisplay = \App\Models\User::whereIn('employee_number', $oldSharedNos)
                                    ->get()
                                    ->keyBy('employee_number');
                            } else {
                                $sharedForDisplay = $sharedUsers
                                    ->map(function($row){ return $row->user; })
                                    ->filter()
                                    ->keyBy('employee_number');
                                $oldSharedNos = $sharedForDisplay->keys()->all();
                            }
                        @endphp

                        @foreach($oldSharedNos as $empNo)
                            <input type="hidden" name="shared_employee_numbers[]" value="{{ $empNo }}">
                        @endforeach
                    </div>

                    {{-- 表示用：社員番号 / 名前 を「、」区切り --}}
                    <div id="shared-display" style="margin-bottom:4px;color:var(--text);font-size:13px;">
                        @if(empty($oldSharedNos))
                            -
                        @else
                            @php $first = true; @endphp
                            @foreach($oldSharedNos as $empNo)
                                @php $u = $sharedForDisplay[$empNo] ?? null; @endphp
                                @if(!$first) 、@endif
                                @php $first = false; @endphp
                                @if($u)
                                    {{ $u->employee_number }} / {{ $u->name }}
                                @else
                                    {{ $empNo }}
                                @endif
                            @endforeach
                        @endif
                    </div>

                    <button type="button"
                            class="btn"
                            style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                            onclick="openPopupB()">
                        {{ __('cweb.actions.select') }}
                    </button>

                    <div style="margin-top:4px;font-size:11px;color:var(--text);">
                        {{ __('cweb.form.shared_note') }}
                    </div>
                </td>
            </tr>

            {{-- 3行目：費用負担先（必須） --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">
                    <span style="color:red;">＊</span>{{ __('cweb.form.cost_owner') }}
                </td>
                <td style="{{ $inputCell }}">

@php
    $costOwnerCode = old('cost_owner_code', $case->cost_responsible_code);
    $costOwnerName = old('cost_owner_name', $case->cost_responsible_name ?? null);

    if ($costOwnerCode && $costOwnerName) {
        $costOwnerLabel = $costOwnerCode . ' / ' . $costOwnerName;
    } else {
        $costOwnerLabel = $costOwnerCode;
    }
@endphp

                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

                        <input type="hidden" name="cost_owner_code" id="cost-owner-code" value="{{ $costOwnerCode }}">
                        <input type="hidden" name="cost_owner_name" id="cost-owner-name" value="{{ $costOwnerName }}">

                        <span id="cost-owner-display"
                              style="display:{{ $costOwnerCode ? 'inline-block' : 'none' }};font-weight:700;color:var(--text);">
                            {{ $costOwnerLabel }}
                        </span>

                        <button type="button"
                                class="btn"
                                style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                                onclick="openPopupC()">
                            {{ __('cweb.actions.select') }}
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
                    <span style="color:red;">＊</span>{{ __('cweb.form.customer') }}
                </td>
                <td style="{{ $inputCell }}">
                    <input type="text"
                           name="customer_name"
                           value="{{ old('customer_name', $case->customer_name) }}"
                           style="width:220px;padding:6px 8px;border-radius:4px;border:1px solid #9ca3af;">
                    @error('customer_name')
                        <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </td>
            </tr>

            {{-- 5行目：カテゴリー（必須・複数可） --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">
                    <span style="color:red;">＊</span>{{ __('cweb.form.category') }}
                </td>
                <td style="{{ $inputCell }}">
@php
    $oldCategories = (array)old('categories', $defaultCategories);
@endphp
                    <label style="color:var(--text);">
                        <input type="checkbox" name="categories[]" value="standard"
                            {{ in_array('standard', $oldCategories, true) ? 'checked' : '' }}>
                        {{ __('cweb.categories.standard') }}
                    </label>
                    <label style="margin-left:12px;color:var(--text);">
                        <input type="checkbox" name="categories[]" value="pcn"
                            {{ in_array('pcn', $oldCategories, true) ? 'checked' : '' }}>
                        {{ __('cweb.categories.pcn') }}
                    </label>
                    <label style="margin-left:12px;color:var(--text);">
                        <input type="checkbox" name="categories[]" value="other"
                            {{ in_array('other', $oldCategories, true) ? 'checked' : '' }}>
                        {{ __('cweb.categories.other') }}
                    </label>

                    @error('categories')
                        <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </td>
            </tr>

            {{-- 6行目：対象製品（必須・プルダウン2つ） --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">
                    <span style="color:red;">＊</span>{{ __('cweb.form.product') }}
                </td>
                <td style="{{ $inputCell }}">
@php
    $oldMain = old('product_main', $case->product_group);
    $oldSub  = old('product_sub',  $case->product_code);
@endphp

                    <select name="product_main"
                            id="product-main"
                            style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                        <option value="" {{ $oldMain === '' ? 'selected' : '' }} style="color:#9ca3af;">
                            {{ __('cweb.form.choose') }}
                        </option>
                        <option value="HogoMax-内製品"   {{ $oldMain === 'HogoMax-内製品' ? 'selected' : '' }}>HogoMax-内製品</option>
                        <option value="HogoMax-OEM品"    {{ $oldMain === 'HogoMax-OEM品' ? 'selected' : '' }}>HogoMax-OEM品</option>
                        <option value="StayClean-内製品" {{ $oldMain === 'StayClean-内製品' ? 'selected' : '' }}>StayClean-内製品</option>
                        <option value="StayClean-OEM品"  {{ $oldMain === 'StayClean-OEM品' ? 'selected' : '' }}>StayClean-OEM品</option>
                        <option value="ResiFlat-内製品"   {{ $oldMain === 'ResiFlat-内製品' ? 'selected' : '' }}>ResiFlat-内製品</option>
                        <option value="その他"           {{ $oldMain === 'その他' ? 'selected' : '' }}>その他</option>
                    </select>

                    <select name="product_sub"
                            id="product-sub"
                            style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;margin-left:8px;">
                        <option value="" style="color:#9ca3af;">{{ __('cweb.form.choose') }}</option>
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
                <td style="{{ $labelCell }}">{{ __('cweb.show.pcn_items') }}</td>
                <td style="{{ $inputCell }}">
                    <div id="pcn-rows">
@php
    $pcnOld = old('pcn_items');

    if (is_null($pcnOld)) {
        $pcnOld = $pcnItems->map(function ($item) {
            return [
                'category'      => $item->category,
                'title'         => $item->title,
                'months_before' => $item->months_before,
            ];
        })->toArray();

        if (empty($pcnOld)) {
            $pcnOld = [
                ['category' => null, 'title' => null, 'months_before' => null],
            ];
        }
    }
@endphp

                        @foreach($pcnOld as $i => $item)
                            <div class="pcn-row" style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                                <select name="pcn_items[{{ $i }}][category]"
                                        style="padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                                    <option value="">{{ __('cweb.form.choose') }}</option>
                                    <option value="spec"        {{ ($item['category'] ?? '') === 'spec' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.spec') }}</option>
                                    <option value="man"         {{ ($item['category'] ?? '') === 'man' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.man') }}</option>
                                    <option value="machine"     {{ ($item['category'] ?? '') === 'machine' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.machine') }}</option>
                                    <option value="material"    {{ ($item['category'] ?? '') === 'material' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.material') }}</option>
                                    <option value="method"      {{ ($item['category'] ?? '') === 'method' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.method') }}</option>
                                    <option value="measurement" {{ ($item['category'] ?? '') === 'measurement' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.measurement') }}</option>
                                    <option value="environment" {{ ($item['category'] ?? '') === 'environment' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.environment') }}</option>
                                    <option value="other"       {{ ($item['category'] ?? '') === 'other' ? 'selected' : '' }}>{{ __('cweb.pcn.categories.other') }}</option>
                                </select>

                                <input type="text"
                                       name="pcn_items[{{ $i }}][title]"
                                       value="{{ $item['title'] ?? '' }}"
                                       placeholder="{{ __('cweb.form.pcn_title_placeholder') }}"
                                       style="width:200px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">

                                <input type="number"
                                       name="pcn_items[{{ $i }}][months_before]"
                                       value="{{ $item['months_before'] ?? '' }}"
                                       min="0"
                                       style="width:50px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                                <span style="color:var(--text);">{{ __('cweb.pcn.months_before_suffix') }}</span>

                                <button type="button"
                                        onclick="removePcnRow(this)"
                                        style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                                    {{ __('cweb.actions.remove') }}
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button"
                            onclick="addPcnRow()"
                            style="margin-top:4px;background:#b91c1c;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:12px;">
                        {{ __('cweb.actions.add_row') }}
                    </button>
                </td>
            </tr>

            {{-- 8行目：その他要求 --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">{{ __('cweb.show.other_requests') }}</td>
                <td style="{{ $inputCell }}">
                    <div id="other-rows">
@php
    $otherOld = old('other_requirements');

    if (is_null($otherOld)) {
        $otherOld = $otherReqs->map(function ($req) use ($otherEmployees) {
            $empNo = $req->responsible_employee_number ?? null;
            $emp   = $empNo ? ($otherEmployees[$empNo] ?? null) : null;
            $label = $empNo && $emp ? ($empNo.' / '.$emp->name) : ($empNo ?? '');

            return [
                'content'                    => $req->content,
                'responsible_employee_number'=> $empNo,
                'responsible_label'          => $label,
            ];
        })->toArray();

        if (empty($otherOld)) {
            $otherOld = [
                ['content' => null, 'responsible_employee_number' => null, 'responsible_label' => null],
            ];
        }
    }
@endphp

                        @foreach($otherOld as $i => $row)
                            @php $respLabel = $row['responsible_label'] ?? ''; @endphp

                            <div class="other-row" style="margin-bottom:8px;">
                                <textarea name="other_requirements[{{ $i }}][content]"
                                          placeholder="{{ __('cweb.form.other_content_placeholder') }}"
                                          style="width:30%;height:40px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;resize:none;">{{ $row['content'] ?? '' }}</textarea>

                                <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span style="font-weight:700;color:var(--text);">{{ __('cweb.show.responsible') }}：</span>

                                    <input type="hidden"
                                           name="other_requirements[{{ $i }}][responsible_employee_number]"
                                           id="other-resp-no-{{ $i }}"
                                           value="{{ $row['responsible_employee_number'] ?? '' }}">

                                    <input type="hidden"
                                           name="other_requirements[{{ $i }}][responsible_label]"
                                           id="other-resp-label-{{ $i }}"
                                           value="{{ $respLabel }}">

                                    <span id="other-resp-display-{{ $i }}"
                                          style="display:{{ $respLabel ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
                                        {{ $respLabel }}
                                    </span>

                                    <button type="button"
                                            class="btn"
                                            style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                                            onclick="openPopupAForOther({{ $i }})">
                                        {{ __('cweb.actions.select') }}
                                    </button>

                                    <button type="button"
                                            onclick="removeOtherRow(this)"
                                            style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                                        {{ __('cweb.actions.remove') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button"
                            onclick="addOtherRow()"
                            style="margin-top:4px;background:#b91c1c;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:12px;">
                        {{ __('cweb.actions.add_row') }}
                    </button>
                </td>
            </tr>

            {{-- 9行目：Will --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">{{ __('cweb.show.will') }}</td>
                <td style="{{ $inputCell }}">
                    <span style="color:var(--text);font-weight:700;">{{ __('cweb.show.will_initial') }}：</span>
                    <input type="number"
                           name="will_initial"
                           value="{{ old('will_initial', $case->will_registration_cost) }}"
                           min="0"
                           style="width:120px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                    <span style="color:var(--text);font-weight:700;">will</span>

                    &nbsp;&nbsp;
                    <span style="color:var(--text);font-weight:700;">{{ __('cweb.show.will_monthly') }}：</span>
                    <input type="number"
                           name="will_monthly"
                           value="{{ old('will_monthly', $case->will_monthly_cost) }}"
                           min="0"
                           style="width:120px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
                    <span style="color:var(--text);font-weight:700;">will</span>
                </td>
            </tr>

            {{-- 10行目：月額管理費の分配 --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">{{ __('cweb.show.will_allocations') }}</td>
                <td style="{{ $inputCell }}">
                    @if($errors->has('will_allocations'))
                        <div style="color:#dc2626; font-size:14px; margin:0 0 4px;">
                            {{ $errors->first('will_allocations') }}
                        </div>
                    @endif

                    <div id="will-rows">
@php
    $allocOld = old('will_allocations');

    if (is_null($allocOld)) {
        $allocOld = $willAllocations->map(function ($alloc) {
            return [
                'employee_number' => $alloc->employee_number,
                'employee_name'   => $alloc->employee_name,
                'percentage'      => $alloc->percentage,
            ];
        })->toArray();

        if (empty($allocOld)) {
            $allocOld = [
                ['employee_number' => null, 'employee_name' => null, 'percentage' => null],
            ];
        }
    }
@endphp

                        @foreach($allocOld as $i => $alloc)
                            <div class="will-row"
                                 style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">

                                <input type="hidden"
                                       name="will_allocations[{{ $i }}][employee_number]"
                                       id="will-emp-no-{{ $i }}"
                                       value="{{ $alloc['employee_number'] ?? '' }}">
                                <input type="hidden"
                                       name="will_allocations[{{ $i }}][employee_name]"
                                       id="will-emp-name-{{ $i }}"
                                       value="{{ $alloc['employee_name'] ?? '' }}">

                                <button type="button"
                                        class="btn"
                                        style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:4px;border:none;font-size:12px;"
                                        onclick="openPopupAForWill({{ $i }})">
                                    {{ __('cweb.actions.select') }}
                                </button>

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
                                    {{ __('cweb.actions.remove') }}
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button"
                            onclick="addWillRow()"
                            style="margin-top:4px;background:#b91c1c;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:12px;">
                        {{ __('cweb.actions.add_row') }}
                    </button>
                </td>
            </tr>

            {{-- 11行目：関連Q-WEB --}}
            <tr style="{{ $rowStyle }}">
                <td style="{{ $labelCell }}">{{ __('cweb.show.related_qweb') }}</td>
                <td style="{{ $inputCell }}">
                    <textarea name="related_qweb"
                              rows="2"
                              style="width:40%;height:40px;padding:6px 8px;border-radius:4px;border:1px solid #9ca3af;resize:none;">{{ old('related_qweb', $case->related_qweb) }}</textarea>
                </td>
            </tr>

        </table>
    </div>

</form>

{{-- ▼ 社員検索モーダルのオーバーレイ --}}
<div class="ui dimmer modals page" id="emp-modal-overlay"></div>

{{-- ▼ 社員検索モーダル本体 --}}
<div class="ui large modal transition front" id="empsearch">
    <div class="header title_boader" id="emplbltype1">{{ __('cweb.emp_modal.title_sales') }}</div>
    <div class="header title_boader" id="emplbltype2" style="display: none;">{{ __('cweb.emp_modal.title_shared') }}</div>
    <div class="header title_boader" id="emplbltype3" style="display: none;">{{ __('cweb.emp_modal.title_cost') }}</div>
    <div class="header title_boader" id="emplbltype4" style="display: none;">{{ __('cweb.emp_modal.title_other') }}</div>

    <input type="hidden" id="empworkmode" value="0">

    <div class="scrolling content" style="min-height: 300px">
        <div class="cweb-search-group">
            <input type="text"
                   placeholder="{{ __('cweb.search.keyword_placeholder') }}"
                   data-content="{{ __('cweb.search.placeholder') }}"
                   id="empkeyword"
                   autocomplete="off">

            <button class="cweb-search-btn" id="empicon" type="button">
                <i class="search icon"></i>
                {{ __('cweb.actions.search') }}
            </button>
        </div>

        <div class="ui two column grid emplist">
            <div class="row" style="margin-top: 1rem">
                <div class="column">
                    <label class="emp_f_s">{{ __('cweb.emp_modal.search_result') }}</label>
                    <div class="emp_l_s"></div>
                    <div class="ui middle divided selection list" id="EmpSearchResult"></div>
                </div>
                <div class="column">
                    <label class="emp_f_s">{{ __('cweb.emp_modal.selected') }}</label>
                    <div class="emp_l_s"></div>
                    <div class="ui middle divided selection list" id="empselectedlist"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        <div class="ui button positive ok" id="emp-ok-btn">{{ __('cweb.common.ok') }}</div>
        <div class="ui button cancel" id="emp-cancel-btn">{{ __('cweb.common.cancel') }}</div>
    </div>
</div>


{{-- 更新完了モーダル --}}
<div id="success-modal-overlay" class="ui dimmer" style="display:none;"></div>

<div id="success-modal" class="ui small modal" style="display:block; opacity:0; pointer-events:none;">
    <div class="header">{{ __('cweb.modal.done_title') }}</div>
    <div class="content" style="text-align:center; font-size:16px; padding:20px;">
        {{ __('cweb.modal.updated') }}
    </div>
    <div class="actions" style="text-align:center;">
        <button type="button" class="ui blue button" onclick="closeSuccessModal()">{{ __('cweb.common.ok') }}</button>
    </div>
</div>

<script>
/**
 * Blade内の JS で使う翻訳（行追加した瞬間に日本語直書きが混ざるのを防ぐ）
 */
const I18N = {
  actions: {
    select: @json(__('cweb.actions.select')),
    remove: @json(__('cweb.actions.remove')),
    addRow:  @json(__('cweb.actions.add_row')),
    search:  @json(__('cweb.actions.search')),
  },
  common: {
    ok: @json(__('cweb.common.ok')),
    cancel: @json(__('cweb.common.cancel')),
  },
  form: {
    choose: @json(__('cweb.form.choose')),
    pcnTitlePh: @json(__('cweb.form.pcn_title_placeholder')),
    otherContentPh: @json(__('cweb.form.other_content_placeholder')),
  },
  pcn: {
    monthsBeforeSuffix: @json(__('cweb.pcn.months_before_suffix')),
    categories: {
      spec: @json(__('cweb.pcn.categories.spec')),
      man: @json(__('cweb.pcn.categories.man')),
      machine: @json(__('cweb.pcn.categories.machine')),
      material: @json(__('cweb.pcn.categories.material')),
      method: @json(__('cweb.pcn.categories.method')),
      measurement: @json(__('cweb.pcn.categories.measurement')),
      environment: @json(__('cweb.pcn.categories.environment')),
      other: @json(__('cweb.pcn.categories.other')),
    }
  },
  empModal: {
    search_result: @json(__('cweb.emp_modal.search_result')),
    selected: @json(__('cweb.emp_modal.selected')),
    title_sales: @json(__('cweb.emp_modal.title_sales')),
    title_shared:@json(__('cweb.emp_modal.title_shared')),
    title_cost:  @json(__('cweb.emp_modal.title_cost')),
    title_other: @json(__('cweb.emp_modal.title_other')),
    title_will:  @json(__('cweb.emp_modal.title_will')),
  }
};

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
            <option value="">${I18N.form.choose}</option>
            <option value="spec">${I18N.pcn.categories.spec}</option>
            <option value="man">${I18N.pcn.categories.man}</option>
            <option value="machine">${I18N.pcn.categories.machine}</option>
            <option value="material">${I18N.pcn.categories.material}</option>
            <option value="method">${I18N.pcn.categories.method}</option>
            <option value="measurement">${I18N.pcn.categories.measurement}</option>
            <option value="environment">${I18N.pcn.categories.environment}</option>
            <option value="other">${I18N.pcn.categories.other}</option>
        </select>
        <input type="text" name="pcn_items[${index}][title]"
               placeholder="${I18N.form.pcnTitlePh}"
               style="width:200px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
        <input type="number" name="pcn_items[${index}][months_before]"
               min="0"
               style="width:50px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;">
        <span style="color:var(--text);">${I18N.pcn.monthsBeforeSuffix}</span>
        <button type="button"
                onclick="removePcnRow(this)"
                style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
            ${I18N.actions.remove}
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
                  placeholder="${I18N.form.otherContentPh}"
                  style="width:30%;height:40px;padding:4px 6px;border-radius:4px;border:1px solid #9ca3af;resize:none;"></textarea>

        <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span style="font-weight:700;color:var(--text);">{{ __('cweb.show.responsible') }}：</span>

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
                ${I18N.actions.select}
            </button>

            <button type="button"
                    onclick="removeOtherRow(this)"
                    style="background:#4b5563;border:none;border-radius:4px;color:#e5e7eb;padding:2px 6px;font-size:11px;">
                ${I18N.actions.remove}
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
            ${I18N.actions.select}
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
            ${I18N.actions.remove}
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
    'ResiFlat-内製品':   ['103'],
    'その他':           []
};

function refreshProductSubOptions(selectedMain, selectedSub) {
    const subSelect = document.getElementById('product-sub');
    if (!subSelect) return;

    while (subSelect.firstChild) subSelect.removeChild(subSelect.firstChild);

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = I18N.form.choose;
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

    if (selectedSub && codes.includes(selectedSub)) subSelect.value = selectedSub;
    else subSelect.value = '';
}

document.addEventListener('DOMContentLoaded', function () {
    const mainSelect = document.getElementById('product-main');
    const subSelect  = document.getElementById('product-sub');
    if (!mainSelect || !subSelect) return;

    const initialMain = mainSelect.value;
    const initialSub  = @json(old('product_sub', $oldSub ?? ''));

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
    if (searchIcon) searchIcon.addEventListener('click', rebuildEmpLists);
    if (okBtn) okBtn.addEventListener('click', applyEmpSelection);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEmpModal);
});

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

    // 見出し切り替え（翻訳）
    if (context === 'sales') {
        const h = document.getElementById('emplbltype1');
        if (h) { h.style.display = 'block'; h.textContent = I18N.empModal.title_sales; }
    } else if (context === 'shared') {
        const h = document.getElementById('emplbltype2');
        if (h) { h.style.display = 'block'; h.textContent = I18N.empModal.title_shared; }
    } else if (context === 'cost') {
        const h = document.getElementById('emplbltype3');
        if (h) { h.style.display = 'block'; h.textContent = I18N.empModal.title_cost; }
    } else if (context === 'other') {
        const h = document.getElementById('emplbltype1');
        if (h) { h.style.display = 'block'; h.textContent = I18N.empModal.title_other; }
    } else if (context === 'will') {
        const h = document.getElementById('emplbltype1');
        if (h) { h.style.display = 'block'; h.textContent = I18N.empModal.title_will; }
    }

    // キーワード初期化
    const kw = document.getElementById('empkeyword');
    if (kw) kw.value = '';

    // 一時選択を hidden から復元
    initTempSelectionFromHidden();

    // リスト再描画
    rebuildEmpLists();

    // オーバーレイ＆モーダル表示
    const overlay = document.getElementById('emp-modal-overlay');
    const modal   = document.getElementById('empsearch');

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
    }

    tempSelectedEmps = [];
    tempSelectedCost = null;
    currentOtherIndex = null;
    currentWillIndex  = null;
}

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
                if (emp) tempSelectedEmps.push({ ...emp });
            });
        }

    } else if (empContext === 'cost') {
        const codeEl = document.getElementById('cost-owner-code');
        const nameEl = document.getElementById('cost-owner-name');
        const code   = codeEl?.value || '';
        const name   = nameEl?.value || '';

        if (code && name) tempSelectedCost = { code, name };

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

    // ▼ 社員を使うケース
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

    // ▼ 費用負担先
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
        if (idx >= 0) tempSelectedEmps.splice(idx, 1);
        else {
            const emp = EMP_MASTER.find(e => e.no === no);
            if (emp) tempSelectedEmps.push({ ...emp });
        }
    }
    rebuildEmpLists();
}
function toggleCostSelect(code) {
    if (tempSelectedCost && tempSelectedCost.code === code) {
        tempSelectedCost = null;
    } else {
        const co = COST_OWNERS.find(c => c.code === code);
        if (co) tempSelectedCost = { ...co };
    }
    rebuildEmpLists();
}

// ========== OK で反映 ==========
function applyEmpSelection() {
    if (empContext === 'sales') {
        const hiddenNo   = document.getElementById('sales-emp-no');
        const hiddenName = document.getElementById('sales-emp-name');
        const display    = document.getElementById('sales-emp-display');

        if (tempSelectedEmps.length > 0) {
            const emp = tempSelectedEmps[0];
            hiddenNo.value   = emp.no;
            hiddenName.value = emp.name;
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

            if (tempSelectedEmps.length === 0) {
                displayContainer.textContent = '-';
            } else {
                tempSelectedEmps.forEach((emp, index) => {
                    const hidden = document.createElement('input');
                    hidden.type  = 'hidden';
                    hidden.name  = 'shared_employee_numbers[]';
                    hidden.value = emp.no;
                    hiddenContainer.appendChild(hidden);

                    const textNode = document.createTextNode(emp.no + ' / ' + emp.name);
                    displayContainer.appendChild(textNode);

                    if (index < tempSelectedEmps.length - 1) {
                        displayContainer.appendChild(document.createTextNode('、'));
                    }
                });
            }
        }

    } else if (empContext === 'cost') {
        const hiddenCode = document.getElementById('cost-owner-code');
        const hiddenName = document.getElementById('cost-owner-name');
        const display    = document.getElementById('cost-owner-display');

        if (tempSelectedCost) {
            hiddenCode.value = tempSelectedCost.code;
            hiddenName.value = tempSelectedCost.name;
            display.textContent = tempSelectedCost.code + ' / ' + tempSelectedCost.name;
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
                lblEl.value = (no && name) ? (no + ' / ' + name) : '';
                disp.textContent = (no && name) ? (no + ' / ' + name) : '';
                disp.style.display = (no && name) ? 'inline-block' : 'none';
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

// ===== 更新完了モーダル =====
function showSuccessModal() {
    const overlay = document.getElementById('success-modal-overlay');
    const modal   = document.getElementById('success-modal');
    if (!overlay || !modal) return;

    overlay.classList.add('visible', 'active');
    overlay.style.display = 'flex';
    overlay.style.opacity = '1';

    modal.classList.add('visible', 'active');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}
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

    window.location.href = @json(route('cweb.cases.index', ['locale' => request()->route('locale')]));


}
</script>

@endsection
