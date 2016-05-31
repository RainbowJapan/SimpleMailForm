<?php

/**
 * MailConfig
 *
 * メール送信時の設定を保管するクラス
 */
class MailConfig {
	public $ToAdresses = array();     // 宛先
	public $FromAdress = array();     // 差出人
	public $ReturnPath = array();     // ReturnPath
	public $MailBodyTemplate = '';    // メールのテンプレート
	public $MailSubjectTemplate = ''; // サブジェクトのテンプレート
	public $ArrowTempFiles = true; // 添付ファイルがある場合、添付するかどうか
}
?>