<?php

namespace App\Http\Controllers;

use App\Models\CwebCase;
use App\Models\CwebCaseComment;
use Illuminate\Http\Request;

class CwebCaseCommentController extends Controller
{
    public function store(Request $request, CwebCase $case)
    {
        $user = $request->user();

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            // （あとでポップアップEの送信先も追加できる）
        ]);

        // コメント保存
        CwebCaseComment::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'body'    => $data['body'],
        ]);

        // （必要ならメール通知ここで呼ぶ）

        return back()->with('ok', 'コメントを投稿しました。');
    }
}
