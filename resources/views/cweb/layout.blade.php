<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>C-WEB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css">

    <style>
        :root{
            --bg:#f8fafc;
            --card:#ffffff;
            --text:#0f172a;
            --muted:#64748b;
            --primary:#2563eb;
        }
        @media (prefers-color-scheme: dark){
            :root{
                --bg:#020617;
                --card:#020617;
                --text:#e5e7eb;
                --muted:#9ca3b1;
                --primary:#3b82f6;
            }
        }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* 🔹 各ページで使う共通ヘッダー用クラス（中身はページごとに変える） */
        .cweb-header {
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
            background: #130d37;
        }
        .cweb-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #fff;
        }
        .cweb-header-left {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .cweb-header-right {
    display:flex;
    align-items:center;
    gap:12px;
    color:#e5e7eb;  /* 右側の文字色を少し薄めに */
}

/* 日本語 / EN ブロック */
.cweb-header-lang {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-left: 8px;
    padding-left: 12px;  /* 左に縦線の余白 */
}

/* 言語の左にヘッダーの横棒を縦にズバッと入れる */
.cweb-header-lang::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;     /* 上下にはみ出させて「がっつり」見せる */
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* 「日本語 / EN」ボタン本体 */
.cweb-header-lang-toggle {
    border: none;
    background: transparent;
    color: inherit;           /* 白系をそのまま継承 */
    font-size: 12px;
    cursor: pointer;
    padding: 0 6px;
    line-height: 1.4;
    opacity: 0.75;            /* 通常は少し薄く */
    transition:
        opacity .15s ease,
        background-color .15s ease,
        transform .04s ease;
}

/* ユーザー名ボタン */
.cweb-header-user-toggle {
    position: relative;
    margin-left: 8px;
    padding-left: 12px;       /* 左に縦線ぶん余白 */

    border: none;
    background: transparent;
    color: inherit;
    font-size: 12px;
    cursor: pointer;
    line-height: 1.4;
    opacity: 0.75;
    transition:
        opacity .15s ease,
        background-color .15s ease,
        transform .04s ease;
}

/* ユーザー名の左側にも縦線を入れる */
.cweb-header-user-toggle::before {
    content: "";
    position: absolute;
    left: 0;
    top: -6px;
    bottom: -6px;
    width: 1px;
    background: rgba(148, 163, 184, 0.6);
}

/* ホバーしたら不透明＋背景ちょい色付き（反射ではなく色が濃くなるイメージ） */
.cweb-header-lang-toggle:hover,
.cweb-header-user-toggle:hover {
    opacity: 1;
    background-color: rgba(255, 255, 255, 0.06);
}

/* クリック時、少しだけ縮む */
.cweb-header-lang-toggle:active,
.cweb-header-user-toggle:active {
    transform: scale(0.97);
}

/* ダークモード時（縦線の色を少し調整） */
@media (prefers-color-scheme: dark) {
    .cweb-header-right {
        color:#e5e7eb;
    }
    .cweb-header-lang::before,
    .cweb-header-user-toggle::before {
        background: rgba(75, 85, 99, 0.8);
    }
}


        .cweb-brand-link {
            color:#ffffff;
            text-decoration:none;
            font-size:18px;
            font-weight:700;
        }

        .btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:6px 12px;
            border-radius:8px;
            border:1px solid transparent;
            font-size:13px;
            font-weight:600;
            cursor:pointer;
            text-decoration:none;
            background:var(--card);
            color:var(--text);
            transition: background-color .15s ease, transform .08s ease, box-shadow .08s ease, opacity .08s ease;
        }

        .btn:hover {
            box-shadow:0 2px 5px rgba(0,0,0,.25);
            opacity:0.96;
        }

        .btn:active {
            transform:translateY(1px);
            box-shadow:0 0 0 rgba(0,0,0,0);
            opacity:0.9;
        }

        .btn-accent {
            background:#d97721;
            color:#ffffff;
            border-color:#b45309;
        }
        .btn-qweb {
            background:#0070c0;
            color:#fff;
        }
        .btn-qweb:hover {
            background:#005EA8;
        }
        .btn-qweb:active {
            background:#004A84;
        }

        /* 本文：共通コンテナ */
        .cweb-main {
            width: 100%;
            margin: 16px 0 24px;
            padding: 0 4%;
            box-sizing: border-box; 
        }

        @media (max-width: 768px) {
            .cweb-header-inner {
                padding: 8px 10px;
            }
            .cweb-main {
                padding: 0 8px;
            }
        }

        /* ⑤ 検索ボックス用：青い円＋白い虫眼鏡 */
