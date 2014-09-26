<?php
require_once(dirname(__FILE__).'/class.encode_phpmailer.php');
require_once(dirname(__FILE__).'/class.mail_config.php');

/**
 * HtmlRender
 *
 * メールフォームを管理します。
 */
class HtmlRender {
	
	protected $RequestParam = NULL;       // ユーザが送信したフォームデータ(ユーザ送信データ)
	
	/**
	 * コンストラクタ
	 */
	public function __construct(&$requestParam) {
		$this->RequestParam = $requestParam;
	}
	
	public function text($name) {
	
	}
	
	public function textarea($name) {
	
	}
	
	public function selectOptions($name) {
	
	}
}

?>