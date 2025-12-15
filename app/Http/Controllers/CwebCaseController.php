<?php

namespace App\Http\Controllers;

use App\Models\CwebCase;
use App\Models\CwebCaseSharedUser;
use App\Models\CwebCasePcnItem;
use App\Models\CwebCaseOtherRequirement;
use App\Models\CwebCaseWillAllocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CwebCaseController extends Controller
{
    /**
     * 一覧
     */
    public function index(Request $request, string $locale)
    {
        $user = $request->user();

        $tab       = (string)$request->query('tab', 'all');
        $keyword   = trim((string)$request->query('keyword', ''));
        $sort      = (string)$request->query('sort', '');
        $direction = $request->query('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $status       = (string)$request->query('status', '');
        $category     = (string)$request->query('category', '');
        $productGroup = (string)$request->query('product_group', '');
        $productCode  = (string)$request->query('product_code', '');

        // ▼ tab=product はそのまま（あなたの既存ロジック）
        if ($tab === 'product') {
            $groupOptions = [
                'HogoMax-内製品'   => ['102','103','104','105','106','107','108','152','153','201','202','203','204'],
                'HogoMax-OEM品'    => ['002','003'],
                'StayClean-内製品' => ['201','301','401'],
                'StayClean-OEM品'  => ['-A','-F','-R'],
                'ResiFlat-内製品'  => ['103'],
                'その他'           => [],
            ];

            $hasGroup = $productGroup !== '';

            $contractSummary = [
                'standard' => 0,
                'pcn'      => 0,
                'other'    => 0,
            ];

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
                $casesForProductQuery = CwebCase::query()
                    ->with('pcnItems')
                    ->where('status', 'active')
                    ->where('product_group', $productGroup);

                if ($productCode !== '') {
                    $casesForProductQuery->where('product_code', $productCode);
                }

                $casesForProduct = $casesForProductQuery->get();

                $contractSummary['standard'] = $casesForProduct->where('category_standard', true)->count();
                $contractSummary['pcn']      = $casesForProduct->where('category_pcn',      true)->count();
                $contractSummary['other']    = $casesForProduct->where('category_other',    true)->count();

                foreach ($casesForProduct as $case) {
                    foreach ($case->pcnItems as $pcn) {
                        $catRaw  = trim((string)$pcn->category);
                        $months  = (int)($pcn->months_before ?? 0);

                        foreach ($pcnCategoryDefs as $label => $candidates) {
                            if (!in_array($catRaw, $candidates, true)) continue;

                            $pcnSummary[$label]['count']++;

                            $pcnSummary[$label]['cases'][] = [
                                'id'            => $case->id,
                                'manage_no'     => $case->manage_no,
                                'customer_name' => $case->customer_name,
                                'months_before' => $months,
                            ];

                            if ($pcnSummary[$label]['max_months'] === null || $months > $pcnSummary[$label]['max_months']) {
                                $pcnSummary[$label]['max_months'] = $months;
                                $pcnSummary[$label]['customer']   = $case->customer_name;
                            }

                            break;
                        }
                    }
                }

                foreach ($pcnSummary as &$s) {
                    usort($s['cases'], fn($a,$b) => ($b['months_before'] ?? 0) <=> ($a['months_before'] ?? 0));
                }
                unset($s);
            }

            return view('cweb.cases.product', [
                'locale'          => $locale,
                'tab'             => $tab,
                'productGroup'    => $productGroup,
                'productCode'     => $productCode,
                'groupOptions'    => $groupOptions,
                'contractSummary' => $contractSummary,
                'pcnSummary'      => $pcnSummary,
            ]);
        }

        // ▼ 通常一覧
        $query = CwebCase::query()
            ->leftJoin('users as sales', 'sales.employee_number', '=', 'cweb_cases.sales_contact_employee_number')
            ->select('cweb_cases.*', 'sales.name as sales_contact_employee_name');

        if ($tab === 'mine') {
            $userId = $user->id;
            $empNo  = (string) $user->employee_number;

            $query->where(function ($q) use ($userId, $empNo) {
                $q->where('created_by_user_id', $userId);
                if ($empNo !== '') $q->orWhere('sales_contact_employee_number', $empNo);
            });
        }

        if ($keyword !== '') {
            $kw = mb_strtolower($keyword, 'UTF-8');
            $query->where(function ($inner) use ($kw) {
                $inner->whereRaw('LOWER(cweb_cases.manage_no) LIKE ?', ["%{$kw}%"])
                    ->orWhereRaw('LOWER(cweb_cases.customer_name) LIKE ?', ["%{$kw}%"])
                    ->orWhereRaw('LOWER(sales.name) LIKE ?', ["%{$kw}%"]);
            });
        }

        if ($status !== '') $query->where('cweb_cases.status', $status);

        if ($category === 'standard') $query->where('cweb_cases.category_standard', true);
        if ($category === 'pcn')      $query->where('cweb_cases.category_pcn', true);
        if ($category === 'other')    $query->where('cweb_cases.category_other', true);

        if ($productGroup !== '') $query->where('cweb_cases.product_group', $productGroup);
        if ($productCode !== '')  $query->where('cweb_cases.product_code', $productCode);

        switch ($sort) {
            case 'customer': $query->orderBy('cweb_cases.customer_name', $direction); break;
            case 'status':   $query->orderBy('cweb_cases.status', $direction); break;
        }

        $query->orderBy('cweb_cases.manage_no', 'desc');

        $productGroups = CwebCase::select('product_group')
            ->whereNotNull('product_group')->distinct()->orderBy('product_group')->pluck('product_group');

        $productCodes = CwebCase::select('product_code')
            ->whereNotNull('product_code')->distinct()->orderBy('product_code')->pluck('product_code');

        $cases = $query->paginate(15);

        return view('cweb.cases.index', [
            'locale'          => $locale,
            'cases'           => $cases,
            'tab'             => $tab,
            'sort'            => $sort,
            'direction'       => $direction,
            'keyword'         => $keyword,
            'status'          => $status,
            'category'        => $category,
            'productGroup'    => $productGroup,
            'productCode'     => $productCode,
            'productGroups'   => $productGroups,
            'productCodes'    => $productCodes,
            'contractSummary' => ['standard'=>0,'pcn'=>0,'other'=>0],
            'pcnSummary'      => [],
        ]);
    }

    /**
     * 新規作成画面
     */
    public function create(string $locale)
    {
        $lastCase = CwebCase::whereNotNull('manage_no')->orderBy('created_at', 'desc')->first();
        $nextManagementNo = 'SP-250001';

        if ($lastCase && $lastCase->manage_no && preg_match('/^SP-(\d+)$/', $lastCase->manage_no, $m)) {
            $num = (int)$m[1] + 1;
            $nextManagementNo = 'SP-' . str_pad($num, 6, '0', STR_PAD_LEFT);
        }

        return view('cweb.cases.create', [
            'locale'          => $locale,
            'nextManagementNo'=> $nextManagementNo,
            'isEditing'       => false,
        ]);
    }

    /**
     * 新規登録保存
     */
    public function store(Request $request, string $locale)
    {
        $currentUser = $request->user();

        $validator = Validator::make(
            $request->all(),
            [
                'sales_employee_number' => ['required', 'string', 'max:10'],
                'cost_owner_code'       => ['required', 'string', 'max:20'],
                'customer_name'         => ['required', 'string', 'max:255'],
                'categories'            => ['required', 'array', 'min:1'],
                'categories.*'          => ['string'],
                'product_main'          => ['required', 'string', 'max:100'],
                'product_sub'           => ['nullable', 'string', 'max:50'],

                'shared_employee_numbers'   => ['nullable', 'array'],
                'shared_employee_numbers.*' => ['string', 'max:10'],
                'other_requirement_text'    => ['nullable', 'string'],

                'will_initial' => ['nullable', 'integer', 'min:0'],
                'will_monthly' => ['nullable', 'integer', 'min:0'],

                'pcn_items'                 => ['nullable', 'array'],
                'pcn_items.*.category'      => ['nullable', 'string'],
                'pcn_items.*.title'         => ['nullable', 'string', 'max:255'],
                'pcn_items.*.months_before' => ['nullable', 'integer', 'min:0'],

                'other_requirements'                               => ['nullable', 'array'],
                'other_requirements.*.content'                     => ['nullable', 'string'],
                'other_requirements.*.responsible_employee_number' => ['nullable', 'string', 'max:10'],
                'other_requirements.*.responsible_label'           => ['nullable', 'string', 'max:100'],

                'will_allocations'                   => ['nullable', 'array'],
                'will_allocations.*.employee_number' => ['nullable', 'string', 'max:10'],
                'will_allocations.*.employee_name'   => ['nullable', 'string', 'max:100'],
                'will_allocations.*.percentage'      => ['nullable', 'integer', 'min:0', 'max:100'],
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

        $validator->after(function ($v) use ($request) {
    $productMain = $request->input('product_main');
    $productSub  = $request->input('product_sub');

    // 「その他」以外のときだけ品番必須
    if ($productMain !== 'その他' && !filled($productSub)) {
        $v->errors()->add('product_sub', '対象製品（品番）を選択してください。');
    }

if ($productMain !== 'その他' && !filled($productSub)) {
    $v->errors()->add('product_sub', '対象製品（品番）を選択してください。');
}

            foreach ((array)$request->input('pcn_items', []) as $i => $row) {
                $cat   = $row['category'] ?? null;
                $title = $row['title'] ?? null;
                $month = $row['months_before'] ?? null;

                $hasAny = filled($cat) || filled($title) || filled($month);
                if ($hasAny) {
                    if (!filled($cat))   $v->errors()->add("pcn_items.$i.category", "PCN管理項目（".($i+1)."行目）：区分を選択してください。");
                    if (!filled($title)) $v->errors()->add("pcn_items.$i.title", "PCN管理項目（".($i+1)."行目）：ラベル変更などを入力してください。");
                    if (!filled($month)) $v->errors()->add("pcn_items.$i.months_before", "PCN管理項目（".($i+1)."行目）：ヵ月前連絡を入力してください。");
                }
            }

            foreach ((array)$request->input('other_requirements', []) as $i => $row) {
                $content = $row['content'] ?? null;
                $respNo  = $row['responsible_employee_number'] ?? null;
                $respLbl = $row['responsible_label'] ?? null;

                $hasAny = filled($content) || filled($respNo) || filled($respLbl);
                if ($hasAny) {
                    if (!filled($content)) $v->errors()->add("other_requirements.$i.content", "その他要求（".($i+1)."行目）：要求内容を入力してください。");
                    if (!filled($respNo))  $v->errors()->add("other_requirements.$i.responsible_employee_number", "その他要求（".($i+1)."行目）：対応者を選択してください。");
                    if (!filled($respLbl)) $v->errors()->add("other_requirements.$i.responsible_label", "その他要求（".($i+1)."行目）：対応者名が未設定です（再選択してください）。");
                }
            }

            $willInit    = $request->input('will_initial');
            $willMonthly = $request->input('will_monthly');
            if (filled($willInit) || filled($willMonthly)) {
                if (!filled($willInit))    $v->errors()->add('will_initial', 'Will：登録費を入力してください（片方だけは不可）。');
                if (!filled($willMonthly)) $v->errors()->add('will_monthly', 'Will：月額を入力してください（片方だけは不可）。');
            }

            $allocNonEmpty = collect((array)$request->input('will_allocations', []))
                ->filter(fn($row) =>
                    filled($row['employee_number'] ?? null) ||
                    filled($row['employee_name'] ?? null) ||
                    filled($row['percentage'] ?? null)
                );

            foreach ($allocNonEmpty as $i => $row) {
                if (!filled($row['employee_number'] ?? null)) $v->errors()->add("will_allocations.$i.employee_number", "月額管理費の分配（".($i+1)."行目）：担当者を選択してください。");
                if (!filled($row['employee_name'] ?? null))   $v->errors()->add("will_allocations.$i.employee_name", "月額管理費の分配（".($i+1)."行目）：担当者名が未設定です（再選択してください）。");
                if (!filled($row['percentage'] ?? null))      $v->errors()->add("will_allocations.$i.percentage", "月額管理費の分配（".($i+1)."行目）：割合(%)を入力してください。");
            }

            $percentTotal = $allocNonEmpty->sum(fn($r) => (int)($r['percentage'] ?? 0));
            if ($percentTotal !== 0 && $percentTotal !== 100) {
                $v->errors()->add('will_allocations', '月額管理費の分配の合計％は 0 または 100 にしてください。');
            }
        });

        $data = $validator->validate();

        $data['pcn_items'] = collect($data['pcn_items'] ?? [])
            ->filter(fn($r) => filled($r['category'] ?? null) || filled($r['title'] ?? null) || filled($r['months_before'] ?? null))
            ->map(function ($r) {
                $r['months_before'] = (int)($r['months_before'] ?? 0);
                return $r;
            })
            ->values()->all();

        $data['other_requirements'] = collect($data['other_requirements'] ?? [])
            ->filter(fn($r) => filled($r['content'] ?? null) || filled($r['responsible_employee_number'] ?? null) || filled($r['responsible_label'] ?? null))
            ->values()->all();

        $data['will_allocations'] = collect($data['will_allocations'] ?? [])
            ->filter(fn($r) => filled($r['employee_number'] ?? null) || filled($r['employee_name'] ?? null) || filled($r['percentage'] ?? null))
            ->values()->all();

        $salesUser = User::where('employee_number', $data['sales_employee_number'])->first();
        $nextManageNo = CwebCase::nextManagementNo();

        DB::transaction(function () use ($currentUser, $data, $salesUser, $nextManageNo) {
            $categories = (array)($data['categories'] ?? []);

            $case = CwebCase::create([
                'manage_no'          => $nextManageNo,
                'status'             => 'active',
                'created_by_user_id' => $currentUser->id,

                'customer_name'      => $data['customer_name'],
                'sales_contact_employee_number' => $data['sales_employee_number'],
                'cost_responsible_code'         => $data['cost_owner_code'],

                'category_standard' => in_array('standard', $categories, true),
                'category_pcn'      => in_array('pcn', $categories, true),
                'category_other'    => in_array('other', $categories, true),

                'product_group' => $data['product_main'],
                'product_code'  => $data['product_sub'] ?? null,

                'other_request_note'      => $data['other_requirement_text'] ?? null,
                'will_registration_cost'  => $data['will_initial'] ?? null,
                'will_monthly_cost'       => $data['will_monthly'] ?? null,
                'related_qweb'            => request()->input('related_qweb'),
            ]);

            if (method_exists($case, 'comments')) {
                $case->comments()->create([
                    'body'    => "【登録】\n案件を登録しました。",
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($data['shared_employee_numbers'] ?? [] as $empNo) {
                $u = User::where('employee_number', $empNo)->first();
                if (!$u) continue;

                CwebCaseSharedUser::create([
                    'case_id' => $case->id,
                    'user_id' => $u->id,
                    'role'    => 'shared',
                ]);
            }

            if ($salesUser) {
                CwebCaseSharedUser::create([
                    'case_id' => $case->id,
                    'user_id' => $salesUser->id,
                    'role'    => 'sales',
                ]);
            }

            foreach ($data['pcn_items'] as $item) {
                CwebCasePcnItem::create([
                    'case_id'       => $case->id,
                    'category'      => $item['category'] ?? '',
                    'title'         => $item['title'] ?? null,
                    'months_before' => (int)($item['months_before'] ?? 0),
                    'note'          => null,
                ]);
            }

            foreach ($data['other_requirements'] as $req) {
                CwebCaseOtherRequirement::create([
                    'case_id'                    => $case->id,
                    'content'                    => $req['content'] ?? '',
                    'responsible_employee_number'=> $req['responsible_employee_number'] ?? null,
                ]);
            }

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
    ->route('cweb.cases.index', ['locale' => $locale])
    ->with('ok', '案件を登録しました。');


    }

    /**
     * 詳細
     */
    public function show(Request $request, string $locale, CwebCase $case)
    {
        $case->load([
            'sharedUsers.user',
            'pcnItems',
            'otherRequirements',
            'willAllocations',
            'comments.user',
        ]);

        $sharedUsers = $case->sharedUsers
            ->where('role', 'shared')
            ->filter(fn($row) => $row->user)
            ->unique('user_id')
            ->values();

        $categories = [];
        if ($case->category_standard) $categories[] = '標準管理';
        if ($case->category_pcn)      $categories[] = 'PCN';
        if ($case->category_other)    $categories[] = 'その他要求';
        $categoryLabel = $categories ? implode(' / ', $categories) : '-';

        $statusLabel = match ($case->status ?? '') {
            'active' => 'アクティブ',
            'closed' => '廃止',
            default  => '不明',
        };

        $productLabel = trim(($case->product_group ?? '') . ' ' . ($case->product_code ?? ''));

        $salesEmployeeNumber = $case->sales_contact_employee_number;
        $salesEmployeeName   = null;
        if (!empty($salesEmployeeNumber)) {
            $salesUser = User::where('employee_number', $salesEmployeeNumber)->first();
            $salesEmployeeName = optional($salesUser)->name;
        }

        $otherNumbers = $case->otherRequirements
            ? $case->otherRequirements->pluck('responsible_employee_number')->filter()->unique()->values()
            : collect();

        $otherEmployees = $otherNumbers->isNotEmpty()
            ? User::whereIn('employee_number', $otherNumbers)->get()->keyBy('employee_number')
            : collect();

        return view('cweb.cases.show', [
            'locale'              => $locale,
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

    /**
     * 編集画面
     */
    public function edit(string $locale, CwebCase $case)
    {
        return view('cweb.cases.edit', [
            'locale'          => $locale,
            'case'            => $case,
            'nextManagementNo'=> $case->manage_no,
        ]);
    }

    /**
     * 更新
     */
public function update(Request $request, string $locale, CwebCase $case)
{
    // ▼ 更新前の値（DBにある元値）を控える
    $original = $case->getOriginal();

    // ▼ バリデーション
    $data = $request->validate([
        'sales_employee_number' => ['required', 'string'],
        'customer_name'         => ['required', 'string', 'max:255'],
        'categories'            => ['required', 'array'],
        'product_main'          => ['required', 'string'],
        'product_sub' => ['nullable', 'string', 'max:50'],
        'cost_owner_code'       => ['required', 'string'],
        'will_initial'          => ['nullable', 'integer', 'min:0'],
        'will_monthly'          => ['nullable', 'integer', 'min:0'],
        'related_qweb'          => ['nullable', 'string'],
    ]);

    // ===== 差分表示用の小道具 =====
    $fmt = function ($v) {
        if ($v === null) return '-';
        if ($v === '') return '-';
        return (string)$v;
    };

    $fmtWill = function ($v) {
        if ($v === null || $v === '') return '-';
        return number_format((int)$v) . ' will';
    };

    $catsLabel = function (bool $standard, bool $pcn, bool $other) {
        $arr = [];
        if ($standard) $arr[] = '標準管理';
        if ($pcn)      $arr[] = 'PCN';
        if ($other)    $arr[] = 'その他要求';
        return $arr ? implode(' / ', $arr) : '-';
    };

    // ▼ 更新後の値をセット（まだ save しない）
    $case->sales_contact_employee_number = $data['sales_employee_number'];
    $case->customer_name                 = $data['customer_name'];
    $case->product_group                 = $data['product_main'];
    $case->product_code                  = $data['product_sub'];
    $case->cost_responsible_code         = $data['cost_owner_code'];

    $cats = $data['categories'] ?? [];
    $case->category_standard = in_array('standard', $cats, true);
    $case->category_pcn      = in_array('pcn',      $cats, true);
    $case->category_other    = in_array('other',    $cats, true);

    $case->will_registration_cost = $data['will_initial'] ?? null;
    $case->will_monthly_cost      = $data['will_monthly'] ?? null;
    $case->related_qweb           = $data['related_qweb'] ?? null;

    // ===== ここから差分生成（save前に作るのがコツ） =====
    // 旧/新で名前も出したい項目があるので、必要ユーザーをまとめて取得
    $oldSalesEmpNo = (string)($original['sales_contact_employee_number'] ?? '');
    $newSalesEmpNo = (string)($case->sales_contact_employee_number ?? '');

    $salesUsers = User::whereIn('employee_number', array_values(array_filter([
        $oldSalesEmpNo ?: null,
        $newSalesEmpNo ?: null,
    ])))->get()->keyBy('employee_number');

    $oldSalesName = $oldSalesEmpNo ? optional($salesUsers[$oldSalesEmpNo] ?? null)->name : null;
    $newSalesName = $newSalesEmpNo ? optional($salesUsers[$newSalesEmpNo] ?? null)->name : null;

    $lines = [];
    $add = function (string $label, $old, $new, $formatter = null) use (&$lines, $fmt) {
        $f = $formatter ?: $fmt;
        $o = $f($old);
        $n = $f($new);
        if ($o !== $n) {
            $lines[] = "・{$label}：{$o} → {$n}";
        }
    };

    // ▼ 差分（メイン項目）
    $add('顧客名',      $original['customer_name'] ?? null, $case->customer_name);
    $add('費用負担先',  $original['cost_responsible_code'] ?? null, $case->cost_responsible_code);
    $add('対象製品（製品）', $original['product_group'] ?? null, $case->product_group);
    $add('対象製品（品番）', $original['product_code'] ?? null, $case->product_code);

    // 営業窓口は「社員番号 / 名前」まで表示
    $oldSalesDisp = trim(($oldSalesEmpNo ?: '-') . ($oldSalesName ? " / {$oldSalesName}" : ''));
    $newSalesDisp = trim(($newSalesEmpNo ?: '-') . ($newSalesName ? " / {$newSalesName}" : ''));
    $add('営業窓口', $oldSalesDisp, $newSalesDisp);

    // カテゴリー（bool3つ → 表示ラベル）
    $oldCatLabel = $catsLabel(
        (bool)($original['category_standard'] ?? false),
        (bool)($original['category_pcn'] ?? false),
        (bool)($original['category_other'] ?? false),
    );
    $newCatLabel = $catsLabel(
        (bool)($case->category_standard ?? false),
        (bool)($case->category_pcn ?? false),
        (bool)($case->category_other ?? false),
    );
    $add('カテゴリー', $oldCatLabel, $newCatLabel);

    // Will
    $add('Will（登録費）', $original['will_registration_cost'] ?? null, $case->will_registration_cost, $fmtWill);
    $add('Will（月額）',   $original['will_monthly_cost'] ?? null,      $case->will_monthly_cost,      $fmtWill);

    // 関連Q-WEB
    $add('関連Q-WEB', $original['related_qweb'] ?? null, $case->related_qweb);

    // ===== 保存 =====
    $case->save();

    // ✅ 更新コメント（差分付き）
    if (method_exists($case, 'comments')) {
        $body = "【更新】\n";
        $body .= $lines
            ? implode("\n", $lines)
            : "変更はありませんでした。";

        $case->comments()->create([
            'body'    => $body,
            'user_id' => auth()->id(),
        ]);
    }

    return redirect()
        ->route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id])
        ->with('ok', '案件を更新しました。');
}

    /**
     * 廃止：GET は画面を返さず、詳細へ戻す（B運用）
     * （web.php に cases.abolish.form があるので、メソッドだけ用意）
     */
    public function abolishForm(string $locale, CwebCase $case)
    {
        return redirect()->route('cweb.cases.show', [
            'locale' => $locale,
            'case'   => $case->id,
        ]);
    }

    /**
     * 廃止：POST（フォーム実行）
     */
    public function abolish(Request $request, string $locale, CwebCase $case)
    {
        $validated = $request->validate([
            'abolish_comment' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($case, $validated) {
            $commentText = $validated['abolish_comment'];

            if (method_exists($case, 'comments')) {
                $case->comments()->create([
                    'body'    => "【廃止】\n" . $commentText,
                    'user_id' => auth()->id(),
                ]);
            }

            $case->status = 'closed';
            // もしカラムがあるならついでに入れる（無ければOK）
            if (\Schema::hasColumn('cweb_cases', 'abolished_by_user_id')) $case->abolished_by_user_id = auth()->id();
            if (\Schema::hasColumn('cweb_cases', 'abolished_comment'))   $case->abolished_comment   = $commentText;
            if (\Schema::hasColumn('cweb_cases', 'abolished_at'))        $case->abolished_at        = now();

            $case->save();
        });

        return redirect()
            ->route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id])
            ->with('ok', '案件を廃止しました。');
    }

    /**
     * products.summary が route にあるので、無いと 404 になる。
     * 使ってないなら route 側を消してOK。
     */
    public function productSummary(Request $request, string $locale)
    {
        return redirect()->route('cweb.cases.index', [
            'locale' => $locale,
            'tab'    => 'product',
        ]);
    }
}
