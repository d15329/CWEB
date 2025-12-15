@extends('cweb.layout')

@section('header')
@php
    $currentLocale = app()->getLocale();
    $nextLocale = $currentLocale === 'ja' ? 'en' : 'ja';
@endphp

<header class="cweb-header">
    <div class="cweb-header-inner">

        <div class="cweb-header-left">
            <a href="{{ route('cweb.cases.index') }}" class="cweb-brand-link">
                C-WEB
            </a>

            <div style="font-weight:700;margin-left:12px;">
                {{ $nextManagementNo }}
                <span style="font-size:13px;margin-left:2px;">
                    ({{ __('cweb.labels.in_progress') }})
                </span>
            </div>
        </div>

        <div class="cweb-header-right">
            <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>

            {{-- ✅ 言語トグル：ボタンじゃなくリンクで /ja ⇔ /en に切り替え --}}
            <div class="cweb-header-lang">
<a class="cweb-header-lang-toggle"
   href="{{ route('lang.switch', ['lang' => $nextLocale]) }}">
  {{ $currentLocale === 'ja' ? 'EN' : '日本語' }}
</a>

            </div>

            @auth
                <button type="button" class="cweb-header-user-toggle">
                    {{ auth()->user()->name }}
                </button>
            @endauth
        </div>

    </div>
</header>
@endsection

@section('content')

<style>
/* ここから下は、あなたの貼ったCSSを基本そのまま（必要最小の修正のみ） */

/* ヘッダーの「（編集中）」タグ */
.cweb-header-editing-tag{
    margin-left: 6px;
    font-size: 11px;
    opacity: 0.85;
    color: #fbbf24;
}

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
    border-radius: .28571429rem;
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
   Dimmer（背景）
   ========================= */ 
/* .ui.dimmer {
    display: none;
    position: fixed;
    inset: 0;
    padding: 1em;
    background: rgba(0, 0, 0, .85);
    opacity: 0;
    transition: all .5s linear;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.ui.dimmer.visible.active {
    display: flex;
    opacity: 1;
}

/* ボタン */
.ui.button {
    display: inline-block;
    padding: .7em 1.6em;
    margin-left: .4em;
    font-size: .95rem;
    font-weight: 700;
    border-radius: .28571429rem;
    border: none;
    background: #e0e1e2;
    color: rgba(0, 0, 0, .6);
    cursor: pointer;
}
.ui.button:hover { background: #cacbcd; color: rgba(0,0,0,.8); }
.ui.positive.button { background: #21ba45; color:#fff; }
.ui.positive.button:hover { background:#16ab39; color:#fff; }

/* ★ ヘッダーと登録ボタンの隙間をなくす調整 */
.cweb-header{ margin-bottom: 0 !important; padding-bottom: 0 !important; }

form{ margin-top:0; padding-bottom: 0; }

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

/* ===== 登録完了モーダル（success-modal）だけ中央固定 ===== */
#success-modal-overlay{
  position: fixed;
  inset: 0;
  z-index: 5000;
  display: none;                 /* showSuccessModal() で flex */
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,.85);
  padding: 16px;                 /* 端に寄らないように */
}

#success-modal{
  width: min(520px, 92vw);
  max-height: 80vh;
  overflow: auto;

  background: #fff;
  border-radius: .28571429rem;
  box-shadow: 1px 3px 3px 0 rgba(0,0,0,.2), 1px 3px 15px 2px rgba(0,0,0,.2);
  padding: 1.2rem 1.3rem 1rem;
}
</style>

<form method="POST" action="{{ route('cweb.cases.store', ['locale' => request()->route('locale') ?? app()->getLocale()]) }}">

    @csrf

    {{-- 登録ボタン（スクロールしても上に固定） --}}
    <div class="cweb-submit-bar">
        <button type="submit" class="btn btn-accent cweb-submit-button">
            {{ __('cweb.actions.register') }}
        </button>
    </div>

    {{-- 1列11行テーブル --}}
    <div style="margin-top:60px;background:#0b1029;border-radius:8px;padding:0;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
@php
    $rowStyle = '';

    $labelCell = implode('', [
        'padding:10px 10px 10px 32px;',
        'width:18%;',
        'vertical-align:middle;',
        'color:#000;',
        'background:#e5e7eb;',
        'border-right:1px solid #d1d5db;',
        'border-bottom:1px solid #e5e7eb;',
        'box-sizing:border-box;',
        'font-weight:700;',
    ]);

    $inputCell = 'padding:10px 10px;background:var(--bg);border-bottom:none;vertical-align:middle;';
@endphp

