@extends('cweb.layout')

@php
    use App\Models\User;
        use App\Models\CwebQualityMaster;
    use App\Models\CwebProductOwner;

    // ===== Locale =====
    $currentLocale = app()->getLocale();
    $nextLocale    = $currentLocale === 'ja' ? 'en' : 'ja';
    $listSep       = $currentLocale === 'ja' ? '、' : ', ';

    // ===== Status label（多言語）=====
    $statusLabel = match($case->status ?? '') {
        'active' => __('cweb.status.active'),
        'closed' => __('cweb.status.closed'),
        default  => __('cweb.status.unknown'),
    };
@endphp

{{-- ▼ ヘッダー（create と同じ構成） --}}
@section('header')
<header class="cweb-header">
    <div class="cweb-header-inner">

        <div class="cweb-header-left">
            <a href="{{ route('cweb.cases.index', ['locale' => $currentLocale]) }}" class="cweb-brand-link">
                C-WEB
            </a>

            {{-- 管理番号 + ステータス --}}
            <div style="font-weight:700;margin-left:12px;">
                {{ $case->manage_no }}
                <span style="margin-left:6px;font-weight:700;">
                    ({{ $statusLabel }})
                </span>
            </div>
        </div>

        <div class="cweb-header-right">
            <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>

            {{-- ✅ 言語トグル：/ja ⇔ /en --}}
            <div class="cweb-header-lang">
                <a class="cweb-header-lang-toggle"
                   href="{{ route('cweb.cases.show', ['locale' => $nextLocale, 'case' => $case->id]) }}">
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

@php
    // ▼ カテゴリー（多言語）
    $cats = [];
    if ($case->category_standard ?? false) $cats[] = __('cweb.categories.standard');
    if ($case->category_pcn ?? false)      $cats[] = __('cweb.categories.pcn');
    if ($case->category_other ?? false)    $cats[] = __('cweb.categories.other');
    $categoryLabel = $cats ? implode(' / ', $cats) : '-';

    // ▼ 対象製品
    $productLabel = trim(($case->product_group ?? '').' '.($case->product_code ?? ''));

    // ▼ Will
    $willInitial = $case->will_registration_cost ?? null;
    $willMonthly = $case->will_monthly_cost ?? null;

    // ▼ 営業窓口（社員番号 + 名前）
    $salesEmployeeNumber = $case->sales_contact_employee_number ?? null;
    $salesEmployeeName   = optional(
        User::where('employee_number', $salesEmployeeNumber)->first()
    )->name;

    // ▼ 情報共有者（sharedUsers リレーションから role=shared のみ）
    $sharedUsers = collect($case->sharedUsers ?? [])
        ->filter(fn($row) => ($row->role ?? null) === 'shared')
        ->values();

    // ▼ リレーション
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
        ? User::whereIn('employee_number', $otherEmpNumbers)->get()->keyBy('employee_number')
        : collect();

    /* =========================
       ここから送信先候補の計算
       ========================= */

    // ▼ 製品別担当者マスタ（employee_number 配列）
$productOwners = [];
$pg = $case->product_group ?? null;
$pc = $case->product_code ?? null;

if ($pg && $pc) {
    $productOwners = CwebProductOwner::query()
        ->where('is_active', true)
        ->where('product_group', $pg)
        ->where('product_code', $pc)
        ->pluck('employee_number')
        ->unique()
        ->values()
        ->all();
}    $productOwners = [];
    $pg = $case->product_group ?? null;
    $pc = $case->product_code ?? null;
    if ($pg && $pc && isset($productOwnersMap[$pg][$pc])) {
        $productOwners = $productOwnersMap[$pg][$pc];
    }

    // ▼ 登録者（created_by_user_id 想定：このBladeでは mail本文は auth()->user()->name を使うので必須ではない）
    $creatorEmpNo = null;
    if (!empty($case->created_by_user_id)) {
        $creatorUser = User::find($case->created_by_user_id);
        $creatorEmpNo = $creatorUser->employee_number ?? null;
    }

    // ▼ 情報共有者社員番号
    $sharedEmpNos = $sharedUsers->map(function($row){
        return optional($row->user)->employee_number;
    })->filter()->unique()->values()->all();

    // ▼ その他要求対応者社員番号
    $otherReqEmpNos = $otherReqs->pluck('responsible_employee_number')->filter()->unique()->values()->all();

    // ▼ 営業窓口の社員番号
    $salesEmpNo = $salesEmployeeNumber;

    // ▼ 品証マスタ（固定）※ここは実運用の社員番号に置き換え
