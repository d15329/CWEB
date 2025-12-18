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
use App\Models\CwebQualityMaster;
use App\Models\CwebProductOwner;

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

// $isShared = \App\Models\CwebCaseSharedUser::where('case_id', $case->id)
//     ->where('user_id', $user->id)
//     ->exists();


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
                // ✅ キーを canonical（辞書キー）にする
                'spec' => [
                    'candidates' => ['spec', '仕様書内容'],
                ],
                'man' => [
                    'candidates' => ['man', '人', '人員変更'],
                ],
                'machine' => [
                    'candidates' => ['machine', '機械', '設備変更'],
                ],
                'material' => [
                    'candidates' => ['material', '材料', '材料変更'],
                ],
                'method' => [
                    'candidates' => ['method', '方法', '手順変更'],
                ],
                'measurement' => [
                    // ※ typo 吸収も入れとく（既存データ対策）
                    'candidates' => ['measurement', '測定', '測定変更', 'mesurement', 'Mesurement'],
                ],
                'environment' => [
                    'candidates' => ['environment', '環境', '環境変更'],
                ],
                'other' => [
                    'candidates' => ['other', 'その他', 'その他変更', 'other_change'],
                ],
                'uncategorized' => [
                    'candidates' => ['uncategorized', '未分類', ''],
                ],
            ];

            $pcnSummary = [];
            foreach ($pcnCategoryDefs as $key => $def) {
                $pcnSummary[$key] = [
                    'label'      => $key,   // 予備（基本はBladeで翻訳する）
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

                        foreach ($pcnCategoryDefs as $label => $def) {
                            $candidates = $def['candidates'] ?? [];
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
    $userId    = $user->id;
    $empNoRaw  = (string)($user->employee_number ?? '');
    $empNoNorm = ltrim($empNoRaw, '0');
    if ($empNoNorm === '') $empNoNorm = '0';

    // ✅ 品証マスタ者は「全案件」
    $isQualityMaster = CwebQualityMaster::query()
        ->where('is_active', true)
        ->whereRaw("ltrim(employee_number, '0') = ?", [$empNoNorm])
        ->exists();

    // 品証マスタ者でない場合だけ「関わる案件」絞り込み
    if (!$isQualityMaster) {
        $query->where(function ($q) use ($userId, $empNoNorm) {

            // 1) 営業窓口者
            $q->whereRaw("ltrim(cweb_cases.sales_contact_employee_number, '0') = ?", [$empNoNorm]);

            // 2) 情報共有者（role=shared の user_id）
            $q->orWhereExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('cweb_case_shared_users as csu')
                    ->whereColumn('csu.case_id', 'cweb_cases.id')
                    ->where('csu.role', 'shared')
                    ->where('csu.user_id', $userId);
            });

            // 3) その他要求対応者（社員番号一致）
            $q->orWhereExists(function ($sub) use ($empNoNorm) {
                $sub->select(DB::raw(1))
                    ->from('cweb_case_other_requirements as cor')
                    ->whereColumn('cor.case_id', 'cweb_cases.id')
                    ->whereNotNull('cor.responsible_employee_number')
                    ->whereRaw("ltrim(cor.responsible_employee_number, '0') = ?", [$empNoNorm]);
            });

            // 4) 当該製品の担当者（product_group + product_code 一致 & employee_number一致）
            $q->orWhereExists(function ($sub) use ($empNoNorm) {
                $sub->select(DB::raw(1))
                    ->from('cweb_product_owners as po')
                    ->where('po.is_active', true)
                    ->whereColumn('po.product_group', 'cweb_cases.product_group')
                    ->whereColumn('po.product_code',  'cweb_cases.product_code')
                    ->whereNotNull('po.employee_number')
                    ->whereRaw("ltrim(po.employee_number, '0') = ?", [$empNoNorm]);
            });
        });
    }
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

        // ★ 追加：作成した案件を外で使う（redirectをshowへ）
        $createdCase = null;

        DB::transaction(function () use ($currentUser, $data, $salesUser, $nextManageNo, &$createdCase) {
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

            // ★ 追加：transaction外へ返す
            $createdCase = $case;
        });

        // ✅ 登録メール：品証マスタ + 営業窓口 + 情報共有者 + その他要求対応者 + 製品担当者（全員）
        $createdCase->load(['sharedUsers.user','otherRequirements']);
        $empNos = $this->getCaseRecipientEmpNos($createdCase);
        $emails = $this->empNosToEmails($empNos);

        $subject = "Registration_顧客要求の新規登録がありました / C-WEB /(".$createdCase->manage_no.")";
        $caseUrl = route('cweb.cases.show', ['locale' => $locale, 'case' => $createdCase->id]);
        $body    = $caseUrl . "\n" . "新規登録しました：" . ($currentUser->name ?? '-');

        $mailto = $this->buildMailto($emails, $subject, $body);

        return redirect()
            ->route('cweb.cases.show', ['locale' => $locale, 'case' => $createdCase->id])
            ->with('ok', '案件を登録しました。')
            ->with('mailto_url', $mailto);
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

    // ✅ 1回表示したら消す（コメント投稿で再表示されない）
    $openMailRegistration = session()->pull('open_mail_registration', false);

    // ✅ コメント送信先（品証なし＆デフォルトONルール適用＆0埋め吸収）
    [$mailRecipients, $empToEmail, $defaultCheckedEmails] = $this->buildCommentMailRecipients($case);

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

        // ★ここがBladeの表示＆JSの根拠になる
        'mailRecipients'      => $mailRecipients,
        'empToEmail'          => $empToEmail,
        'defaultCheckedEmails'=> $defaultCheckedEmails,
        'openMailRegistration'=> $openMailRegistration,
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
        // （ここはあなたの既存のまま）
        $original = $case->getOriginal();

        $data = $request->validate([
            'sales_employee_number' => ['required', 'string'],
            'customer_name'         => ['required', 'string', 'max:255'],
            'categories'            => ['required', 'array'],
            'product_main'          => ['required', 'string'],
            'product_sub'           => ['nullable', 'string', 'max:50'],
            'cost_owner_code'       => ['required', 'string'],
            'will_initial'          => ['nullable', 'integer', 'min:0'],
            'will_monthly'          => ['nullable', 'integer', 'min:0'],
            'related_qweb'          => ['nullable', 'string'],
        ]);

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

        $add('顧客名',      $original['customer_name'] ?? null, $case->customer_name);
        $add('費用負担先',  $original['cost_responsible_code'] ?? null, $case->cost_responsible_code);
        $add('対象製品（製品）', $original['product_group'] ?? null, $case->product_group);
        $add('対象製品（品番）', $original['product_code'] ?? null, $case->product_code);

        $oldSalesDisp = trim(($oldSalesEmpNo ?: '-') . ($oldSalesName ? " / {$oldSalesName}" : ''));
        $newSalesDisp = trim(($newSalesEmpNo ?: '-') . ($newSalesName ? " / {$newSalesName}" : ''));
        $add('営業窓口', $oldSalesDisp, $newSalesDisp);

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

        $add('Will（登録費）', $original['will_registration_cost'] ?? null, $case->will_registration_cost, $fmtWill);
        $add('Will（月額）',   $original['will_monthly_cost'] ?? null,      $case->will_monthly_cost,      $fmtWill);

        $add('関連Q-WEB', $original['related_qweb'] ?? null, $case->related_qweb);

        $case->save();

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
            if (\Schema::hasColumn('cweb_cases', 'abolished_by_user_id')) $case->abolished_by_user_id = auth()->id();
            if (\Schema::hasColumn('cweb_cases', 'abolished_comment'))   $case->abolished_comment   = $commentText;
            if (\Schema::hasColumn('cweb_cases', 'abolished_at'))        $case->abolished_at        = now();

            $case->save();
        });

        // ✅ 廃止メール：品証マスタ + 営業窓口 + 情報共有者 + その他要求対応者 + 製品担当者（全員）
        $case->load(['sharedUsers.user','otherRequirements']);
        $empNos = $this->getCaseRecipientEmpNos($case);
        $emails = $this->empNosToEmails($empNos);

        $subject = "Abolition_顧客要求が廃止されました / C-WEB /(".$case->manage_no.")";
        $body    = "廃止されました：" . (auth()->user()->name ?? '-') . "\n"
                 . "コメント:" . ($validated['abolish_comment'] ?? '');

        $mailto = $this->buildMailto($emails, $subject, $body);

        return redirect()
            ->route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id])
            ->with('ok', '案件を廃止しました。')
            ->with('mailto_url', $mailto);
    }

    /**
     * products.summary が route にあるので、無いと 404 になる。
     */
    public function productSummary(Request $request, string $locale)
    {
        return redirect()->route('cweb.cases.index', [
            'locale' => $locale,
            'tab'    => 'product',
        ]);
    }