/* 検索ボックス用：水色の円＋中に白い虫眼鏡（棒は貫通しない） */
.search-icon-main {
    display:inline-block;
    width:18px;
    height:18px;
    border-radius:999px;
    background:#38bdf8;   /* ← 水色 */
    position:relative;
}
.search-icon-main::before {
    content:'';
    position:absolute;
    width:9px;
    height:9px;
    border-radius:999px;
    border:2px solid #ffffff;
    top:3px;
    left:3px;
}
.search-icon-main::after {
    content:'';
    position:absolute;
    width:7px;   /* ← 棒少し長め */
    height:2px;
    background:#ffffff;
    border-radius:999px;
    transform:rotate(45deg);
    right:4px;   /* ← 円の外には出さない位置 */
    bottom:4px;
}


/* ⑤ フィルタ用：グレーの円＋グレーの棒 */
/* テーブルヘッダ用：小さい濃いグレーの虫眼鏡 */
.filter-icon {
    display:inline-block;
    width:10px;       /* 円を小さく */
    height:10px;
    border-radius:999px;
    border:2px solid #4b5563;  /* 濃いグレー */
    position:relative;
    margin-left:4px;
}
.filter-icon::after {
    content:'';
    position:absolute;
    width:7px;       /* 棒を長め */
    height:2px;
    background:#4b5563;
    border-radius:999px;
    transform:rotate(45deg);
    right:-1px;
    bottom:1px;
    /* 円の外まではみ出さない程度の位置に調整 */
}

/* タブ用 */
.cweb-tabs {
    margin-top: 24px;
    margin-bottom: 4px;
    display: flex;
    gap: 16px;
}

.cweb-tab-link {
    position: relative;
    padding: 4px 0;
    font-weight: 600;
    text-decoration: none;
    color: #64748b;
}

.cweb-tab-link::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -4px;
    height: 2px;
    background: transparent;
    border-radius: 999px;
}

/* ホバー時：色だけ濃く＋薄い水色の下線 */
.cweb-tab-link:hover {
    color: #0f172a;
}
.cweb-tab-link:hover::after {
    background: #bae6fd;
}

/* アクティブタブ：文字色＋水色下線 */
.cweb-tab-link.is-active {
    color: #0f172a;
}
.cweb-tab-link.is-active::after {
    background: #38bdf8;
}

input::placeholder,
textarea::placeholder {
    color:#9ca3af;
}

    </style>
</head>
<body>

    {{-- 🔹 ページごとのヘッダーをここに差し込む --}}
    @yield('header')

    {{-- 🔹 本文 --}}
    <main class="cweb-main">
        @yield('content')
            <div id="success-modal-overlay" class="ui dimmer" style="display:none;"></div>

    <div id="success-modal" class="ui small modal" style="display:block; opacity:0; pointer-events:none;">
        <div class="header">完了</div>
        <div class="content" style="text-align:center; font-size:16px; padding:20px;">
            登録しました
        </div>
        <div class="actions" style="text-align:center;">
            <button type="button" class="ui blue button" onclick="closeSuccessModal()">OK</button>
        </div>
    </div>

    <!-- <script>
        function showSuccessModal() {
            const overlay = document.getElementById('success-modal-overlay');
            const modal   = document.getElementById('success-modal');

            if (!overlay || !modal) return;

            overlay.classList.add('visible');
            modal.classList.add('visible');

            setTimeout(() => {
                closeSuccessModal();
            }, 2000);
        }

        function closeSuccessModal() {
            const overlay = document.getElementById('success-modal-overlay');
            const modal   = document.getElementById('success-modal');

            if (!overlay || !modal) return;

            overlay.classList.remove('visible');
            modal.classList.remove('visible');
        }
    </script> -->
    </main>

</body>
</html>
