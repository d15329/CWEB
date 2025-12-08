<?php

namespace App\Http\Controllers;

use App\Models\CwebCase;
use App\Models\CwebCaseSharedUser;
use App\Models\CwebCasePcnItem;
use App\Models\CwebCaseOtherRequirement;
use App\Models\CwebCaseWillAllocation;
use App\Models\CwebCaseComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CwebCaseController extends Controller
{
 public function abolish(Request $request, CwebCase $case)
    {
        // 必要なら権限チェック
        // $this->authorize('update', $case);

        // ポップアップDで入力したコメントを受け取る
        $validated = $request->validate([
            'abolish_comment' => ['required', 'string', 'max:2000'],
        ]);

        $commentText = $validated['abolish_comment'];

        // ① コメントをコメント欄に残したい場合（任意）
        // ※ ここはあなたのコメント用テーブル/リレーション名に合わせて調整してね
        if (method_exists($case, 'comments')) {
            $case->comments()->create([
                'body'    => "【廃止】\n" . $commentText,
                'user_id' => auth()->id(),
            ]);
        }

        // ② ステータスを廃止に変更（DBに status カラムがある前提）
        $case->status = 'closed';   // active / closed で運用している想定
        $case->save();

        // ③ 完了後は案件詳細に戻す or 一覧に戻す
        return redirect()
            ->route('cweb.cases.index')
            ->with('ok', '案件を廃止しました。');
    }

    // 新規登録保存
public function store(Request $request)
{
    $currentUser = $request->user();

    // ▼ ① Validator生成
    $validator = Validator::make(
        $request->all(),
        [
            // ▼ 必須
            'sales_employee_number' => ['required', 'string', 'max:10'],
            'cost_owner_code'       => ['required', 'string', 'max:20'],
            'customer_name'         => ['required', 'string', 'max:255'],
            'categories'            => ['required', 'array', 'min:1'],
            'categories.*'          => ['string'],
            'product_main'          => ['required', 'string', 'max:100'],
            'product_sub'           => ['required', 'string', 'max:50'],

            // ▼ 任意
            'shared_employee_numbers'   => ['nullable', 'array'],
            'shared_employee_numbers.*' => ['string', 'max:10'],

            'other_requirement_text'    => ['nullable', 'string'],

            'will_initial' => ['nullable', 'integer', 'min:0'],
            'will_monthly' => ['nullable', 'integer', 'min:0'],

            'pcn_items'                 => ['nullable', 'array'],
            'pcn_items.*.category'      => ['nullable', 'string'],
            'pcn_items.*.title'         => ['nullable', 'string', 'max:255'],
            'pcn_items.*.months_before' => ['nullable', 'integer', 'min:0'],

            'other_requirements'                        => ['nullable', 'array'],
            'other_requirements.*.content'              => ['nullable', 'string'],
            'other_requirements.*.responsible_employee_number' => ['nullable', 'string', 'max:10'],

            'will_allocations'                     => ['nullable', 'array'],
            'will_allocations.*.employee_number'  => ['nullable', 'string', 'max:10'],
            'will_allocations.*.employee_name'    => ['nullable', 'string', 'max:100'],
            'will_allocations.*.percentage'       => ['nullable', 'integer', 'min:0', 'max:100'],
        ],
        [
            'sales_employee_number.required' => '営業窓口を選択してください。',
            'cost_owner_code.required'       => '費用負担先を選択してください。',
            'customer_name.required'         => '顧客名を入力してください。',
            'categories.required'            => 'カテゴリーを1つ以上選択してください。',
            'product_main.required'          => '対象製品（製品）を選択してください。',
            'product_sub.required'           => '対象製品（品番）を選択してください。',
        ]
    );

    // ▼ ② 一旦バリデーション（ここで通常の required 等をチェック）
    $data = $validator->validate();

    // ▼ ③ 行追加系：空行除去（今まで通り）
// ▼ ③ 行追加系：空行除去（PCN）
$data['pcn_items'] = collect($data['pcn_items'] ?? [])
    ->filter(function ($row) {
        // 何か1つでも入力されているか
        $hasAny = filled($row['category'] ?? null)
               || filled($row['title'] ?? null)
               || filled($row['months_before'] ?? null);

        if (!$hasAny) {
            // 完全空行は捨てる
            return false;
        }

        // 何か入力してあるのに months_before が空欄なら、その行も捨てる
        if (!filled($row['months_before'] ?? null)) {
            return false;
        }

        return true;
    })
    ->map(function ($row) {
        // months_before を必ず int にしておく
        $row['months_before'] = (int)($row['months_before'] ?? 0);
        return $row;
    })
    ->values()
    ->all();

    $data['other_requirements'] = collect($data['other_requirements'] ?? [])
        ->filter(function ($row) {
            return filled($row['content'] ?? null)
                || filled($row['responsible_employee_number'] ?? null);
        })
        ->values()
        ->all();

    $data['will_allocations'] = collect($data['will_allocations'] ?? [])
        ->filter(function ($row) {
            return filled($row['employee_number'] ?? null)
                || filled($row['percentage'] ?? null);
        })
        ->values()
        ->all();

    // ▼ ④ Will分配の合計％チェック
    $percentTotal = collect($data['will_allocations'])
        ->sum(fn ($alloc) => (int)($alloc['percentage'] ?? 0));

    // ★ 合計が 0 でも 100 でもない → エラーを追加して戻る
    if ($percentTotal !== 0 && $percentTotal !== 100) {
        $validator->errors()->add(
            'will_allocations',
            '月額管理費の分配の合計％は 0 または 100 にしてください。'
        );

        return back()
            ->withErrors($validator)
            ->withInput();
    }

    // ▼ ⑤ ここから下は今までと同じ（$data を使って保存）
    //   $salesUser や $nextManageNo、DB::transaction(...) はそのまま
    //   ...



    // ④ 営業窓口ユーザー
    $salesUser = User::where('employee_number', $data['sales_employee_number'])->first();


// ⑤ 管理番号
$nextManageNo = CwebCase::nextManagementNo();

// ⑥ トランザクションで保存
DB::transaction(function () use ($currentUser, $data, $salesUser, $nextManageNo) {

    // ▼ カテゴリ配列（standard / pcn / other）
    $categories = (array)($data['categories'] ?? []);

    // --- 案件本体 ---
    $case = CwebCase::create([
        'manage_no'          => $nextManageNo,
        'status'             => 'active',
        'created_by_user_id' => $currentUser->id,

        // 顧客名
        'customer_name'      => $data['customer_name'],

        // ★ 営業窓口 → 社員番号をそのまま保持
        'sales_contact_employee_number' => $data['sales_employee_number'],

        // ★ 費用負担先 → コードを保持
        'cost_responsible_code'         => $data['cost_owner_code'],

        // ★ カテゴリ（チェックボックス → bool 3つ）
        'category_standard' => in_array('standard', $categories, true),
        'category_pcn'      => in_array('pcn',      $categories, true),
        'category_other'    => in_array('other',    $categories, true),

        // ★ 対象製品（製品名＋品番）
        'product_group' => $data['product_main'],
        'product_code'  => $data['product_sub'],

        // ★ その他要求の自由記述（使うなら）
        'other_request_note' => $data['other_requirement_text'] ?? null,

        // ★ Will
        'will_registration_cost' => $data['will_initial'] ?? null,
        'will_monthly_cost'      => $data['will_monthly'] ?? null,

        // 関連Q-WEB（あれば）
        'related_qweb' => request()->input('related_qweb'),
    ]);

    // --- 情報共有者 ---
    foreach ($data['shared_employee_numbers'] ?? [] as $empNo) {
        $u = User::where('employee_number', $empNo)->first();
        if (!$u) {
            continue;
        }
        CwebCaseSharedUser::create([
            'case_id' => $case->id,
            'user_id' => $u->id,
            'role'    => 'shared',
        ]);
    }

    // --- 営業窓口も shared に入れる場合 ---
    if ($salesUser) {
        CwebCaseSharedUser::create([
            'case_id' => $case->id,
            'user_id' => $salesUser->id,
            'role'    => 'sales',
        ]);
    }

// --- PCN項目 ---
foreach ($data['pcn_items'] as $item) {
    CwebCasePcnItem::create([
        'case_id'       => $case->id,
        'category'      => $item['category'] ?? '',
        'title'         => $item['title'] ?? null,
        'months_before' => (int)$item['months_before'],  // ← 必ず int
        'note'          => null,
    ]);
}


    // --- その他要求 ---
    foreach ($data['other_requirements'] as $req) {
        CwebCaseOtherRequirement::create([
            'case_id'                    => $case->id,
            'content'                    => $req['content'] ?? '',
            'responsible_employee_number'=> $req['responsible_employee_number'] ?? null,
        ]);
    }

    // --- Will 分配 ---
    foreach ($data['will_allocations'] as $alloc) {
        CwebCaseWillAllocation::create([
            'case_id'         => $case->id,
            'employee_number' => $alloc['employee_number'] ?? '',
            'employee_name'   => $alloc['employee_name'] ?? '',
            'percentage'      => (int)($alloc['percentage'] ?? 0),
        ]);
    }
});

    return redirect()
        ->route('cweb.cases.index')
        ->with('ok', '案件を登録しました。');
}


public function index(Request $request)
{
    $user = $request->user();

    $tab       = (string)$request->query('tab', 'all');
    $keyword   = trim((string)$request->query('keyword', ''));
    $sort      = (string)$request->query('sort', '');
    $direction = $request->query('direction', 'asc') === 'desc' ? 'desc' : 'asc';

    // ★ 絞り込み用
    $status       = (string)$request->query('status', '');
    $category     = (string)$request->query('category', '');
    $productGroup = (string)$request->query('product_group', '');
    $productCode  = (string)$request->query('product_code', '');

    /*
    |--------------------------------------------------------------------------
    | ① tab=product のときだけ、専用ビューに切り替え
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | ① tab=product のときだけ、専用ビューに切り替え
    |--------------------------------------------------------------------------
    */
    if ($tab === 'product') {

        // ▼ プルダウン①〜②の中身（仕様どおり固定）
        $groupOptions = [
            'HogoMax-内製品'   => ['102','103','104','105','106','107','108','152','153','201','202','203','204'],
            'HogoMax-OEM品'    => ['002','003'],
            'StayClean-内製品' => ['201','301','401'],
            'StayClean-OEM品'  => ['-A','-F','-R'],
            'ResiFlat-内製品'  => ['103'],
            'その他'           => [],
        ];

        $hasGroup = $productGroup !== '';

        // ▼ 契約登録数（標準管理 / PCN / その他要求）
        $contractSummary = [
            'standard' => 0,
            'pcn'      => 0,
            'other'    => 0,
        ];

        // ▼ PCN 管理対象のカテゴリ定義
        //   -> 配列の key が画面ラベル（仕様書内容〜その他変更）
        //   -> value に DB 上の category の候補値を全部並べる
$pcnCategoryDefs = [
    '仕様書内容' => ['spec', '仕様書内容'],
    '人員変更'   => ['man', '人員変更'],
    '設備変更'   => ['machine', '設備変更'],
    '手順変更'   => ['method', '手順変更'],
    '材料変更'   => ['material', '材料変更'],
    '測定変更'   => ['measurement', '測定変更'],
    '環境変更'   => ['environment', '環境変更'],
    'その他変更' => ['other', 'other_change', 'その他変更'],
        ];

        // 初期値
        $pcnSummary = [];
        foreach ($pcnCategoryDefs as $label => $_candidates) {
            $pcnSummary[$label] = [
                'label'      => $label,
                'count'      => 0,
                'max_months' => null,
                'customer'   => null,
                'cases'      => [],
            ];
        }

        if ($hasGroup) {
            /*
             * ▼ 対象案件を一気に取得（pcnItems をリレーションでロード）
             */
            $casesForProductQuery = CwebCase::query()
                ->with('pcnItems')            // ← ここがポイント
                ->where('status', 'active')
                ->where('product_group', $productGroup);

            if ($productCode !== '') {
                $casesForProductQuery->where('product_code', $productCode);
            }

            $casesForProduct = $casesForProductQuery->get();

            // 契約登録数（標準管理 / PCN / その他要求）
            $contractSummary['standard'] = $casesForProduct->where('category_standard', true)->count();
            $contractSummary['pcn']      = $casesForProduct->where('category_pcn',      true)->count();
            $contractSummary['other']    = $casesForProduct->where('category_other',    true)->count();

            /*
             * ▼ PCNカテゴリごとの集計
             */
            foreach ($casesForProduct as $case) {
                foreach ($case->pcnItems as $pcn) {
                    $catRaw  = trim((string)$pcn->category);
                    $months  = (int)($pcn->months_before ?? 0);

                    // どのカテゴリラベルに属するか判定
                    foreach ($pcnCategoryDefs as $label => $candidates) {
                        if (!in_array($catRaw, $candidates, true)) {
                            continue;
                        }

                        // 件数カウント
                        $pcnSummary[$label]['count']++;

                        // 詳細（案件一覧）
                        $pcnSummary[$label]['cases'][] = [
                            'id'            => $case->id, 
                            'manage_no'     => $case->manage_no,
                            'customer_name' => $case->customer_name,
                            'months_before' => $months,
                        ];

                        // 最長通知期間 & その顧客
                        if (
                            $pcnSummary[$label]['max_months'] === null ||
                            $months > $pcnSummary[$label]['max_months']
                        ) {
                            $pcnSummary[$label]['max_months'] = $months;
                            $pcnSummary[$label]['customer']   = $case->customer_name;
                        }

                        // どこか1カテゴリにマッチしたら次のPCN行へ
                        break;
                    }
                }
            }

            // 通知期間の数字が大きい順に並べ直し（詳細行用）
            foreach ($pcnSummary as &$s) {
                usort($s['cases'], function ($a, $b) {
                    return ($b['months_before'] ?? 0) <=> ($a['months_before'] ?? 0);
                });
            }
            unset($s);
        }

        // ★ ここで product 用ビューを返して終了
        return view('cweb.cases.product', [
            'tab'             => $tab,
            'productGroup'    => $productGroup,
            'productCode'     => $productCode,
            'groupOptions'    => $groupOptions,
            'contractSummary' => $contractSummary,
            'pcnSummary'      => $pcnSummary,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ② ここから下は「すべて」「あなたが関わる案件」タブ用（元コードそのまま）
    |--------------------------------------------------------------------------
    */
    $query = CwebCase::query()
        ->leftJoin('users as sales', 'sales.employee_number', '=', 'cweb_cases.sales_contact_employee_number')
        ->select('cweb_cases.*', 'sales.name as sales_contact_employee_name');

    // 「あなたが関わる案件」タブ
    if ($tab === 'mine') {
        $userId = $user->id;
        $empNo  = (string) $user->employee_number;

        $query->where(function ($q) use ($userId, $empNo) {
            $q->where('created_by_user_id', $userId);

            if ($empNo !== '') {
                $q->orWhere('sales_contact_employee_number', $empNo);
            }
        });
    }

    // キーワード検索
    if ($keyword !== '') {
        $query->where(function ($inner) use ($keyword) {
            $inner->where('cweb_cases.manage_no', 'like', "%{$keyword}%")
                  ->orWhere('cweb_cases.customer_name', 'like', "%{$keyword}%")
                  ->orWhere('sales.name', 'like', "%{$keyword}%");
        });
    }

    // ステータス絞り込み
    if ($status !== '') {
        $query->where('cweb_cases.status', $status);
    }

    // カテゴリー絞り込み
    if ($category === 'standard') {
        $query->where('cweb_cases.category_standard', true);
    } elseif ($category === 'pcn') {
        $query->where('cweb_cases.category_pcn', true);
    } elseif ($category === 'other') {
        $query->where('cweb_cases.category_other', true);
    }

    // 対象製品絞り込み（既存仕様）
    if ($productGroup !== '') {
        $query->where('cweb_cases.product_group', $productGroup);
    }
    if ($productCode !== '') {
        $query->where('cweb_cases.product_code', $productCode);
    }

    $productGroups = CwebCase::select('product_group')
        ->whereNotNull('product_group')
        ->distinct()
        ->orderBy('product_group')
        ->pluck('product_group');

    $productCodes = CwebCase::select('product_code')
        ->whereNotNull('product_code')
        ->distinct()
        ->orderBy('product_code')
        ->pluck('product_code');

    // ソート
    switch ($sort) {
        case 'customer':
            $query->orderBy('cweb_cases.customer_name', $direction);
            break;
        case 'status':
            $query->orderBy('cweb_cases.status', $direction);
            break;
    }

    // 管理番号降順
    $query->orderBy('cweb_cases.manage_no', 'desc');

        // ▼ 集計用の初期値
    $contractSummary = [
        'standard' => 0,
        'pcn'      => 0,
        'other'    => 0,
    ];
    $pcnSummary = [];

    // ▼ 「製品ごとの要求内容一覧」タブ ＆ 製品が選択されているときだけ集計
    if ($tab === 'product' && $productGroup !== '') {

        // 対象製品の案件を全部取得（pcnItems もまとめてロード）
        $casesForProductQuery = CwebCase::query()
            ->with('pcnItems')
            ->where('product_group', $productGroup);

        if ($productCode !== '') {
            $casesForProductQuery->where('product_code', $productCode);
        }

        $casesForProduct = $casesForProductQuery->get();

        // 契約登録数（標準管理 / PCN / その他要求）
        $contractSummary['standard'] = $casesForProduct->where('category_standard', true)->count();
        $contractSummary['pcn']      = $casesForProduct->where('category_pcn',      true)->count();
        $contractSummary['other']    = $casesForProduct->where('category_other',    true)->count();

        // PCN の項目ごとの集計定義
        // ※ category に入っている値に合わせて match を調整してOK
        $pcnCategories = [
            'spec' => [
                'label' => '仕様書内容',
                'match' => ['仕様書内容', 'spec'],
            ],
            'personnel' => [
                'label' => '人員変更',
                'match' => ['人員変更', 'personnel'],
            ],
            'equipment' => [
                'label' => '設備変更',
                'match' => ['設備変更', 'equipment'],
            ],
            'procedure' => [
                'label' => '手順変更',
                'match' => ['手順変更', 'procedure'],
            ],
            'material' => [
                'label' => '材料変更',
                'match' => ['材料変更', 'material'],
            ],
            'measurement' => [
                'label' => '測定変更',
                'match' => ['測定変更', 'measurement'],
            ],
            'environment' => [
                'label' => '環境変更',
                'match' => ['環境変更', 'environment'],
            ],
            'other' => [
                'label' => 'その他変更',
                'match' => ['その他変更', 'other'],
            ],
        ];

        // 初期化
        foreach ($pcnCategories as $key => $info) {
            $pcnSummary[$key] = [
                'label'      => $info['label'],
                'count'      => 0,
                'max_months' => null,
                'customer'   => null,
                'cases'      => [],
            ];
        }

        // 案件ごとに PCN 項目を集計
        foreach ($casesForProduct as $case) {
            foreach ($case->pcnItems as $pcn) {
                $cat = (string) $pcn->category;

                // どのカテゴリに属するか探索
                foreach ($pcnCategories as $key => $info) {
                    if (!in_array($cat, $info['match'], true)) {
                        continue;
                    }

                    // このカテゴリの集計に反映
                    $months = (int) ($pcn->months_before ?? 0);

                    $pcnSummary[$key]['count']++;

                    $pcnSummary[$key]['cases'][] = [
                        'manage_no'     => $case->manage_no,
                        'customer_name' => $case->customer_name,
                        'months_before' => $months,
                    ];

                    // 最長通知期間＆その顧客
                    if (
                        $pcnSummary[$key]['max_months'] === null ||
                        $months > $pcnSummary[$key]['max_months']
                    ) {
                        $pcnSummary[$key]['max_months'] = $months;
                        $pcnSummary[$key]['customer']   = $case->customer_name;
                    }

                    // マッチしたら次のPCN項目へ
                    break;
                }
            }
        }

        // 詳細表示用に、各カテゴリの cases を「通知期間の大きい順」にソート
        foreach ($pcnSummary as &$s) {
            usort($s['cases'], function ($a, $b) {
                return ($b['months_before'] ?? 0) <=> ($a['months_before'] ?? 0);
            });
        }
        unset($s);
    }


    $cases = $query->paginate(15);

    return view('cweb.cases.index', [
        'cases'        => $cases,
        'tab'          => $tab,
        'sort'         => $sort,
        'direction'    => $direction,
        'keyword'      => $keyword,
        'status'       => $status,
        'category'     => $category,
        'productGroup' => $productGroup,
        'productCode'  => $productCode,
        'productGroups'=> $productGroups,
        'productCodes' => $productCodes,
        'contractSummary' => $contractSummary,
        'pcnSummary'      => $pcnSummary,
    ]);
}


    // 管理番号（SP-25xxxx）を採番
public function create()
{
    // 直近の管理番号を持っているレコードを1件取得
    $lastCase = CwebCase::whereNotNull('manage_no')
        ->orderBy('created_at', 'desc')   // created_at がある前提
        ->first();

    // デフォルト値（1件も無い場合）
    $nextManagementNo = 'SP-250001';

    if ($lastCase && $lastCase->manage_no) {
        // "SP-250004" みたいな形式を想定
        if (preg_match('/^SP-(\d+)$/', $lastCase->manage_no, $m)) {
            $num = (int)$m[1] + 1;                             // +1
            $nextManagementNo = 'SP-' . str_pad($num, 6, '0', STR_PAD_LEFT);
            // 例: 250004 → 250005 → "SP-250005"
        }
    }

    return view('cweb.cases.create', [
        'nextManagementNo' => $nextManagementNo,
        'isEditing'        => true,
        // 他に渡している変数もここに並べる
    ]);
}
public function show(Request $request, CwebCase $case)
{
    // ▼ ここは「関数の中」です！この中に $case->load(...) を書く
    $case->load([
        'sharedUsers.user',       // 情報共有者（中間テーブル→User）
        'pcnItems',
        'otherRequirements',
        'willAllocations',
        'comments.user',
    ]);

        $sharedUsers = $case->sharedUsers
        ->where('role', 'shared')         // sales は除外
        ->filter(fn ($row) => $row->user) // user がいるものだけ
        ->unique('user_id')               // 同じ人が重複していたら1件にまとめる
        ->values();

    // カテゴリー表示用
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

    // ステータス
    $statusLabel = match ($case->status ?? '') {
        'active' => 'アクティブ',
        'closed' => '廃止',
        default  => '不明',
    };

    // 製品（グループ＋コード）
    $productLabel = trim(($case->product_group ?? '') . ' ' . ($case->product_code ?? ''));

    // ▼ 営業窓口（社員番号 → 名前）
    $salesEmployeeNumber = $case->sales_contact_employee_number;
    $salesEmployeeName   = null;
    if (!empty($salesEmployeeNumber)) {
        $salesUser = User::where('employee_number', $salesEmployeeNumber)->first();
        $salesEmployeeName = optional($salesUser)->name;
    }

    // ▼ その他要求：対応者の社員番号 → User 名のマップ
    $otherNumbers = $case->otherRequirements
        ? $case->otherRequirements
            ->pluck('responsible_employee_number')
            ->filter()
            ->unique()
            ->values()
        : collect();

    $otherEmployees = $otherNumbers->isNotEmpty()
        ? User::whereIn('employee_number', $otherNumbers)->get()->keyBy('employee_number')
        : collect();

    return view('cweb.cases.show', [
        'case'                => $case,
        'categoryLabel'       => $categoryLabel,
        'statusLabel'         => $statusLabel,
        'productLabel'        => $productLabel ?: '-',
        'salesEmployeeNumber' => $salesEmployeeNumber,
        'salesEmployeeName'   => $salesEmployeeName,
        'otherEmployees'      => $otherEmployees,
         'sharedUsers'         => $sharedUsers, 
    ]);
}
public function edit(CwebCase $case)
{
    // いったんは「編集画面」のビューを呼び出すだけ
    // （中身は create.blade.php をベースにした edit.blade.php をこれから作る想定）
    return view('cweb.cases.edit', [
        'case'              => $case,
        'nextManagementNo'  => $case->manage_no, // ヘッダーの管理番号表示に使う
    ]);
    
}
public function update(Request $request, CwebCase $case)
    {
        // ▼ バリデーション（最低限）
        $data = $request->validate([
            'sales_employee_number' => ['required', 'string'],
            'customer_name'         => ['required', 'string', 'max:255'],
            'categories'            => ['required', 'array'],
            'product_main'          => ['required', 'string'],
            'product_sub'           => ['required', 'string'],
            'cost_owner_code'       => ['required', 'string'],
            'will_initial'          => ['nullable', 'integer', 'min:0'],
            'will_monthly'          => ['nullable', 'integer', 'min:0'],
            'related_qweb'          => ['nullable', 'string'],
        ]);

        // ▼ 基本項目の更新
        $case->sales_contact_employee_number = $data['sales_employee_number'];
        $case->customer_name                 = $data['customer_name'];
        $case->product_group                 = $data['product_main'];
        $case->product_code                  = $data['product_sub'];
        $case->cost_responsible_code         = $data['cost_owner_code'];

        // カテゴリー（チェックボックス → フラグ）
        $cats = $data['categories'] ?? [];
        $case->category_standard = in_array('standard', $cats, true);
        $case->category_pcn      = in_array('pcn',      $cats, true);
        $case->category_other    = in_array('other',    $cats, true);

        // Will関連
        $case->will_registration_cost = $data['will_initial'] ?? null;
        $case->will_monthly_cost      = $data['will_monthly'] ?? null;

        // 関連Q-WEB
        $case->related_qweb = $data['related_qweb'] ?? null;

        $case->save();

        /*
         * ▼ ここから下は「子テーブル更新」が必要ならあとで肉付け
         *   - $request->input('pcn_items', [])
         *   - $request->input('other_requirements', [])
         *   - $request->input('will_allocations', [])
         *   - $request->input('shared_employee_numbers', [])
         *   を使って、紐づくレコードを入れ直すイメージ。
         */

        return redirect()
            ->route('cweb.cases.index')
            ->with('ok', '案件を更新しました。');
    }

}