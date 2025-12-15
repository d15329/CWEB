@extends('cweb.layout')

{{-- ▼ ヘッダー（create と同じ構成） --}}
@section('header')
<header class="cweb-header">
    <div class="cweb-header-inner">

        <div class="cweb-header-left">
            <a href="{{ route('cweb.cases.index') }}" class="cweb-brand-link">
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
            <span style="margin:0 12px;">日本語 / EN</span>
            @auth
                <span>{{ auth()->user()->name }}</span>
            @endauth
        </div>

    </div>
</header>
@endsection


@section('content')

@php
    use App\Models\User;

    // ▼ カテゴリー
    $cats = [];
    if ($case->category_standard ?? false) $cats[] = '標準管理';
    if ($case->category_pcn ?? false)      $cats[] = 'PCN';
    if ($case->category_other ?? false)    $cats[] = 'その他要求';
    $categoryLabel = $cats ? implode(' / ', $cats) : '-';

    // ▼ ステータス
    $statusLabel = match($case->status ?? '') {
        'active' => 'アクティブ',
        'closed' => '廃止',
        default  => '不明',
    };

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

    // ▼ まだ残っている style="{{ $labelCell }}" / "{{ $inputCell }}" 対策
    $labelCell = implode('', [
        'padding:10px 10px 10px 32px;',
        'width:30%;',
        'vertical-align:middle;',
        'color:#000;',
        'background:#e5e7eb;',
        'border-right:1px solid #d1d5db;',
        'border-bottom:none;',
        'box-sizing:border-box;',
        'font-weight:700;',
    ]);

    $inputCell = 'padding:10px 10px;background:var(--bg);border-bottom:none;vertical-align:middle;color:var(--text);';

    /* =========================
       ここから送信先候補の計算
       ========================= */

    // ▼ 製品別担当者マスタ（employee_number 配列）
    $productOwnersMap = [
        'HogoMax-内製品' => [
            '102' => ['03867','07335'],
            '103' => ['03867','07335'],
            '104' => ['03867','07335'],
            '105' => ['03867','07335'],
            '106' => ['03867','07335'],
            '107' => ['03867','07335'],
            '108' => ['03867','07335'],
            '152' => ['03867','07335'],
            '153' => ['03867','07335'],
            '201' => ['03867','07335'],
            '202' => ['03867','07335'],
            '203' => ['03867','07335'],
            '204' => ['03867','07335'],
        ],
        'HogoMax-OEM品' => [
            '002' => ['03867'],
            '003' => ['03867'],
        ],
        'StayClean-内製品' => [
            '301' => ['10660','07335'],
            '401' => ['10660','07335'],
        ],
        'StayClean-OEM品' => [
            '-A' => ['03048'],
            '-F' => ['12588','01474'],
            '-R' => ['12588','01474'],
        ],
        'ResiFlat-内製品' => [
            '103' => ['03074','03112'],
        ],
        'ResiFlat-OEM品' => [
            '002' => ['03074','03112'],
        ],
    ];

    $productOwners = [];
    $pg = $case->product_group ?? null;
    $pc = $case->product_code ?? null;
    if ($pg && $pc && isset($productOwnersMap[$pg][$pc])) {
        $productOwners = $productOwnersMap[$pg][$pc];
    }

    // ▼ 登録者（created_by は users.id 想定）
    $creatorUser = null;
    if (!empty($case->created_by)) {
        $creatorUser = User::find($case->created_by);
    }
    $creatorEmpNo = $creatorUser->employee_number ?? null;

    // ▼ 情報共有者社員番号
    $sharedEmpNos = $sharedUsers->map(function($row){
        return optional($row->user)->employee_number;
    })->filter()->unique()->values()->all();

    // ▼ その他要求対応者社員番号
    $otherReqEmpNos = $otherReqs->pluck('responsible_employee_number')->filter()->unique()->values()->all();

    // ▼ 営業窓口の社員番号（既に $salesEmployeeNumber にある）
    $salesEmpNo = $salesEmployeeNumber;

    // ▼ メール送信候補社員番号を集約
    $candidateEmpNos = collect([
            $creatorEmpNo,
            $salesEmpNo,
        ])
        ->merge($sharedEmpNos)
        ->merge($otherReqEmpNos)
        ->merge($productOwners)
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
            // どこかのロールで default=true なら優先
            if ($defaultChecked) {
                $mailRecipients[$empNo]['checked'] = true;
            }
        }
    };

    // ルールに従って登録
    if ($creatorEmpNo) {
        // 登録者：デフォルトではチェックしない
        $addRecipient($creatorEmpNo, false);
    }
    if ($salesEmpNo) {
        // 営業窓口：デフォルトチェック
        $addRecipient($salesEmpNo, true);
    }
    foreach ($sharedEmpNos as $empNo) {
        // 情報共有者：デフォルトチェック
        $addRecipient($empNo, true);
    }
    foreach ($otherReqEmpNos as $empNo) {
        // その他要求対応者：デフォルトチェック
        $addRecipient($empNo, true);
    }
    foreach ($productOwners as $empNo) {
        // 当該製品の担当者：デフォルトチェック
        $addRecipient($empNo, true);
    }
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
    .cweb-submit-bar-right{
        display:flex;
        align-items:center;
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
    .cweb-submit-button:hover{
        opacity:0.9;
        transform:translateY(-1px);
    }
    .cweb-submit-button:active{
        transform:translateY(0);
        box-shadow:0 2px 4px rgba(0,0,0,0.25);
    }
    .cweb-btn-edit{
        background:#f97316;
    }
    .cweb-btn-delete{
        background:#dc2626;
    }
    .cweb-btn-folder{
        background:#22c55e;
    }

    /* ===== 本文2カラム ===== */
    .cweb-case-layout{
        margin-top:60px;  /* ボタンバー高さぶん */
        display:flex;
        flex-wrap:nowrap;
        gap:16px;
        align-items:flex-start;
        overflow-x:auto;
    }
    .cweb-case-left{
        flex:1 1 auto;
        min-width:0;
    }
    .cweb-case-right{
        flex:0 0 31.25%;
        min-width:320px;
    }

    .cweb-case-table-wrapper{
        background:transparent;
        border-radius:0;
        padding:0;
    }
    .cweb-case-table{
        width:100%;
        border-collapse:separate;
        border-spacing:0;
        border:none;
        font-size:13px;
    }

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
    @media (prefers-color-scheme: dark){
        .cweb-case-td{
            background:var(--bg);
        }
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

    .cweb-comment-container .ui.fixed.items{
        text-align:right;
        margin-top:4px;
        margin-bottom:8px;
    }

    .cweb-comment-container .ui.blue.button.menu_btn{
        background:#2185d0;
        border:none;
        color:#fff;
        padding:.6em 1.2em;
        border-radius:.28571429rem;
        font-weight:600;
        font-size:.9rem;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:.4em;
    }
    .cweb-comment-container .ui.blue.button.menu_btn:hover{
        background:#1678c2;
    }

    .cweb-comment-list{
        margin-top:8px;
    }

    .cweb-comment-item{
        border-top:1px solid #e5e7eb;
        padding:6px 0 8px;
    }

    .cweb-comment-body{
        font-size:14px;
        line-height:1.4;
        color:var(--text);
        white-space:pre-wrap;
        word-break:break-word;
    }

    .cweb-comment-meta-row{
        display:flex;
        align-items:flex-start;
        margin-top:2px;
    }

    .cweb-comment-icon{
        flex:0 0 auto;
        margin-right:4px;
        line-height:1.4;
    }

    .cweb-comment-meta{
        flex:1 1 auto;
        font-size:11px;
        color:var(--muted);
    }

    /* ▼ 廃止ポップアップ：オーバーレイ */
    #case-delete-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.4);
        z-index: 1000;
        display: none;
    }

    /* ▼ 廃止ポップアップ：モーダル本体 */
    #case-delete-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        display: block;
        transform: translate(-50%, -50%) scale(0.9);
        opacity: 0;
        pointer-events: none;
        z-index: 1001;
        text-align: left;
        background: #fff;
        border: none;
        box-shadow: 1px 3px 3px 0 rgba(0, 0, 0, .2),
                    1px 3px 15px 2px rgba(0, 0, 0, .2);
        border-radius: .28571429rem;
        font-size: 1rem;
        padding: 1.2rem 1.3rem 1rem;
        box-sizing: border-box;
        transition: transform .22s ease-out, opacity .22s ease-out;
    }

    #case-delete-modal.active {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        pointer-events: auto;
    }

    .title_boader {
        border-bottom: 3px solid #2185d0;
        padding-bottom: 6px;
        margin-bottom: 8px;
    }

    #case-delete-modal textarea#abolish-comment {
        width: 100%;
        min-height: 100px;
        resize: vertical;
    }

    #case-delete-modal .abolish-note {
        margin-top: 8px;
        color: #dc2626;
        font-size: 12px;
    }

    #case-delete-modal.actions,
    #case-delete-modal .actions {
        margin-top: 1rem;
        padding-top: .75rem;
        border-top: 1px solid rgba(34, 36, 38, .15);
        text-align: right;
    }

    /* ▼ コメント送信先選択モーダル（中央に固定） */
    #selection-dimmer {
        position: fixed;
        inset: 0;
        display: none;
        background: rgba(0,0,0,.6);
        z-index: 1001;
        align-items: center;
        justify-content: center;
    }

    #selectionmodal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1002;
    }

    @media only screen and (max-width: 767.98px) {
        #selectionmodal.ui.tiny.modal {
            width: 95%;
            margin: 0;
        }
    }

