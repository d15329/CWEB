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
    $data['pcn_items'] = collect($data['pcn_items'] ?? [])
        ->filter(function ($row) {
            return filled($row['category'] ?? null)
                || filled($row['title'] ?? null)
                || filled($row['months_before'] ?? null);
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
            'months_before' => $item['months_before'] ?? null,
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
    $status   = (string)$request->query('status', '');
    $category = (string)$request->query('category', '');
    $productGroup = (string)$request->query('product_group', '');
    $productCode  = (string)$request->query('product_code', '');
    $query = CwebCase::query()
        ->leftJoin('users as sales', 'sales.employee_number', '=', 'cweb_cases.sales_contact_employee_number')
        ->select('cweb_cases.*', 'sales.name as sales_contact_employee_name');

    // ▼ 「あなたが関わる案件」タブ
    if ($tab === 'mine') {
        $query->where(function ($inner) use ($user) {
            $inner->where('cweb_cases.created_by_user_id', $user->id);

            if (!empty($user->employee_number)) {
                $inner->orWhere('cweb_cases.sales_contact_employee_number', $user->employee_number);
            }
        });
    }

    // ▼ キーワード検索
    if ($keyword !== '') {
        $query->where(function ($inner) use ($keyword) {
            $inner->where('cweb_cases.manage_no', 'like', "%{$keyword}%")
                  ->orWhere('cweb_cases.customer_name', 'like', "%{$keyword}%")
                  ->orWhere('sales.name', 'like', "%{$keyword}%");
        });
    }

    // ▼ ステータス絞り込み
    if ($status !== '') {
        $query->where('cweb_cases.status', $status);
    }

    // ▼ カテゴリー絞り込み（booleanフラグ想定）
    if ($category === 'standard') {
        $query->where('cweb_cases.category_standard', true);
    } elseif ($category === 'pcn') {
        $query->where('cweb_cases.category_pcn', true);
    } elseif ($category === 'other') {
        $query->where('cweb_cases.category_other', true);
    }
        // ▼ 対象製品：大カテゴリのみ or 大+小 の両方をサポート
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
    // ▼ ソート
switch ($sort) {
    case 'customer':
        $query->orderBy('cweb_cases.customer_name', $direction);
        break;
    case 'status':
        $query->orderBy('cweb_cases.status', $direction);
        break;
}
// 降順
$query->orderBy('cweb_cases.manage_no', 'desc');
    $cases = $query->paginate(15); // ← appends は Blade 側でやるならここまで

    return view('cweb.cases.index', [
        'cases'     => $cases,
        'tab'       => $tab,
        'sort'      => $sort,
        'direction' => $direction,
        'keyword'   => $keyword,
        'status'    => $status,
        'category'  => $category,
        'productGroup' => $productGroup,
        'productCode'  => $productCode,
        'productGroups' => $productGroups,
        'productCodes'  => $productCodes,
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
        // 他に渡している変数もここに並べる
    ]);
}
}