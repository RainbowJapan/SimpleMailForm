<?php
mb_language('japanese');
mb_internal_encoding('UTF-8');

require_once(dirname(__FILE__).'/../../lib/class.mailform.php');
require_once(dirname(__FILE__).'/../../lib/class.phpmailer.php');

$mailform = new MailForm();

// メールフォームのテンプレートファイルのパスを設定
$base_path = dirname(__FILE__).'/';
$mailform->InputURL = $base_path.'input.php';
$mailform->ConfirmURL = $base_path.'confirm.php';
$mailform->CompleteURL = $base_path.'complete.php';
$mailform->ErrorURL = $base_path.'input.php';

// フォームの設定を読み込む
$mailform->setMailerFactory(new EncodePHPMailerFactory());
$mailform->loadFormConfig(dirname(__FILE__)."/conf/mailform_config.yml", dirname(__FILE__)."/conf");
$mailform->TmpDirectryPath = dirname(__FILE__)."/var/tmp/"; // 添付ファイルの一時置き場

// データを設定
$mailform->setRequestParameter($_POST);
// 実行
$mailform->execute();
?>