</style>

{{-- ▼ 上部ボタン --}}
<div class="cweb-submit-bar">
    <div class="cweb-submit-bar-left">
        <button type="button"
                class="cweb-submit-button cweb-btn-edit"
                onclick="goEditPage()">
            編集
        </button>
                @if(($case->status ?? '') !== 'closed')
        <button type="button"
                class="cweb-submit-button cweb-btn-delete"
                onclick="openDeleteModal()">
            廃止
        </button>
              @endif
    </div>

<button type="button"
        class="cweb-submit-button cweb-btn-folder"
        onclick="copyFolderPath()">
    フォルダ
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
                        <span style="color:red;">＊</span>営業窓口
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

                @php
                    $sharedUsers = collect($case->sharedUsers ?? [])->filter(function($row) {
                        return ($row->role ?? null) === 'shared';
                    });
                @endphp

                {{-- 情報共有者 --}}
                <tr>
                    <td class="cweb-case-th">
                        情報共有者
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
                                        、
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    </td>
                </tr>

                {{-- 費用負担先 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>費用負担先
                    </td>
                    <td class="cweb-case-td">
                        {{ $case->cost_responsible_code ?? '-' }}
                    </td>
                </tr>

                {{-- 顧客名 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>顧客名
                    </td>
                    <td class="cweb-case-td">
                        {{ $case->customer_name ?? '-' }}
                    </td>
                </tr>

                {{-- カテゴリー --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>カテゴリー
                    </td>
                    <td class="cweb-case-td">
                        {{ $categoryLabel }}
                    </td>
                </tr>

                {{-- 対象製品 --}}
                <tr>
                    <td class="cweb-case-th">
                        <span style="color:red;">＊</span>対象製品
                    </td>
                    <td class="cweb-case-td">
                        {{ $productLabel ?: '-' }}
                    </td>
                </tr>

                {{-- PCN管理項目 --}}
                <tr>
                    <td class="cweb-case-th">PCN管理項目</td>
                    <td class="cweb-case-td">
                        @if($pcnItems->isNotEmpty())
                            <ul style="margin:0;padding-left:18px;">
                                @foreach($pcnItems as $item)
                                    @php
                                        $catLabel = match($item->category ?? '') {
                                            'spec'        => '仕様書内容',
                                            'man'         => '人（Man）',
                                            'machine'     => '機械（Machine）',
                                            'material'    => '材料（Material）',
                                            'method'      => '方法（Method）',
                                            'measurement' => '測定（Measurement）',
                                            'environment' => '環境（Environment）',
                                            'other'       => 'その他',
                                            default       => '未分類',
                                        };
                                    @endphp
                                    <li style="margin-bottom:2px;">
                                        {{ $catLabel }}
                                        @if(!empty($item->title))
                                            ：{{ $item->title }}
                                        @endif
                                        @if(!is_null($item->months_before))
                                            （{{ $item->months_before }}ヵ月前連絡）
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            -
                        @endif
                    </td>
                </tr>

                @php
                    $otherEmpNumbers = $otherReqs->pluck('responsible_employee_number')
                                                 ->filter()
                                                 ->unique()
                                                 ->values()
                                                 ->all();
                    $otherEmployees = $otherEmpNumbers
                        ? \App\Models\User::whereIn('employee_number', $otherEmpNumbers)->get()->keyBy('employee_number')
                        : collect();
                @endphp

                {{-- その他要求 --}}
                <tr>
                    <td class="cweb-case-th">その他要求</td>
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
                                        対応者：
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
                    <td class="cweb-case-th">Will</td>
                    <td class="cweb-case-td">
                        登録費：
                        {{ $willInitial !== null ? number_format($willInitial).' will' : '-' }}
                        &nbsp;&nbsp;
                        月額：
                        {{ $willMonthly !== null ? number_format($willMonthly).' will' : '-' }}
                    </td>
                </tr>

                {{-- 月額管理費の分配 --}}
                <tr>
                    <td class="cweb-case-th">月額管理費の分配</td>
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
                    <td class="cweb-case-th">関連Q-WEB</td>
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
        <form id="comment-form" method="POST" action="{{ route('cweb.cases.comments.store', $case) }}">
            @csrf
            <div class="ui.input" style="width:100%;">
                <textarea id="val_message"
                          rows="5"
                          name="body"
                          placeholder="Add a comments...">{{ old('body') }}</textarea>
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
                    <i class="comment outline icon"></i>投稿
                </button>
            </div>

            {{-- ▼ 送信先選択モーダル（コメントフォーム内で OK） --}}
            <div id="selection-dimmer" class="ui dimmer" style="display:none;"></div>

            <div class="ui tiny modal transition front" id="selectionmodal" style="display:none;">
                <div class="header title_boader">送信先を選択してください</div>

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
                        <p>送信先候補がありません。</p>
                    @endforelse
                </div>

                <div class="actions arcon_hf" style="text-align: center">
                    <div class="ui button positive ok" id="selection-ok">OK</div>
                    <div class="ui button cancel" id="selection-cancel">Cancel</div>
                </div>
            </div>
        </form> {{-- ★ コメントフォームはここで閉じる（廃止フォームとは分離） --}}

        {{-- ▼ 廃止ポップアップ（コメントフォームの外側） --}}

        {{-- オーバーレイ（黒半透明） --}}
        <div id="case-delete-overlay" class="ui dimmer" style="display:none;"></div>

        {{-- モーダル本体 --}}
        <div id="case-delete-modal"
            class="ui small modal front"
            style="display:none; opacity:0; pointer-events:none;">

            <div class="header title_boader">
                廃止にしますか？
            </div>

            <div class="content">
                <textarea id="abolish-comment"
                        placeholder="comments..."></textarea>

                <div class="abolish-note">
                    担当営業の誰からいつ合意を得たかを記載してください
                </div>
            </div>

            <div class="actions" style="text-align:center;">
                <div class="ui positive button" onclick="onAbolishOk()">
                    OK
                </div>
                <div class="ui button" onclick="closeDeleteModal()">
                    Cancel
                </div>
            </div>
        </div>

        {{-- 廃止送信用フォーム（コメントフォームとは別フォーム） --}}
        <form id="abolish-form"
            method="POST"
            action="{{ route('cweb.cases.abolish', $case) }}">
            @csrf
            <input type="hidden" name="abolish_comment" id="abolish-comment-hidden">
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
                            {{ optional($comment->user)->name ?? '－' }}
                            ／
                            {{ $comment->created_at?->format('Y/m/d H:i') ?? '' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>{{-- .cweb-case-right --}}
</div>{{-- .cweb-case-layout --}}

<script>
function goEditPage() {
    window.location.href = "{{ route('cweb.cases.edit', $case) }}";
}

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
        alert('コメントを入力してください。');
        return;
    }

    document.getElementById('abolish-comment-hidden').value = comment;

    closeDeleteModal();
    document.getElementById('abolish-form').submit();
}

/* ▼ コメント投稿時の送信先選択モーダル */
document.addEventListener('DOMContentLoaded', function () {
    const form        = document.getElementById('comment-form');
    const openBtn     = document.getElementById('wfbtnsendmsg');
    const selection   = document.getElementById('selectionmodal');
    const dimmer      = document.getElementById('selection-dimmer');
    const okBtn       = document.getElementById('selection-ok');
    const cancelBtn   = document.getElementById('selection-cancel');

    if (!form || !openBtn || !selection || !dimmer || !okBtn || !cancelBtn) {
        return;
    }

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

    openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openSelectionModal();
    });

    okBtn.addEventListener('click', function () {
        closeSelectionModal();
        form.submit();
    });

    cancelBtn.addEventListener('click', function () {
        closeSelectionModal();
    });

    dimmer.addEventListener('click', function (e) {
        if (e.target === dimmer) {
            closeSelectionModal();
        }
    });
});

function copyFolderPath() {
    const path = "\\\\ftktake01\\QWeb_Data\\Specification\\{{ $case->manage_no }}";

    // Clipboard API（HTTPS / localhost向け）
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(path)
            .then(() => {
                // 成功時：何もしない
            })
            .catch(() => {
                alert("フォルダが存在しません。");
            });
        return;
    }

    // フォールバック（http等でも動く可能性あり）
    fallbackCopy(path);
}

function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);

    ta.select();
    ta.setSelectionRange(0, ta.value.length);

    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch (e) {
        ok = false;
    } finally {
        document.body.removeChild(ta);
    }

    if (!ok) {
        alert("フォルダが存在しません。");
    }
}
</script>

@endsection
