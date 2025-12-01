@extends('cweb.layout')

@section('content')
@if(session('ok'))
    <div style="margin-bottom:8px;color:#16a34a">{{ session('ok') }}</div>
@endif

<header style="background:#130d37;color:#fff;border-radius:8px 8px 0 0;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="background:#fff;color:#000;padding:4px 8px;border-radius:4px;font-weight:700;">
                {{ $case->status === 'active' ? 'アクティブ' : '廃止' }}
            </div>
            <div style="font-weight:700;">
                {{ $case->management_no }}
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <a href="http://qweb.discojpn.local/" class="btn btn-qweb">Q-WEB</a>
            <span>日本語 / EN</span>
            <span>{{ auth()->user()->name }}</span>
        </div>
    </div>
</header>

<div style="display:flex;justify-content:space-between;margin-bottom:12px;gap:8px;">
    <div>
        <button type="button" class="btn btn-accent">編集</button>
        <button type="button" class="btn btn-accent"
                style="margin-left:8px;background:#dc2626;"
                {{-- TODO: ポップアップDで廃止確認 --}}
        >廃止</button>
    </div>
    <div>
        <button type="button" class="btn"
                style="background:#22c55e;color:#fff;">
            フォルダ
        </button>
        {{-- TODO: クリックで $case->folder_path をクリップボードコピー --}}
    </div>
</div>

{{-- 新規登録ページの表示版（今は簡易） --}}
<div style="background:var(--card);border-radius:8px;padding:12px 14px;margin-bottom:16px;">
    <div style="margin-bottom:6px;font-size:14px;">
        顧客名：{{ $case->customer_name }}
    </div>
    <div style="margin-bottom:6px;font-size:14px;">
        カテゴリー：
        @php
            $cats = [];
            if($case->category_standard) $cats[] = '標準管理';
            if($case->category_pcn)      $cats[] = 'PCN';
            if($case->category_other)    $cats[] = 'その他要求';
        @endphp
        {{ $cats ? implode(' / ', $cats) : '-' }}
    </div>
    <div style="margin-bottom:6px;font-size:14px;">
        対象製品：{{ $case->product_group }} {{ $case->product_code }}
    </div>
    <div style="font-size:13px;color:var(--muted);">
        登録者：{{ optional($case->creator)->name }}
    </div>
</div>

{{-- コメント欄 --}}
<div style="margin-bottom:12px;">
    <form method="POST" action="{{ route('cweb.cases.comments.store',$case) }}">
        @csrf
        <textarea name="body" rows="3"
                  placeholder="Add a comments..."
                  style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid var(--border);resize:vertical;"></textarea>
        @error('body')
            <div style="color:#f97316;">{{ $message }}</div>
        @enderror

        <div style="display:flex;justify-content:flex-end;margin-top:6px;">
            <button type="submit" class="btn btn-qweb">
                投稿
            </button>
        </div>
    </form>
</div>

<div>
    @forelse($case->comments as $comment)
        <div style="display:flex;gap:8px;margin-bottom:10px;">
            <div style="width:32px;height:32px;border-radius:999px;background:#111827;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;">
                {{-- アイコン代わりに頭文字 --}}
                {{ mb_substr($comment->user->name ?? 'U',0,1) }}
            </div>
            <div style="flex:1;">
                <div style="font-size:13px;margin-bottom:2px;">
                    <strong>{{ $comment->user->name ?? 'Unknown' }}</strong>
                    <span style="color:var(--muted);font-size:11px;margin-left:6px;">
                        {{ $comment->created_at->format('Y-m-d H:i') }}
                    </span>
                </div>
                <div style="font-size:13px;">
                    {{ $comment->body }}
                </div>
            </div>
        </div>
    @empty
        <div style="color:var(--muted);font-size:13px;">コメントはまだありません。</div>
    @endforelse
</div>
@endsection
