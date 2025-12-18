<?php

namespace App\Http\Controllers;

use App\Models\CwebCase;
use App\Models\CwebCaseComment;
use App\Models\User;
use Illuminate\Http\Request;

class CwebCaseCommentController extends Controller
{
    public function store(Request $request, string $locale, CwebCase $case)
    {
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],

            // 選択送信先（社員番号配列）
            'val_mailsend'   => ['nullable', 'array'],
            'val_mailsend.*' => ['string', 'max:10'],
        ]);

        // コメント保存
        $comment = CwebCaseComment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'body'    => $validated['body'],
        ]);

        // ▼ 選択された社員番号 → メールアドレス
        $empNos = $validated['val_mailsend'] ?? [];

        $emails = [];
        if (!empty($empNos)) {
            $emails = User::query()
                ->whereIn('employee_number', $empNos)
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->pluck('email')
                ->unique()
                ->values()
                ->all();
        }

        // ▼ mailto を組み立て（件名・本文）
        $subject = 'New Comment コメントが投稿されました / C-WEB /(' . $case->manage_no . ')';
        $body    = (string)($validated['body'] ?? '');

        $mailto = $this->buildMailto($emails, $subject, $body);

        // ✅ show に戻す（次画面でJSが mailto を開く想定）
        return redirect()
            ->route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id])
            ->with('ok', 'コメントを投稿しました。')
            ->with('mailto_url', $mailto)                 // ★これをBlade側で拾う
            ->with('open_mailinfo', true);                // ★任意：開いたよポップアップ用
    }

    private function buildMailto(array $emails, string $subject, string $body): string
    {
        $to = implode(',', $emails);

        return 'mailto:' . $to
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }
}
