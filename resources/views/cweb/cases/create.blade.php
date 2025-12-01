@extends('cweb.layout')

@section('header')
<header class="cweb-header">
    <div class="cweb-header-inner">
        <div class="cweb-header-left">
            <div style="background:#fff;color:#000;padding:4px 8px;border-radius:4px;font-weight:700;">
                新規要求登録
            </div>
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

    {{-- メッセージ --}}
    @if(session('ok'))
        <div style="margin-bottom:8px;color:#16a34a">{{ session('ok') }}</div>
    @endif

    {{-- Will分配の全体エラー --}}
    @if($errors->has('will_allocations'))
        <div style="margin-bottom:8px;color:#fca5a5">{{ $errors->first('will_allocations') }}</div>
    @endif

    <form method="POST" action="{{ route('cweb.cases.store') }}">
        @csrf

        {{-- 登録ボタン --}}
        <div style="margin-bottom:12px;">
            <button type="submit"
                    class="btn btn-accent"
                    style="background:#f97316;color:#fff;border:none;padding:6px 16px;border-radius:4px;font-weight:600;">
                登録
            </button>
        </div>

        {{-- 1列11行テーブル --}}
        <div style="background:#0b1029;border-radius:8px;padding:0;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
@php
    $rowStyle = '';

    // 1列目：幅半分（10%）、真ん中寄せ、左に空白、仕切りはグレー
        $labelCell = implode('', [
            'padding:10px 10px 10px 32px;',
            'width:14%;',
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
            $salesName = old('sales_employee_name');
        @endphp

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

            {{-- ▼ 選択済みの表示（未選択なら完全に非表示＝幅0） --}}
            <span id="sales-emp-display"
                  style="display:{{ $salesName ? 'inline-block' : 'none' }};color:var(--text);font-weight:700;">
                {{ $salesName }}
            </span>

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
                            ＋ PCN要求の入力欄を1行追加
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
            ＋ その他要求の入力欄を1行追加
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
            ＋ 支払先を1行追加
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

    <script>
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
                      style="min-width:220px;display:inline-block;color:#e5e7eb;">
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

        // ▼ ポップアップはまだダミー（後で実装差し替え）
        function openPopupA()            { alert('営業窓口選択（ポップアップA）仮実装'); }
        function openPopupB()            { alert('情報共有者選択（ポップアップB）仮実装'); }
        function openPopupC()            { alert('費用負担先選択（ポップアップC）仮実装'); }
        function openPopupAForOther(i)   { alert('その他要求の対応者選択 index=' + i); }
        function openPopupAForWill(i)    { alert('Will分配の担当者選択 index=' + i); }
        
function setOtherResponsible(index, empNo, label) {
    document.getElementById('other-resp-no-' + index).value = empNo;
    document.getElementById('other-resp-label-' + index).value = label;

    const span = document.getElementById('other-resp-display-' + index);
    span.textContent = label;
    span.style.display = 'inline-block';
}



    </script>
@endsection
