<?php

namespace App\Http\Controllers;

use App\Models\CwebCase;
use App\Models\CwebCaseComment;
use App\Services\CwebMailDraftService;
use Illuminate\Http\Request;

class CwebMailDraftController extends Controller
{
    public function open(Request $request, string $locale, CwebCase $case, CwebMailDraftService $svc)
    {
        $draft = session('mailDraft');

        // draftが無いなら普通に戻す
        if (!$draft || !is_array($draft)) {
            return redirect()->route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id]);
        }

        $type      = $draft['type'] ?? 'comment';
        $toEmpNos  = (array)($draft['to_empnos'] ?? []);
        $commentId = $draft['comment_id'] ?? null;
        $backUrl   = $draft['back_url'] ?? route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id]);

        $caseUrl = route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id]);

        $commentBody = null;
        if ($commentId) {
            $c = CwebCaseComment::find($commentId);
            $commentBody = $c?->body;
        }

        // 送信先
        $toEmails = match ($type) {
            'registration', 'abolition' => $svc->defaultRecipientEmails($case),
            default => $svc->selectedRecipientEmails($toEmpNos),
        };

        // 送信先が0件なら戻す（誰にも飛ばせない）
        if (empty($toEmails)) {
            return redirect($backUrl)->with('ng', 'メール送信先（email）が見つかりませんでした。users.email を確認してください。');
        }

        $subject = $svc->subject($type, $case);
        $body    = $svc->body($type, $case, $caseUrl, $commentBody);

        $mailto  = $svc->buildMailto($toEmails, $subject, $body);

        return view('cweb.emails.open_mailto', [
            'mailto'  => $mailto,
            'backUrl' => $backUrl,
        ]);
    }
}