private function buildCommentMailRecipients(CwebCase $case): array
{
    // 正規化（先頭0除去）
    $norm = function ($v) {
        $s = trim((string)$v);
        $s = ltrim($s, '0');
        return $s === '' ? '0' : $s;
    };

    // 候補（品証なし）
    $creatorEmpNo = null;
    if (!empty($case->created_by_user_id)) {
        $creatorEmpNo = optional(User::find($case->created_by_user_id))->employee_number;
    }

    $salesEmpNo = $case->sales_contact_employee_number ?? null;

    $sharedEmpNos = collect($case->sharedUsers ?? [])
        ->filter(fn($row) => ($row->role ?? null) === 'shared')
        ->map(fn($row) => optional($row->user)->employee_number)
        ->filter()
        ->values()
        ->all();

    $otherEmpNos = collect($case->otherRequirements ?? [])
        ->pluck('responsible_employee_number')
        ->filter()
        ->values()
        ->all();

    $productEmpNos = [];
    if (!empty($case->product_group) && !empty($case->product_code)) {
        $productEmpNos = CwebProductOwner::query()
            ->where('is_active', true)
            ->where('product_group', $case->product_group)
            ->where('product_code',  $case->product_code)
            ->pluck('employee_number')
            ->filter()
            ->values()
            ->all();
    }

    // raw候補
    $rawEmpNos = collect([$creatorEmpNo, $salesEmpNo])
        ->merge($sharedEmpNos)
        ->merge($otherEmpNos)
        ->merge($productEmpNos)
        ->filter()
        ->map(fn($v) => trim((string)$v))
        ->filter(fn($v) => $v !== '')
        ->unique()
        ->values()
        ->all();

    // 正規化候補で users を引く（0埋め違い吸収）
    $normEmpNos = collect($rawEmpNos)->map($norm)->unique()->values()->all();

    $users = User::query()
        ->select([
            'employee_number',
            'name',
            'email',
            DB::raw("ltrim(employee_number::text, '0') as emp_no_norm"),
        ])
        ->whereIn(DB::raw("ltrim(employee_number::text, '0')"), $normEmpNos)
        ->get();

    $byNorm = $users->keyBy('emp_no_norm');

    $findUserByEmp = function ($empNo) use ($byNorm, $norm) {
        $n = $norm($empNo);
        return $byNorm[$n] ?? null;
    };

    // recipients：連想配列禁止（キー崩壊するので）→ リストで返す
    $recipients = [];
    $seen = []; // keyは prefix 付きで数値化を防ぐ

    $push = function ($empNo, bool $checked) use (&$recipients, &$seen, $findUserByEmp, $norm) {
        $empNo = trim((string)$empNo);
        if ($empNo === '') return;

        $nKey = 'n' . $norm($empNo); // 例: n123（数値化防止）
        $u = $findUserByEmp($empNo);

        $dispEmpNo = $u->employee_number ?? $empNo;

        if (!isset($seen[$nKey])) {
            $seen[$nKey] = count($recipients);
            $recipients[] = [
                'employee_number' => (string)$dispEmpNo,
                'name'            => (string)($u->name ?? ''),
                'checked'         => $checked,
            ];
        } else {
            if ($checked) {
                $recipients[$seen[$nKey]]['checked'] = true;
            }
        }
    };

    // ルール：登録者OFF、他ON
    if ($creatorEmpNo) $push($creatorEmpNo, false);
    if ($salesEmpNo)   $push($salesEmpNo, true);
    foreach ($sharedEmpNos as $e)  $push($e, true);
    foreach ($otherEmpNos as $e)   $push($e, true);
    foreach ($productEmpNos as $e) $push($e, true);

    // empToEmail：キーに prefix を付けて数値化を防ぐ（JSも同じprefixで引く）
    $empToEmail = [];
    foreach ($recipients as $r) {
        $u = $findUserByEmp($r['employee_number']);
        $empToEmail['e'.$r['employee_number']] = $u->email ?? null;
    }

    $defaultCheckedEmails = collect($recipients)
        ->filter(fn($r) => !empty($r['checked']))
        ->map(fn($r) => $empToEmail['e'.$r['employee_number']] ?? null)
        ->filter()
        ->values()
        ->all();

    return [$recipients, $empToEmail, $defaultCheckedEmails];
}


    private function getCaseRecipientEmpNos(CwebCase $case): array
    {
        // 品証マスタ
        $qualityEmpNos = CwebQualityMaster::query()
            ->where('is_active', true)
            ->pluck('employee_number')
            ->all();

        // 営業窓口
        $salesEmpNo = $case->sales_contact_employee_number ?? null;

        // 情報共有者（role=shared）
        $sharedEmpNos = collect($case->sharedUsers ?? [])
            ->filter(fn($row) => ($row->role ?? null) === 'shared')
            ->map(fn($row) => optional($row->user)->employee_number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // その他要求対応者
        $otherEmpNos = collect($case->otherRequirements ?? [])
            ->pluck('responsible_employee_number')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // 製品担当者（product_group + product_code でDBから）
        $productEmpNos = [];
        $pg = $case->product_group ?? null;
        $pc = $case->product_code ?? null;
        if ($pg && $pc) {
            $productEmpNos = CwebProductOwner::query()
                ->where('is_active', true)
                ->where('product_group', $pg)
                ->where('product_code', $pc)
                ->pluck('employee_number')
                ->unique()
                ->values()
                ->all();
        }

        return collect([])
            ->merge($qualityEmpNos)
            ->push($salesEmpNo)
            ->merge($sharedEmpNos)
            ->merge($otherEmpNos)
            ->merge($productEmpNos)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function empNosToEmails(array $empNos): array
    {
        if (empty($empNos)) return [];

        return User::query()
            ->whereIn('employee_number', $empNos)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }

    private function buildMailto(array $emails, string $subject, string $body): string
    {
        $to = implode(',', $emails);

        return 'mailto:' . $to
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }
}
