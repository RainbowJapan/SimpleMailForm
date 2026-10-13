<?php
mb_language('japanese');
mb_internal_encoding('UTF-8');

require_once(dirname(__FILE__).'/../../lib/class.mailform.php');

$mailform = new MailForm();

// 内部でテンプレートを読み込まない
$mailform->IncludeTemplateURL = false;
// メールフォームの各ページのURLを設定
$request_uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
$base_path = parse_url($request_uri, PHP_URL_PATH);
if (pathinfo($base_path, PATHINFO_EXTENSION)) {
	$base_path = dirname($base_path);
}
$base_path = rtrim($base_path, '/') . '/';
$mailform->InputURL = $base_path.'index.php';
$mailform->ConfirmURL = $base_path.'confirm.php';
$mailform->CompleteURL = $base_path.'complete.php';
$mailform->ErrorURL = $base_path.'index.php?type=error';

// フォームの設定を読み込む
$mailform->loadFormConfig(dirname(__FILE__)."/conf/mailform_config.json", dirname(__FILE__)."/conf", MailForm::MAILFORM_FILE_TYPE_JSON);

// データを設定
$mailform->setRequestParameter($_POST);
// 実行
$mailform->execute();