{{-- 1行目：営業窓口（必須） --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">
        <span style="color:red;">＊</span>{{ __('cweb.form.sales_contact') }}
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
            <input type="hidden" name="sales_employee_number" id="sales-emp-no" value="{{ old('sales_employee_number') }}">
            <input type="hidden" name="sales_employee_name"   id="sales-emp-name" value="{{ old('sales_employee_name') }}">

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
        @php
            $oldSharedNos    = (array)old('shared_employee_numbers', []);
            $oldSharedLabels = (array)old('shared_employee_labels', []);
        @endphp

        <div id="shared-hidden-container">
            @foreach($oldSharedNos as $i => $empNo)
                <input type="hidden" name="shared_employee_numbers[]" value="{{ $empNo }}">
                <input type="hidden" name="shared_employee_labels[]"  value="{{ $oldSharedLabels[$i] ?? '' }}">
            @endforeach
        </div>

        <div id="shared-display" style="margin-bottom:4px;">
            @foreach($oldSharedNos as $i => $empNo)
                @php $label = $oldSharedLabels[$i] ?? ''; @endphp
                @if($empNo || $label)
                    <div style="color:var(--text);font-weight:700;">
                        {{ $empNo }}@if($label) / {{ $label }} @endif
                    </div>
                @endif
            @endforeach
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
        @php $costOwnerName = old('cost_owner_name'); @endphp

        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <input type="hidden" name="cost_owner_code" id="cost-owner-code" value="{{ old('cost_owner_code') }}">
            <input type="hidden" name="cost_owner_name" id="cost-owner-name" value="{{ old('cost_owner_name') }}">

            <span id="cost-owner-display"
                  style="display:{{ $costOwnerName ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
                {{ $costOwnerName }}
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
        <span style="color:red;">＊</span>{{ __('cweb.form.category') }}
    </td>
    <td style="{{ $inputCell }}">
        @php $oldCategories = (array)old('categories', []); @endphp

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

        {{-- ✅ typo 修正：ccolor → color --}}
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

{{-- 6行目：対象製品（必須） --}}
<tr style="{{ $rowStyle }}">
    <td style="{{ $labelCell }}">
        <span style="color:red;">＊</span>{{ __('cweb.form.product') }}
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
                {{ __('cweb.form.select') }}
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
            <option value="" {{ $oldSub === '' ? 'selected' : '' }} style="color:#9ca3af;">
                {{ __('cweb.form.select') }}
            </option>
        </select>

        @error('product_main')
            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
        @enderror
        @error('product_sub')
            <div style="color:#fca5a5;margin-top:4px;">{{ $message }}</div>
        @enderror
    </td>
</tr>

{{-- 7〜11行目以降は “あなたの貼ったもの” をそのまま維持（JSも含めてOK） --}}
{{-- ↓↓↓ ここから下は変更なしなので、あなたのコードをそのまま続けて貼ってOK ↓↓↓ --}}

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
        'ResiFlat-内製品':   ['103'],
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

            // 表示は「社員番号 / 名前」
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
                // hidden: 番号
                const hiddenNo = document.createElement('input');
                hiddenNo.type  = 'hidden';
                hiddenNo.name  = 'shared_employee_numbers[]';
                hiddenNo.value = emp.no;
                hiddenContainer.appendChild(hiddenNo);

                // hidden: 表示ラベル（氏名）
                const hiddenLabel = document.createElement('input');
                hiddenLabel.type  = 'hidden';
                hiddenLabel.name  = 'shared_employee_labels[]';
                hiddenLabel.value = emp.name;
                hiddenContainer.appendChild(hiddenLabel);

                // 表示用
                const line = document.createElement('div');
                line.style.cssText = 'color:var(--text);font-weight:700;';
                line.textContent = emp.no + ' / ' + emp.name;
                displayContainer.appendChild(line);
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
            const no    = tempSelectedEmps[0]?.no   || '';
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
            const no    = tempSelectedEmps[0]?.no   || '';
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
// function showSuccessModal(){
//   const overlay = document.getElementById('success-modal-overlay');
//   const modal   = document.getElementById('success-modal');
//   if (!overlay || !modal) return;

//   overlay.classList.add('visible','active');
//   overlay.style.display = 'flex';

//   modal.classList.add('visible','active');
//   modal.style.display = 'block';
//   modal.style.opacity = '1';
//   modal.style.pointerEvents = 'auto';
// }

// function closeSuccessModal(){
//   const overlay = document.getElementById('success-modal-overlay');
//   const modal   = document.getElementById('success-modal');

//   if (modal){
//     modal.classList.remove('visible','active');
//     modal.style.opacity = '0';
//     modal.style.pointerEvents = 'none';
//   }
//   if (overlay){
//     overlay.classList.remove('visible','active');
//     overlay.style.display = 'none';
//   }

//   window.location.href = "{{ route('cweb.cases.index', ['locale' => app()->getLocale()]) }}";
// }


document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('case-form');
  if (!form) return;

  const isFilled = (v) => v !== null && v !== undefined && String(v).trim() !== '';

  form.addEventListener('submit', (e) => {
    const messages = [];

    // ===== PCN管理項目（行単位：どれか入れたら全部必須） =====
    document.querySelectorAll('#pcn-rows .pcn-row').forEach((row, idx) => {
      const sel   = row.querySelector('select[name*="[category]"]');
      const title = row.querySelector('input[name*="[title]"]');
      const month = row.querySelector('input[name*="[months_before]"]');

      const vCat   = sel?.value ?? '';
      const vTitle = title?.value ?? '';
      const vMonth = month?.value ?? '';

      const hasAny = isFilled(vCat) || isFilled(vTitle) || isFilled(vMonth);
      if (!hasAny) return;

      if (!isFilled(vCat))   messages.push(`PCN管理項目（${idx+1}行目）：区分を選択してください。`);
      if (!isFilled(vTitle)) messages.push(`PCN管理項目（${idx+1}行目）：ラベル変更などを入力してください。`);
      if (!isFilled(vMonth)) messages.push(`PCN管理項目（${idx+1}行目）：ヵ月前連絡を入力してください。`);
    });

    // ===== その他要求（行単位：どれか入れたら全部必須） =====
    document.querySelectorAll('#other-rows .other-row').forEach((row, idx) => {
      const content = row.querySelector('textarea[name*="[content]"]');
      const respNo  = row.querySelector('input[type="hidden"][name*="[responsible_employee_number]"]');

      const vContent = content?.value ?? '';
      const vRespNo  = respNo?.value ?? '';

      const hasAny = isFilled(vContent) || isFilled(vRespNo);
      if (!hasAny) return;

      if (!isFilled(vContent)) messages.push(`その他要求（${idx+1}行目）：要求内容を入力してください。`);
      if (!isFilled(vRespNo))  messages.push(`その他要求（${idx+1}行目）：対応者を選択してください。`);
    });

    // ===== Will（登録費/月額：片方入れたら両方必須） =====
    const willInit    = form.querySelector('input[name="will_initial"]')?.value ?? '';
    const willMonthly = form.querySelector('input[name="will_monthly"]')?.value ?? '';
    const hasAnyWill  = isFilled(willInit) || isFilled(willMonthly);

    if (hasAnyWill) {
      if (!isFilled(willInit))    messages.push('Will：登録費を入力してください（片方だけは不可）。');
      if (!isFilled(willMonthly)) messages.push('Will：月額を入力してください（片方だけは不可）。');
    }

    // ===== 月額管理費の分配（行単位 + 合計0 or 100） =====
    let totalPct = 0;
    let hasAlloc = false;

    document.querySelectorAll('#will-rows .will-row').forEach((row, idx) => {
      const empNo = row.querySelector('input[type="hidden"][name*="[employee_number]"]')?.value ?? '';
      const empNm = row.querySelector('input[type="hidden"][name*="[employee_name]"]')?.value ?? '';
      const pctEl = row.querySelector('input[name*="[percentage]"]');
      const pct   = pctEl?.value ?? '';

      const hasAny = isFilled(empNo) || isFilled(empNm) || isFilled(pct);
      if (!hasAny) return;

      hasAlloc = true;

      if (!isFilled(empNo)) messages.push(`月額管理費の分配（${idx+1}行目）：担当者を選択してください。`);
      if (!isFilled(empNm)) messages.push(`月額管理費の分配（${idx+1}行目）：担当者名が未設定です（再選択してください）。`);
      if (!isFilled(pct))   messages.push(`月額管理費の分配（${idx+1}行目）：割合(%)を入力してください。`);

      totalPct += parseInt(pct || '0', 10);
    });

    if (hasAlloc && totalPct !== 0 && totalPct !== 100) {
      messages.push('月額管理費の分配：合計％は 0 または 100 にしてください。');
    }

    // ===== アラートして送信停止 =====
    if (messages.length > 0) {
      e.preventDefault();
      alert("入力エラーがあります。\n\n" + messages.join("\n"));
    }
  });
});

</script>

@if ($errors->any())
<script>
  const errors = @json($errors->all());
  alert("入力エラーがあります。\n\n" + errors.join("\n"));
</script>
@endif

@endsection

<!-- @if (session('cweb_success'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // showSuccessModal() は create 側の script に定義済みの想定
    if (typeof showSuccessModal === 'function') {
      showSuccessModal();
    }
  });
</script>
@endif -->