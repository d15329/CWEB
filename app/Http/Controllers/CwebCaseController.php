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

class CwebCaseController extends Controller
{
    // 新規登録保存
    public function store(Request $request)
    {
        $currentUser = $request->user();

        // ① バリデーション
        $data = $request->validate(
            [
                'customer_name' => ['required', 'string', 'max:255'],
                'categories'    => ['nullable', 'array'],
                'categories.*'  => ['string'],
                'product_main'  => ['nullable', 'string', 'max:100'],
                'product_sub'   => ['nullable', 'string', 'max:50'],

                'sales_employee_number' => ['required', 'string', 'max:10'],
                'shared_employee_numbers'   => ['nullable', 'array'],
                'shared_employee_numbers.*' => ['string', 'max:10'],

                'cost_owner_code' => ['required', 'string', 'max:20'],

                'other_requirement_text' => ['nullable', 'string'],

                'will_initial' => ['nullable', 'integer', 'min:0'],
                'will_monthly' => ['nullable', 'integer', 'min:0'],

                // PCN項目
                'pcn_items'                 => ['nullable', 'array'],
                'pcn_items.*.category'      => ['required_with:pcn_items.*.title,pcn_items.*.months_before', 'string'],
                'pcn_items.*.title'         => ['nullable', 'string', 'max:255'],
                'pcn_items.*.months_before' => ['nullable', 'integer', 'min:0'],

                // その他要求
                'other_requirements'                        => ['nullable', 'array'],
                'other_requirements.*.content'              => ['required_with:other_requirements.*.responsible_employee_number', 'string'],
                'other_requirements.*.responsible_employee_number' => ['nullable', 'string', 'max:10'],

                // Will分配
                'will_allocations'                     => ['nullable', 'array'],
                'will_allocations.*.employee_number'  => ['required_with:will_allocations.*.percentage', 'string', 'max:10'],
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

        // ② Will分配の合計％チェック
        $percentTotal = 0;
        foreach ($data['will_allocations'] ?? [] as $alloc) {
            $percentTotal += (int)($alloc['percentage'] ?? 0);
        }
        if ($percentTotal !== 0 && $percentTotal !== 100) {
            return back()
                ->withErrors(['will_allocations' => '月額管理費の分配の合計％は100にしてください。'])
                ->withInput();
        }

        // ③ 営業窓口のユーザーを users から探す（なければ null）
        $salesUser = User::where('employee_number', $data['sales_employee_number'])->first();

        // ④ 管理番号の採番（SP-250001 形式）
        $nextManageNo = $this->generateNextManageNo();

        // ⑤ トランザクションで一括保存
        DB::transaction(function () use ($currentUser, $data, $salesUser, $nextManageNo) {
            // --- 案件本体 ---
            $case = CwebCase::create([
                'manage_no'      => $nextManageNo,
                'status'         => 'active',
                'created_by'     => $currentUser->id,
                'sales_user_id'  => $salesUser?->id,
                'customer_name'  => $data['customer_name'],
                'categories'     => !empty($data['categories']) ? json_encode($data['categories']) : null,
                'product_main'   => $data['product_main'] ?? null,
                'product_sub'    => $data['product_sub'] ?? null,
                'other_requirement_text' => $data['other_requirement_text'] ?? null,
                'will_initial'   => $data['will_initial'] ?? null,
                'will_monthly'   => $data['will_monthly'] ?? null,
            ]);

            // --- 情報共有者（B） ---
            foreach ($data['shared_employee_numbers'] ?? [] as $empNo) {
                $u = User::where('employee_number', $empNo)->first();
                if (!$u) {
                    continue;
                }
                CwebCaseSharedUser::create([
                    'case_id' => $case->id,
                    'user_id' => $u->id,
                    'role'    => 'shared',   // 情報共有
                ]);
            }

            // --- 営業窓口も shared に入れておきたいならここで ---
            if ($salesUser) {
                CwebCaseSharedUser::create([
                    'case_id' => $case->id,
                    'user_id' => $salesUser->id,
                    'role'    => 'sales',
                ]);
            }

            // --- PCN項目 ---
            foreach ($data['pcn_items'] ?? [] as $item) {
                if (empty($item['category']) && empty($item['title']) && empty($item['months_before'])) {
                    continue;
                }
                CwebCasePcnItem::create([
                    'case_id'       => $case->id,
                    'category'      => $item['category'] ?? '',
                    'title'         => $item['title'] ?? null,
                    'months_before' => $item['months_before'] ?? null,
                    'note'          => null,
                ]);
            }

            // --- その他要求 ---
            foreach ($data['other_requirements'] ?? [] as $req) {
                if (empty($req['content']) && empty($req['responsible_employee_number'])) {
                    continue;
                }
                CwebCaseOtherRequirement::create([
                    'case_id'                   => $case->id,
                    'content'                   => $req['content'] ?? '',
                    'responsible_employee_number' => $req['responsible_employee_number'] ?? null,
                ]);
            }

            // --- Will 分配 ---
            foreach ($data['will_allocations'] ?? [] as $alloc) {
                if (empty($alloc['employee_number']) || $alloc['percentage'] === null) {
                    continue;
                }
                CwebCaseWillAllocation::create([
                    'case_id'         => $case->id,
                    'employee_number' => $alloc['employee_number'],
                    'employee_name'   => $alloc['employee_name'] ?? '',
                    'percentage'      => (int)$alloc['percentage'],
                ]);
            }

            // --- ここでメール通知なども発火させられる ---
            // $this->sendCaseCreatedMail($case);
        });

        return redirect()
            ->route('cweb.cases.index')
            ->with('ok', '案件を登録しました。');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // タブ: all / mine / product
        $tab = $request->query('tab', 'all');

        $query = CwebCase::query();

        // 「あなたが関わる案件」タブのときだけフィルタ
        if ($tab === 'mine') {
            $query->where(function ($inner) use ($user) {
                $inner->where('created_by', $user->id)
                      ->orWhere('sales_user_id', $user->id);
                // sharedUsers まで含めたいならあとで追加しよう
            });
        }

        // ※ tab === 'product' のときも今は同じ挙動にしておく（あとで肉付け）
        $cases = $query
            ->orderBy('manage_no')
            ->paginate(15);

        return view('cweb.cases.index', [
            'cases' => $cases,
            'tab'   => $tab,
        ]);
    }


    // 管理番号（SP-25xxxx）を採番
    private function generateNextManageNo(): string
    {
        // 年度から 25 の部分を作るとか、今は固定で 25 とする
        $prefix = 'SP-25';

        $last = CwebCase::where('manage_no', 'like', $prefix.'%')
            ->orderBy('manage_no', 'desc')
            ->first();

        if (!$last) {
            return $prefix.'0001';
        }

        $num = (int)substr($last->manage_no, -4); // 下4桁
        $nextNum = $num + 1;

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
        public function create()
    {
        // ① まずは仮で固定値
    $nextManagementNo = 'SP-250001';

        // ② 案件番号をビューに渡して新規登録画面を表示
        return view('cweb.cases.create', [
        'nextManagementNo' => $nextManagementNo,
        ]);
    }
}
