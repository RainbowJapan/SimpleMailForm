<?php

/**
 * MailConfig
 *
 * メール送信時の設定を保管するクラスです。
 */
class MailConfig {
	public $ToAdresses = array();      // 宛先
	public $FromAdress = array();      // 差出人
	public $ReturnPath = array();      // ReturnPath
	public $MailBodyTemplate = '';     // メールのテンプレート
	public $MailSubjectTemplate = '';  // 件名のテンプレート
}
?>