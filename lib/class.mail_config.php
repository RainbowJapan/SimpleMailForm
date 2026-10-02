<?php

/**
 * MailConfig
 *
 * メール送信時の設定を保管するクラス
 */
class MailConfig
{
	public $ToAddresses = [];     // 宛先
	public $FromAddress = [];     // 差出人
	public $ReplyTo = [];         // ReplyTo
	public $ReturnPath = [];     // ReturnPath
	public $MailBodyTemplate = '';    // メールのテンプレート
	public $MailSubjectTemplate = ''; // サブジェクトのテンプレート
	public $AllowTempFiles = true; // 添付ファイルがある場合、添付するかどうか
}