$qualityMasterEmpNos = CwebQualityMaster::query()
    ->where('is_active', true)
    ->pluck('employee_number')
    ->toArray();

    // ▼ メール送信候補社員番号を集約
    $candidateEmpNos = collect([
            $creatorEmpNo,
            $salesEmpNo,
        ])
        ->merge($sharedEmpNos)
        ->merge($otherReqEmpNos)
        ->merge($productOwners)
        ->merge($qualityMasterEmpNos) // ★追加
        ->filter()
        ->unique()
        ->values()
        ->all();

    $candidateUsers = $candidateEmpNos
        ? User::whereIn('employee_number', $candidateEmpNos)->get()->keyBy('employee_number')
        : collect();

    // ▼ 送信先リスト [empNo => ['employee_number','name','checked']]
    $mailRecipients = [];

    $addRecipient = function(string $empNo, bool $defaultChecked = false) use (&$mailRecipients, $candidateUsers) {
        if (!isset($mailRecipients[$empNo])) {
            $user = $candidateUsers[$empNo] ?? null;
            $mailRecipients[$empNo] = [
                'employee_number' => $empNo,
                'name'    => $user->name ?? '',
                'checked' => $defaultChecked,
            ];
        } else {
            if ($defaultChecked) {
                $mailRecipients[$empNo]['checked'] = true;
            }
        }
    };

    // ルールに従って登録
    if ($creatorEmpNo) $addRecipient($creatorEmpNo, false); // 登録者：デフォルトOFF
    if ($salesEmpNo)   $addRecipient($salesEmpNo, true);    // 営業窓口：デフォルトON
    foreach ($sharedEmpNos as $empNo)    $addRecipient($empNo, true); // 情報共有者：ON
    foreach ($otherReqEmpNos as $empNo)  $addRecipient($empNo, true); // その他要求：ON
    foreach ($productOwners as $empNo)   $addRecipient($empNo, true); // 製品担当：ON
    foreach ($qualityMasterEmpNos as $empNo) $addRecipient($empNo, true); // ★品証マスタ：ON

    // ▼ 社員番号 => email（mailto用）
    $empToEmail = $candidateUsers->map(fn($u) => $u->email ?? null)->toArray();

    // ▼ show URL（Registrationで使用）
    $caseUrl = route('cweb.cases.show', ['locale' => $currentLocale, 'case' => $case->id]);

    // ▼ チェックONの送信先（Registration / Abolition の宛先に使う）
    $defaultCheckedEmpNos = collect($mailRecipients)
        ->filter(fn($r) => !empty($r['checked']))
        ->keys()
        ->values()
        ->all();

    $defaultCheckedEmails = collect($defaultCheckedEmpNos)
        ->map(fn($empNo) => $empToEmail[$empNo] ?? null)
        ->filter()
        ->values()
        ->all();

    $openMailRegistration = (bool)session('open_mail_registration', false);
@endphp

