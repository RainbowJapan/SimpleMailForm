<?php
require __DIR__ . '/class.request_checker.php';

// アクセスチェック
// 不正な場合、404ページに飛ぶ
$requestchecker = new RequestChecker();
if (!$requestchecker->check($_POST)) {
	header("Location: /404/");
	exit();
}
