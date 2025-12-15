<?php

namespace App\Http\Controllers;

use App\Models\CwebCase;
use App\Models\CwebCaseComment;
use Illuminate\Http\Request;

class CwebCaseCommentController extends Controller
{
    public function store(Request $request, string $locale, CwebCase $case)
    {
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],

            // ★ ポップアップE：選択送信先（社員番号配列）
            'val_mailsend'   => ['nullable', 'array'],
            'val_mailsend.*' => ['string', 'max:10'],
        ]);

        // コメント保存
        $comment = CwebCaseComment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'body'    => $validated['body'],
        ]);

        // ★ mailto起動画面へ（New Comment）
        return redirect()
            ->route('cweb.cases.mail_draft', ['locale' => $locale, 'case' => $case->id])
            ->with('mailDraft', [
                'type'       => 'comment',                 // 件名：New Comment...
                'to_empnos'   => $validated['val_mailsend'] ?? [], // 選択された送信先
                'comment_id'  => $comment->id,             // 本文にコメントを入れたい場合
                'back_url'    => route('cweb.cases.show', ['locale' => $locale, 'case' => $case->id]),
            ]);
    }
}