<style>
    /* ===== 上部のボタンバー ===== */
    .cweb-submit-bar{
        position: fixed;
        top: 45px;
        left: 0;
        right: 0;
        z-index: 45;
        background: var(--bg);
        padding: 8px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cweb-submit-bar-left{
        display:flex;
        align-items:center;
        gap:8px;
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
        display:inline-flex;
        align-items:center;
        justify-content:center;
        white-space:nowrap;
    }
    .cweb-submit-button:hover{ opacity:0.9; transform:translateY(-1px); }
    .cweb-submit-button:active{ transform:translateY(0); box-shadow:0 2px 4px rgba(0,0,0,0.25); }

    .cweb-btn-edit{ background:#f97316; }
    .cweb-btn-delete{ background:#dc2626; }
    .cweb-btn-folder{ background:#22c55e; }

    /* ===== 本文2カラム ===== */
    .cweb-case-layout{
        margin-top:60px;
        display:flex;
        flex-wrap:nowrap;
        gap:16px;
        align-items:flex-start;
        overflow-x:auto;
    }
    .cweb-case-left{ flex:1 1 auto; min-width:0; }
    .cweb-case-right{ flex:0 0 31.25%; min-width:320px; }

    .cweb-case-table{ width:100%; border-collapse:separate; border-spacing:0; border:none; font-size:13px; }

    .cweb-case-th{
        padding:10px 10px 10px 32px;
        width:30%;
        vertical-align:middle;
        color:#000;
        background:#e5e7eb;
        border-right:1px solid #d1d5db;
        border-bottom:none;
        box-sizing:border-box;
        font-weight:700;
    }

    .cweb-case-td{
        padding:10px 10px;
        background:var(--bg);
        border-bottom:none;
        vertical-align:middle;
        color:var(--text);
    }

    .cweb-comment-container{
        width:100%;
        padding:4px 8px 10px;
        box-sizing:border-box;
        text-align:left;
    }
    .cweb-comment-container textarea{
        width:100%;
        min-height:5rem;
        resize:vertical;
        padding:.5rem .75rem;
        border-radius:.28571429rem;
        border:1px solid rgba(34,36,38,.15);
        font-size:1rem;
        box-sizing:border-box;
    }
    .cweb-comment-container .ui.fixed.items{ text-align:right; margin-top:4px; margin-bottom:8px; }
    .cweb-comment-container .ui.blue.button.menu_btn{
        background:#2185d0; border:none; color:#fff;
        padding:.6em 1.2em; border-radius:.28571429rem;
        font-weight:600; font-size:.9rem; cursor:pointer;
        display:inline-flex; align-items:center; gap:.4em;
    }
    .cweb-comment-container .ui.blue.button.menu_btn:hover{ background:#1678c2; }

    .cweb-comment-list{ margin-top:8px; }
    .cweb-comment-item{ border-top:1px solid #e5e7eb; padding:6px 0 8px; }
    .cweb-comment-body{ font-size:14px; line-height:1.4; color:var(--text); white-space:pre-wrap; word-break:break-word; }

    .cweb-comment-meta-row{ display:flex; align-items:flex-start; margin-top:2px; }
    .cweb-comment-icon{ flex:0 0 auto; margin-right:4px; line-height:1.4; }
    .cweb-comment-meta{ flex:1 1 auto; font-size:11px; color:var(--muted); }

    /* ▼ 廃止ポップアップ：オーバーレイ */
    #case-delete-overlay{ position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1000; display: none; }

    /* ▼ 廃止ポップアップ：モーダル本体 */
    #case-delete-modal{
        position: fixed; top: 50%; left: 50%;
        display: block;
        transform: translate(-50%, -50%) scale(0.9);
        opacity: 0; pointer-events: none;
        z-index: 1001;
        text-align: left;
        background: #fff;
        border: none;
        box-shadow: 1px 3px 3px 0 rgba(0, 0, 0, .2), 1px 3px 15px 2px rgba(0, 0, 0, .2);
        border-radius: .28571429rem;
        font-size: 1rem;
        padding: 1.2rem 1.3rem 1rem;
        box-sizing: border-box;
        transition: transform .22s ease-out, opacity .22s ease-out;
    }
    #case-delete-modal.active{
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        pointer-events: auto;
    }
    .title_boader{ border-bottom: 3px solid #2185d0; padding-bottom: 6px; margin-bottom: 8px; }

    #case-delete-modal textarea#abolish-comment{ width: 100%; min-height: 100px; resize: vertical; }
    #case-delete-modal .abolish-note{ margin-top: 8px; color: #dc2626; font-size: 12px; }
    #case-delete-modal .actions{
        margin-top: 1rem;
        padding-top: .75rem;
        border-top: 1px solid rgba(34, 36, 38, .15);
        text-align: right;
    }

    /* ▼ コメント送信先選択モーダル */
    #selection-dimmer{
        position: fixed;
        inset: 0;
        display: none;
        background: rgba(0,0,0,.6);
        z-index: 1001;
        align-items: center;
        justify-content: center;
    }
    #selectionmodal{
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1002;
    }
    @media only screen and (max-width: 767.98px) {
        #selectionmodal.ui.tiny.modal { width: 95%; margin: 0; }
    }

    /* ▼ 登録メール起動モーダル（追加） */
    #regmail-dimmer{
        position: fixed;
        inset: 0;
        display: none;
        background: rgba(0,0,0,.6);
        z-index: 1100;
        align-items: center;
        justify-content: center;
    }
    #regmail-modal{
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1101;
        display:none;
        background:#fff;
        border-radius:.28571429rem;
        box-shadow: 1px 3px 15px 2px rgba(0, 0, 0, .2);
        padding: 1.1rem 1.2rem 1rem;
        width: 520px;
        max-width: 95%;
    }
    #regmail-modal .actions{
        margin-top: 12px;
        text-align:center;
        display:flex;
        gap:10px;
        justify-content:center;
    }

    /* ▼ メール起動完了モーダル（登録/廃止 共通） */
