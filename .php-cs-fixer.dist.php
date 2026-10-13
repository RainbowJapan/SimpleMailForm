<?php

// ⚠️ PHP 5.5互換

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
->in(__DIR__)
->exclude([
'external',  // 今後は使わないPHPMailerのフォルダは整形の対象外にして隔離する
'vendor56',  // 他バージョンのベンダーフォルダを除外
]);

return (new Config())
->setRiskyAllowed(false) 
->setRules([
// PHP 5.5環境でも完全に安全な、見た目だけを整える標準ルール（@PSR2）を採用
'@PSR2' => true,

// array() を [] にする短縮構文。これはPHP 5.4から対応しているので5.5でも安全かつ綺麗になります
'array_syntax' => ['syntax' => 'short'], 

// コード内の余分な空白（スペース）を削除
'no_extra_blank_lines' => true,

// 行末の無駄なスペースを自動削除
'no_trailing_whitespace' => true,
])
// 💡 ここでインデントを「タブ」に変更し、スペースによるインデントを強制的に書き換えます
->setIndent("\t")
->setFinder($finder);
