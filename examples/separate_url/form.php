<?php
mb_language('japanese');
mb_internal_encoding('UTF-8');

require_once(dirname(__FILE__).'/../../lib/class.mailform.php');

$mailform = new MailForm();

// 内部でテンプレートを読み込まない
$mailform->IncludeTemplateURL = false;
// メールフォームの各ページのURLを設定
$base_path = dirname($_SERVER["REQUEST_URI"]).'/';
$mailform->InputURL = $base_path.'input.php';
$mailform->ConfirmURL = $base_path.'confirm.php';
$mailform->CompleteURL = $base_path.'complete.php';
$mailform->ErrorURL = $base_path.'index.php';


// フォームの設定を読み込む
$mailform->loadFormConfig(dirname(__FILE__)."/conf/mailform_config.yml", dirname(__FILE__)."/conf");

// データを設定
$mailform->setRequestParameter($_POST);
// 実行
$mailform->execute();
?>