#mailinfo-dimmer{
    position: fixed;
    inset: 0;
    display: none;
    background: rgba(0,0,0,.6);
    z-index: 1200;
    align-items: center;
    justify-content: center;
}
#mailinfo-modal{
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1201;
    display:none;
    background:#fff;
    border-radius:.28571429rem;
    box-shadow: 1px 3px 15px 2px rgba(0, 0, 0, .2);
    padding: 1.1rem 1.2rem 1rem;
    width: 520px;
    max-width: 95%;
}
#mailinfo-modal .actions{
    margin-top: 12px;
    text-align:center;
}

</style>

{{-- ▼ 上部ボタン --}}
<div class="cweb-submit-bar">
    <div class="cweb-submit-bar-left">
        <button type="button"
                class="cweb-submit-button cweb-btn-edit"
                onclick="goEditPage()">
            {{ __('cweb.actions.edit') }}
        </button>

        @if(($case->status ?? '') !== 'closed')
            <button type="button"
                    class="cweb-submit-button cweb-btn-delete"
                    onclick="openDeleteModal()">
                {{ __('cweb.actions.abolish') }}
            </button>
        @endif
    </div>

    <button type="button"
            class="cweb-submit-button cweb-btn-folder"
            onclick="copyFolderPath()">
        {{ __('cweb.actions.folder') }}
    </button>
</div>

