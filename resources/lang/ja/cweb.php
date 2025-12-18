<?php

return [
  'actions' => [
    'create'   => '新規登録',
    'search'   => '検索',
    'open_category_guide' => 'カテゴリーの定義及び管理費紹介',

    'register' => '新規登録',
    'select'   => '選択',

    'edit'   => '編集',
    'abolish'=> '廃止',
    'folder' => 'フォルダ',
    'post'   => '投稿',

    // edit / 共通で使いがち
    'update'  => '更新',
    'add_row' => '＋ 行追加',
    'remove'  => '削除',
  ],

  'labels' => [
    'in_progress' => '登録中',
    'editing'     => '編集中',
  ],

  'form' => [
    'select'        => '選択',
    'choose'        => '選択', // option の placeholder にも使える
    'sales_contact' => '営業窓口',
    'shared_users'  => '情報共有者',
    'shared_note'   => '各製品の技術/製造担当は自動で情報共有されます',
    'cost_owner'    => '費用負担先',
    'customer'      => '顧客名',
    'category'      => 'カテゴリー',
    'product'       => '対象製品',

    // edit 画面で出てくるやつ
    'pcn_title_placeholder' => 'ラベル変更など',
    'other_content_placeholder' => '要求内容',
  ],

  'modal' => [
    'done_title' => '完了',
    'saved'      => '登録しました',
    'updated'    => '更新しました',
  ],

  'common' => [
    'ok' => 'OK',
    'cancel' => 'キャンセル',
  ],

  'tabs' => [
    'all' => 'すべて',
    'mine' => 'あなたが関わる案件',
    'product' => '製品ごとの要求内容一覧',
  ],

  'search' => [
    'placeholder' => '検索…',
    'keyword_placeholder' => 'keyword...',
  ],

  'table' => [
    'management_no' => '管理番号',
    'status' => 'ステータス',
    'category' => 'カテゴリー',
    'product' => '対象製品',
    'customer' => '顧客名',
    'sales_contact' => '営業窓口',
    'monthly_cost' => '月額費用',
  ],

  'filter' => [
    'title' => '絞り込み条件',
    'all' => '（すべて）',
    'apply' => '絞り込み',
    'clear' => '解除',
    'product_group' => '対象製品',
    'product_code' => '詳細カテゴリ ※任意',
  ],

  'categories' => [
    'standard' => '標準管理',
    'pcn' => 'PCN',
    'other' => 'その他要求',
  ],

  'status' => [
    'active' => 'アクティブ',
    'closed' => '廃止',
    'unknown' => '不明',
  ],

  'empty' => [
    'no_cases' => 'まだ案件がありません。',
  ],

  'show' => [
    'pcn_items'        => 'PCN管理項目',
    'other_requests'   => 'その他要求',
    'responsible'      => '対応者',
    'will'             => 'Will',
    'will_initial'     => '登録費',
    'will_monthly'     => '月額',
    'will_allocations' => '月額管理費の分配',
    'related_qweb'     => '関連Q-WEB',
  ],

  'pcn' => [
    'months_before_suffix' => 'ヵ月前連絡',
    'categories' => [
      'spec' => '仕様書内容',
      'man' => '人（Man）',
      'machine' => '機械（Machine）',
      'material' => '材料（Material）',
      'method' => '方法（Method）',
      'measurement' => '測定（Measurement）',
      'environment' => '環境（Environment）',
      'other' => 'その他',
      'uncategorized' => '未分類',
    ],
  ],

  'comments' => [
    'placeholder'    => 'コメントを入力...',
    'send_to_title'  => '送信先を選択してください',
    'no_candidates'  => '送信先候補がありません。',
  ],

  'abolish' => [
    'title' => '廃止にしますか？',
    'placeholder' => 'コメント...',
    'note' => '担当営業の誰からいつ合意を得たかを記載してください',
    'alert_enter_comment' => 'コメントを入力してください。',
  ],

  'clipboard' => [
    'folder_not_found' => 'フォルダが存在しません。',
  ],

  // 社員検索モーダル（edit画面で使ってる文言）
  'emp_modal' => [
    'search_result' => 'SearchResult',
    'selected'      => 'Selected',
    'title_sales'   => '営業窓口としたい人を選択（ダブルクリックで追加/削除）',
    'title_shared'  => '共有したい人を選択（ダブルクリックで追加/削除）',
    'title_cost'    => '費用負担先を選択（ダブルクリックで追加/削除）',
    'title_other'   => 'その他要求の対応者を選択（ダブルクリックで追加/削除）',
    'title_will'    => '月額管理費の分配担当者を選択（ダブルクリックで追加/削除）',
  ],
    'product' => [
    'select_group' => '製品選択',
    'all_in_group' => '（すべて）',

    'help_select_to_show' => '製品を選択すると、契約登録数および PCN 管理対象の情報が表示されます',
    'coming_soon' => 'Coming soon',

    'cards' => [
      'contract_count' => '契約登録数',
      'other_requests' => 'その他要求',
      'pcn_targets'    => 'PCN管理対象',
    ],

    'contract_breakdown' => [
      'standard' => '標準管理',
      'pcn'      => 'PCN',
      'other'    => 'その他要求',
      'suffix'   => '件',
    ],

    'pcn' => [
      'max_notice' => '最長通知期間',
      'customer'   => '顧客',
      'manage_no'  => '管理番号',
      'notice'     => '通知期間',
      'months_suffix' => 'か月前',
      'no_cases'   => '該当案件はありません。',
    ],
  ],

];
