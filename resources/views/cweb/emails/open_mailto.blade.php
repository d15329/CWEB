@extends('cweb.layout')

@section('content')
<div style="max-width:720px;margin:24px auto;padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
    <div style="font-weight:700;font-size:16px;margin-bottom:8px;">
        メールを起動します
    </div>

    <div style="font-size:13px;color:#6b7280;margin-bottom:12px;">
        もし自動で開かない場合は、下のボタンを押してください。
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ $mailto }}"
           style="display:inline-block;padding:10px 14px;border-radius:999px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700;">
            メールを開く
        </a>

        <a href="{{ $backUrl }}"
           style="display:inline-block;padding:10px 14px;border-radius:999px;background:#e5e7eb;color:#111827;text-decoration:none;font-weight:700;">
            案件へ戻る
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // mailto起動 → 少し待って戻る（戻しは好みで）
    window.location.href = @json($mailto);
    setTimeout(function(){
        window.location.href = @json($backUrl);
    }, 1200);
});
</script>
@endsection