<div class="cweb-case-layout">

    {{-- 左：案件情報 --}}
    <div class="cweb-case-left">
        <div class="cweb-case-table-wrapper">
            <table class="cweb-case-table">
                <tbody>

                {{-- 営業窓口 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>{{ __('cweb.form.sales_contact') }}
                    </td>
                    <td class="cweb-case-td">
                        @if($salesEmployeeNumber)
                            {{ $salesEmployeeNumber }}
                            @if(!empty($salesEmployeeName))
                                / {{ $salesEmployeeName }}
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>

                {{-- 情報共有者 --}}
                <tr>
                    <td class="cweb-case-th">
                        {{ __('cweb.form.shared_users') }}
                    </td>
                    <td class="cweb-case-td">
                        @if($sharedUsers->isEmpty())
                            -
                        @else
                            @foreach($sharedUsers as $shared)
                                @php $user = $shared->user; @endphp
                                @if($user)
                                    {{ $user->employee_number }}
                                    @if(!empty($user->name))
                                        / {{ $user->name }}
                                    @endif
                                    @if(!$loop->last)
                                        {{ $listSep }}
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    </td>
                </tr>

                {{-- 費用負担先 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>{{ __('cweb.form.cost_owner') }}
                    </td>
                    <td class="cweb-case-td">
                        {{ $case->cost_responsible_code ?? '-' }}
                    </td>
                </tr>

                {{-- 顧客名 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>{{ __('cweb.form.customer') }}
                    </td>
                    <td class="cweb-case-td">
                        {{ $case->customer_name ?? '-' }}
                    </td>
                </tr>

                {{-- カテゴリー --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>{{ __('cweb.form.category') }}
                    </td>
                    <td class="cweb-case-td">
                        {{ $categoryLabel }}
                    </td>
                </tr>

                {{-- 対象製品 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>{{ __('cweb.form.product') }}
                    </td>
                    <td class="cweb-case-td">
                        {{ $productLabel ?: '-' }}
                    </td>
                </tr>

                {{-- PCN管理項目 --}}
                <tr>
                    <td class="cweb-case-th">{{ __('cweb.show.pcn_items') }}</td>
                    <td class="cweb-case-td">
                        @if($pcnItems->isNotEmpty())
                            <ul style="margin:0;padding-left:18px;">
                                @foreach($pcnItems as $item)
                                    @php
                                        $catLabel = match($item->category ?? '') {
                                            'spec'        => __('cweb.pcn.categories.spec'),
                                            'man'         => __('cweb.pcn.categories.man'),
                                            'machine'     => __('cweb.pcn.categories.machine'),
                                            'material'    => __('cweb.pcn.categories.material'),
                                            'method'      => __('cweb.pcn.categories.method'),
                                            'measurement' => __('cweb.pcn.categories.measurement'),
                                            'environment' => __('cweb.pcn.categories.environment'),
                                            'other'       => __('cweb.pcn.categories.other'),
                                            default       => __('cweb.pcn.categories.uncategorized'),
                                        };
                                    @endphp
                                    <li style="margin-bottom:2px;">
                                        {{ $catLabel }}
                                        @if(!empty($item->title))
                                            ：{{ $item->title }}
                                        @endif
                                        @if(!is_null($item->months_before))
                                            （{{ $item->months_before }}{{ __('cweb.pcn.months_before_suffix') }}）
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            -
                        @endif
                    </td>
                </tr>

                {{-- その他要求 --}}
                <tr>
                    <td class="cweb-case-th">{{ __('cweb.show.other_requests') }}</td>
                    <td class="cweb-case-td">
                        @if($otherReqs->isNotEmpty())
                            @foreach($otherReqs as $req)
                                @php
                                    $empNo = $req->responsible_employee_number ?? null;
                                    $emp   = $empNo ? ($otherEmployees[$empNo] ?? null) : null;
                                @endphp

                                <div style="margin-bottom:6px;">
                                    <div style="margin-bottom:2px;">
                                        {{ $req->content ?? '' }}
                                    </div>
                                    <div style="font-size:11px;color:var(--muted);">
                                        {{ __('cweb.show.responsible') }}：
                                        @if($empNo || $emp)
                                            {{ $empNo }}
                                            @if($emp)
                                                / {{ $emp->name }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>

                {{-- Will --}}
                <tr>
                    <td class="cweb-case-th">{{ __('cweb.show.will') }}</td>
                    <td class="cweb-case-td">
                        {{ __('cweb.show.will_initial') }}：
                        {{ $willInitial !== null ? number_format($willInitial).' will' : '-' }}
                        &nbsp;&nbsp;
                        {{ __('cweb.show.will_monthly') }}：
                        {{ $willMonthly !== null ? number_format($willMonthly).' will' : '-' }}
                    </td>
                </tr>

                {{-- 月額管理費の分配 --}}
                <tr>
                    <td class="cweb-case-th">{{ __('cweb.show.will_allocations') }}</td>
                    <td class="cweb-case-td">
                        @if($willAllocations->isNotEmpty())
                            @foreach($willAllocations as $alloc)
                                <div style="margin-bottom:4px;">
                                    <span style="display:inline-block;min-width:220px;">
                                        {{ $alloc->employee_number ?? '' }}
                                        {{ $alloc->employee_name ?? '' }}
                                    </span>
                                    <span style="font-weight:700;">
                                        {{ $alloc->percentage ?? 0 }} %
                                    </span>
                                </div>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>

                {{-- 関連Q-WEB --}}
                <tr>
                    <td class="cweb-case-th">{{ __('cweb.show.related_qweb') }}</td>
                    <td class="cweb-case-td">
                        {{ $case->related_qweb ?? '-' }}
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
    </div>

    {{-- 右：コメントエリア --}}
    <div class="cweb-comment-container ui cweb-case-right">

        {{-- コメント入力フォーム --}}
        <form id="comment-form" method="POST" action="{{ route('cweb.cases.comments.store', ['locale' => $currentLocale, 'case' => $case->id]) }}">
            @csrf
            <div class="ui.input" style="width:100%;">
                <textarea id="val_message"
                          rows="5"
                          name="body"
                          placeholder="{{ __('cweb.comments.placeholder') }}">{{ old('body') }}</textarea>
            </div>

            @error('body')
                <div style="color:#f97316;font-size:12px;margin-top:2px;">
                    {{ $message }}
                </div>
            @enderror

            <div class="ui.fixed.items" style="text-align:right">
                <button type="button"
                        class="ui blue button menu_btn"
                        name="wfbtnsendmsg"
                        id="wfbtnsendmsg">
                    <i class="comment outline icon"></i>{{ __('cweb.actions.post') }}
                </button>
            </div>

            {{-- ▼ 送信先選択モーダル --}}
            <div id="selection-dimmer" class="ui dimmer" style="display:none;"></div>

            <div class="ui tiny modal transition front" id="selectionmodal" style="display:none;">
                <div class="header title_boader">{{ __('cweb.comments.send_to_title') }}</div>

                <div class="content" id="selection_content">
                    @forelse($mailRecipients as $empNo => $info)
                        <div class="ui checkbox" style="margin-bottom:5px">
                            <input type="checkbox"
                                   name="val_mailsend[]"
                                   id="val_mailsend_{{ $loop->index }}"
                                   value="{{ $empNo }}"
                                   {{ $info['checked'] ? 'checked' : '' }}>
                            <label for="val_mailsend_{{ $loop->index }}">
                                {{ $empNo }}
                                @if(!empty($info['name']))
                                    / {{ $info['name'] }}
                                @endif
                            </label>
                        </div>
                    @empty
                        <p>{{ __('cweb.comments.no_candidates') }}</p>
                    @endforelse
                </div>

                <div class="actions arcon_hf" style="text-align: center">
                    <div class="ui button positive ok" id="selection-ok">{{ __('cweb.common.ok') }}</div>
                    <div class="ui button cancel" id="selection-cancel">{{ __('cweb.common.cancel') }}</div>
                </div>
            </div>
        </form>

        {{-- ▼ 廃止ポップアップ（コメントフォームの外側） --}}
        <div id="case-delete-overlay" class="ui dimmer" style="display:none;"></div>

        <div id="case-delete-modal"
             class="ui small modal front"
             style="display:none; opacity:0; pointer-events:none;">

            <div class="header title_boader">
                {{ __('cweb.abolish.title') }}
            </div>

            <div class="content">
                <textarea id="abolish-comment"
                          placeholder="{{ __('cweb.abolish.placeholder') }}"></textarea>

                <div class="abolish-note">
                    {{ __('cweb.abolish.note') }}
                </div>
            </div>

            <div class="actions" style="text-align:center;">
                <div class="ui positive button" onclick="onAbolishOk()">
                    {{ __('cweb.common.ok') }}
                </div>
                <div class="ui button" onclick="closeDeleteModal()">
                    {{ __('cweb.common.cancel') }}
                </div>
            </div>
        </div>

        {{-- 廃止送信用フォーム --}}
        <form id="abolish-form"
              method="POST"
              action="{{ route('cweb.cases.abolish', ['locale' => $currentLocale, 'case' => $case->id]) }}">
            @csrf
            <input type="hidden" name="abolish_comment" id="abolish-comment-hidden" value="">
        </form>

        {{-- コメント一覧 --}}
        <div class="cweb-comment-list">
            @foreach($case->comments ?? [] as $comment)
                <div class="cweb-comment-item">
                    <div class="cweb-comment-body">
                        {{ $comment->body }}
                    </div>
                    <div class="cweb-comment-meta-row">
                        <div class="cweb-comment-icon">
                            <i class="user circle outline icon"></i>
                        </div>
                        <div class="cweb-comment-meta">
                            {{ optional($comment->user)->name ?? '-' }}
                            ／
                            {{ $comment->created_at?->format('Y/m/d H:i') ?? '' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>{{-- .cweb-case-right --}}
</div>{{-- .cweb-case-layout --}}

{{-- ▼ 登録後の「メール作成」モーダル（追加） --}}
<div id="regmail-dimmer"></div>
<div id="regmail-modal">
    <div class="header title_boader" style="font-weight:800;">
        Registration_顧客要求の新規登録がありました / C-WEB /({{ $case->manage_no }})
    </div>
    <div class="content" style="font-size:13px;">
        登録メールを作成します。<br>
        （宛先：品証マスタ / 営業窓口 / 情報共有者 / その他要求対応者 / 製品担当）
    </div>
    <div class="actions">
        <button type="button" class="ui positive button" id="regmail-ok">メール作成</button>
        <button type="button" class="ui button" id="regmail-cancel">閉じる</button>
    </div>
</div>

<script>
function goEditPage() {
    window.location.href = "{{ route('cweb.cases.edit', ['locale' => $currentLocale, 'case' => $case->id]) }}";
}

/* ========= mailto起動 ========= */
function openMailClient(toEmails, subject, body) {
    const unique = [...new Set((toEmails || []).filter(Boolean))];
    if (unique.length === 0) {
        alert('送信先のメールアドレスが取得できませんでした（users.email を確認してください）');
        return false;
    }
    const to = unique.join(',');
    const url = `mailto:${to}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body ?? '')}`;

    // mailto は window.open がブロックされることがあるので location も用意
    const w = window.open(url, '_blank');
    if (!w) window.location.href = url;

    return true;
}

/* ========= メール起動完了ポップアップ（グローバルで使えるようにする） ========= */
let mailInfoAfterClose = null;

function openMailInfoModal(afterCloseFn) {
    const dimmer = document.getElementById('mailinfo-dimmer');
    const modal  = document.getElementById('mailinfo-modal');

    mailInfoAfterClose = (typeof afterCloseFn === 'function') ? afterCloseFn : null;

    if (!dimmer || !modal) return;

    dimmer.style.display = 'flex';
    modal.style.display  = 'block';
}

function closeMailInfoModal() {
    const dimmer = document.getElementById('mailinfo-dimmer');
    const modal  = document.getElementById('mailinfo-modal');

    if (dimmer) dimmer.style.display = 'none';
    if (modal)  modal.style.display  = 'none';

    const fn = mailInfoAfterClose;
    mailInfoAfterClose = null;
    if (fn) fn();
}

const EMP_TO_EMAIL = @json($empToEmail);
const CASE_MANAGE_NO = @json($case->manage_no);
const CURRENT_USER_NAME = @json(auth()->user()->name ?? '');
const CASE_URL = @json($caseUrl);
const DEFAULT_TO_EMAILS = @json($defaultCheckedEmails);
const OPEN_MAIL_REGISTRATION = @json($openMailRegistration);

/* ▼ 廃止モーダル関連 */
function openDeleteModal() {
    const overlay = document.getElementById('case-delete-overlay');
    const modal   = document.getElementById('case-delete-modal');
    if (!overlay || !modal) return;

    const textarea = document.getElementById('abolish-comment');
    if (textarea) textarea.value = '';

    overlay.style.display = 'flex';
    overlay.classList.add('visible', 'active');

    modal.style.display = 'block';
    modal.classList.add('visible', 'active');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}

function closeDeleteModal() {
    const overlay = document.getElementById('case-delete-overlay');
    const modal   = document.getElementById('case-delete-modal');
    if (!overlay || !modal) return;

    overlay.classList.remove('visible', 'active');
    overlay.style.display = 'none';

    modal.classList.remove('visible', 'active');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    modal.style.display = 'none';
}

function onAbolishOk() {
    const textarea = document.getElementById('abolish-comment');
    if (!textarea) return;

    const comment = textarea.value.trim();
    if (!comment) {
        alert(@json(__('cweb.abolish.alert_enter_comment')));
        return;
    }

    const subject = `Abolition_顧客要求が廃止されました / C-WEB /(${CASE_MANAGE_NO})`;
    const body = `廃止されました：${CURRENT_USER_NAME}\nコメント:${comment}`;

    const opened = openMailClient(DEFAULT_TO_EMAILS, subject, body);

    document.getElementById('abolish-comment-hidden').value = comment;
    closeDeleteModal();

    // 廃止は画面遷移するので、OK後に submit
    if (opened) {
        openMailInfoModal(() => {
            document.getElementById('abolish-form').submit();
        });
    } else {
        document.getElementById('abolish-form').submit();
    }
}

/* ▼ 初期化 */
document.addEventListener('DOMContentLoaded', function () {

    // mailinfo OK/外側クリック
    const mailOk = document.getElementById('mailinfo-ok');
    const mailDimmer = document.getElementById('mailinfo-dimmer');
    if (mailOk) mailOk.addEventListener('click', closeMailInfoModal);
    if (mailDimmer) {
        mailDimmer.addEventListener('click', function(e){
            if (e.target === mailDimmer) closeMailInfoModal();
        });
    }

    // コメント送信先選択モーダル
    const form      = document.getElementById('comment-form');
    const openBtn   = document.getElementById('wfbtnsendmsg');
    const selection = document.getElementById('selectionmodal');
    const dimmer    = document.getElementById('selection-dimmer');
    const okBtn     = document.getElementById('selection-ok');
    const cancelBtn = document.getElementById('selection-cancel');

    function openSelectionModal() {
        dimmer.style.display = 'flex';
        dimmer.classList.add('visible', 'active');
        selection.style.display = 'block';
        selection.classList.add('visible', 'active');
    }
    function closeSelectionModal() {
        dimmer.classList.remove('visible', 'active');
        dimmer.style.display = 'none';
        selection.classList.remove('visible', 'active');
        selection.style.display = 'none';
    }

    if (form && openBtn && selection && dimmer && okBtn && cancelBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openSelectionModal();
        });

        okBtn.addEventListener('click', function () {
            const checkedEmpNos = Array.from(document.querySelectorAll('input[name="val_mailsend[]"]:checked'))
                .map(el => el.value);

            const toEmails = checkedEmpNos
                .map(empNo => EMP_TO_EMAIL[empNo])
                .filter(Boolean);

            const comment = (document.getElementById('val_message')?.value || '').trim();
            const subject = `New Comment コメントが投稿されました / C-WEB /(${CASE_MANAGE_NO})`;
            const body = `${comment}`;

            openMailClient(toEmails, subject, body);

            closeSelectionModal();
            form.submit();
        });

        cancelBtn.addEventListener('click', closeSelectionModal);
        dimmer.addEventListener('click', function (e) {
            if (e.target === dimmer) closeSelectionModal();
        });
    }

    // 登録直後モーダル
    const regDimmer = document.getElementById('regmail-dimmer');
    const regModal  = document.getElementById('regmail-modal');
    const regOk     = document.getElementById('regmail-ok');
    const regCancel = document.getElementById('regmail-cancel');

    function openRegMailModal() {
        if (!regDimmer || !regModal) return;
        regDimmer.style.display = 'flex';
        regModal.style.display  = 'block';
    }
    function closeRegMailModal() {
        if (!regDimmer || !regModal) return;
        regDimmer.style.display = 'none';
        regModal.style.display  = 'none';
    }

    if (OPEN_MAIL_REGISTRATION) openRegMailModal();

    if (regOk) {
        regOk.addEventListener('click', function () {
            const subject = `Registration_顧客要求の新規登録がありました / C-WEB /(${CASE_MANAGE_NO})`;
            const body = `${CASE_URL}\n\n新規登録しました：${CURRENT_USER_NAME}`;

            const opened = openMailClient(DEFAULT_TO_EMAILS, subject, body);
            closeRegMailModal();

            if (opened) openMailInfoModal();
        });
    }
    if (regCancel) regCancel.addEventListener('click', closeRegMailModal);
    if (regDimmer) {
        regDimmer.addEventListener('click', function (e) {
            if (e.target === regDimmer) closeRegMailModal();
        });
    }
});

/* ▼ フォルダパスコピー（成功メッセージなし / 失敗時だけアラート） */
function copyFolderPath() {
    const path = "\\\\ftktake01\\QWeb_Data\\Specification\\{{ $case->manage_no }}";

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(path)
            .then(() => { /* 成功時：何もしない */ })
            .catch(() => { alert(@json(__('cweb.clipboard.folder_not_found'))); });
        return;
    }

    const ta = document.createElement('textarea');
    ta.value = path;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);

    ta.select();
    ta.setSelectionRange(0, ta.value.length);

    let ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    document.body.removeChild(ta);

    if (!ok) alert(@json(__('cweb.clipboard.folder_not_found')));
}
</script>

{{-- ▼ メール起動完了ポップアップ（登録/廃止 共通） --}}
<div id="mailinfo-dimmer"></div>
<div id="mailinfo-modal">
    <div class="header title_boader" style="font-weight:800;">
        {{ $currentLocale === 'ja' ? '確認' : 'Notice' }}
    </div>
    <div class="content" style="font-size:13px; line-height:1.6;">
        {{ $currentLocale === 'ja'
            ? 'メールを立ち上げました。関係者に送信してください。'
            : 'Mail composer opened. Please send it to the related members.' }}
    </div>
    <div class="actions">
        <button type="button" class="ui positive button" id="mailinfo-ok">
            {{ $currentLocale === 'ja' ? 'OK' : 'OK' }}
        </button>
    </div>
</div>

@endsection
