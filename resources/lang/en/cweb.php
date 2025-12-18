<?php

return [
  'actions' => [
    'create'   => 'Create',
    'search'   => 'Search',
    'open_category_guide' => 'Category guide / Admin fee',

    'register' => 'Register',
    'select'   => 'Select',

    'edit'   => 'Edit',
    'abolish'=> 'Abolish',
    'folder' => 'Folder',
    'post'   => 'Post',

    // edit / common
    'update'  => 'Update',
    'add_row' => '+ Add row',
    'remove'  => 'Remove',
  ],

  'labels' => [
    'in_progress' => 'Editing',
    'editing'     => 'Editing',
  ],

  'form' => [
    'select'        => 'Select',
    'choose'        => 'Select',
    'sales_contact' => 'Sales contact',
    'shared_users'  => 'Shared users',
    'shared_note'   => 'Technical/manufacturing members will be shared automatically by product.',
    'cost_owner'    => 'Cost owner',
    'customer'      => 'Customer',
    'category'      => 'Category',
    'product'       => 'Product',

    'pcn_title_placeholder' => 'Label change, etc.',
    'other_content_placeholder' => 'Requirement',
  ],

  'modal' => [
    'done_title' => 'Done',
    'saved'      => 'Saved',
    'updated'    => 'Updated.',
  ],

  'common' => [
    'ok' => 'OK',
    'cancel' => 'Cancel',
  ],

  'tabs' => [
    'all' => 'All',
    'mine' => 'Cases you’re involved in',
    'product' => 'Requirements by product',
  ],

  'search' => [
    'placeholder' => 'Search…',
    'keyword_placeholder' => 'keyword...',
  ],

  'table' => [
    'management_no' => 'Management No.',
    'status' => 'Status',
    'category' => 'Category',
    'product' => 'Product',
    'customer' => 'Customer',
    'sales_contact' => 'Sales contact',
    'monthly_cost' => 'Monthly cost',
  ],

  'filter' => [
    'title' => 'Filter',
    'all' => '(All)',
    'apply' => 'Apply',
    'clear' => 'Clear',
    'product_group' => 'Product',
    'product_code' => 'Product code (optional)',
  ],

  'categories' => [
    'standard' => 'Standard',
    'pcn' => 'PCN',
    'other' => 'Other requests',
  ],

  'status' => [
    'active' => 'Active',
    'closed' => 'Closed',
    'unknown' => 'Unknown',
  ],

  'empty' => [
    'no_cases' => 'No cases yet.',
  ],

  'show' => [
    'pcn_items'        => 'PCN items',
    'other_requests'   => 'Other requests',
    'responsible'      => 'Responsible',
    'will'             => 'Will',
    'will_initial'     => 'Initial fee',
    'will_monthly'     => 'Monthly fee',
    'will_allocations' => 'Monthly allocation',
    'related_qweb'     => 'Related Q-WEB',
  ],

  'pcn' => [
    'months_before_suffix' => ' months before',
    'categories' => [
      'spec' => 'Specification',
      'man' => 'Man',
      'machine' => 'Machine',
      'material' => 'Material',
      'method' => 'Method',
      'measurement' => 'Measurement',
      'environment' => 'Environment',
      'other' => 'Other',
      'uncategorized' => 'Uncategorized',
    ],
  ],

  'comments' => [
    'placeholder'    => 'Add a comment...',
    'send_to_title'  => 'Select recipients',
    'no_candidates'  => 'No recipient candidates.',
  ],

  'abolish' => [
    'title' => 'Abolish this case?',
    'placeholder' => 'Comment...',
    'note' => 'Please describe who approved it and when.',
    'alert_enter_comment' => 'Please enter a comment.',
  ],

  'clipboard' => [
    'folder_not_found' => 'Folder does not exist.',
  ],

  'emp_modal' => [
    'search_result' => 'Search Result',
    'selected'      => 'Selected',
    'title_sales'   => 'Select a sales contact (double-click to add/remove)',
    'title_shared'  => 'Select people to share with (double-click to add/remove)',
    'title_cost'    => 'Select cost owner (double-click to add/remove)',
    'title_other'   => 'Select a person in charge (double-click to add/remove)',
    'title_will'    => 'Select allocation owner (double-click to add/remove)',
  ],
    'product' => [
    'select_group' => 'Select product',
    'all_in_group' => '(All)',

    'help_select_to_show' => 'Select a product to view contract counts and PCN targets.',
    'coming_soon' => 'Coming soon',

    'cards' => [
      'contract_count' => 'Contract count',
      'other_requests' => 'Other requests',
      'pcn_targets'    => 'PCN targets',
    ],

    'contract_breakdown' => [
      'standard' => 'Standard',
      'pcn'      => 'PCN',
      'other'    => 'Other requests',
      'suffix'   => '', // 例: "3" の後ろに何も付けない
    ],

    'pcn' => [
      'max_notice' => 'Max notice',
      'customer'   => 'Customer',
      'manage_no'  => 'Management No.',
      'notice'     => 'Notice',
      'months_suffix' => ' months before',
      'no_cases'   => 'No matching cases.',
    ],
  ],

];
