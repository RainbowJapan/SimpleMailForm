<?php
require_once(dirname(__FILE__).'/interfece.mailer.php');

/**
 * SimpleMailer用のファクトリクラス
 *
 */
class SimplemailerFactory implements MailerFactoryInterface {
	public function getInstance() {
		return new SimpleMailer();
	}
}

/**
 * メールを送信するための簡易クラス
 *
 * 添付ファイルを送信したり、HTML形式のメールを送信したりなど、
 * 複雑なことはできません。
 */
class SimpleMailer implements MailerInterface {
	protected $ToEncoding = 'UTF8';
	protected $FromEncoding = 'UTF8';
	protected $Encoding = NULL;
	protected $From = NULL;
	protected $To = array();
	protected $Cc = array();
	protected $Bcc = array();
	protected $ReplyTo = NULL;
	protected $ReturnPath = NULL;
	protected $Subject = '';
	protected $Body = '';

	public function __construct() {
		$this->FromEncoding = mb_internal_encoding();
		$this->ToEncoding = mb_internal_encoding();
	}

	/**
	 * エンコード元の文字コードを設定する。
	 *
	 * @param string $value エンコード元の文字コード
	 */
	public function setFromEncoding($value) {
		$this->FromEncoding = $value;
	}

	/**
	 * 差出人を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setEncode($value) {
		$this->Encoding = $value;
	}
	
	/**
	 * 送信元を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setFrom($address, $name = '', $auto = true){
		if ($name){
			$name = $this->encode($name);
		}
		$this->From = array($address, $name);
	}

	/**
	 * 送信元を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addAddress($address,$name='') {
		if ($name){
			$name = $this->encode($name);
		}
		$this->To[] = array($address, $name);
	}
	
	/**
	 * CCを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addCc($address,$name='') {
		if ($name){
			$name = $this->encode($name);
		}
		$this->Cc[] = array($address, $name);
	}
	
	/**
	 * CCを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addBcc($address,$name='') {
		if ($name){
			$name = $this->encode($name);
		}
		$this->Bcc[] = array($address, $name);
	}

	/**
	 * Bccを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setReplyTo($address,$name='') {
		if ($name){
			$name = $this->encode($name);
		}
		$this->ReplyTo = array($address, $name);
	}

	/**
	 * ReturnPathを設定する。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPath($address, $name = ''){
		if ($name){
			$name = $this->encode($name);
		}
		$this->ReturnPath = array($address, $name);
	}

	/**
	 * CharSetを設定する。
	 * メールの内容を設定する前に、必ず設定してください。
	 *
	 * @param string $charset CharSet
	 */
	public function setCharSet($charset) {
		$this->ToEncoding = $charset;
	}

	/**
	 * サブジェクトを設定する。
	 *
	 * @param string $subject サブジェクト
	 */
	public function setSubject($subject){
		$this->Subject = $this->encode($subject);
	}

	/**
	 * 本文をセットする。
	 * HTML形式には対応していません。
	 *
	 * @param string $body 本文
	 * @param string $isHtml HTML有無(任意)
	 */
	public function setBody($body, $isHtml=false){
		$this->Body = $this->encode($body);
	}

	/**
	 * 代替え本文をセットする。(非対応)
	 *
	 * @param string $altbody 本文
	 */
	public function setAltBody($altbody){
		assert('unimplemented');
	}

	/**
	 * 添付ファイルを追加する。(非対応)
	 *
	 * @param string $path パス
	 */
	public function addAttachment($path){
		assert('unimplemented');
	}

	/**
	 * カスタムヘッダーを追加する。
	 * 非対応
	 *
	 * @param string $key キー名
	 * @param string $value 値
	 */
	public function addHeader($key,$value){
		assert('unimplemented');
	}

	/**
	 * カスタムでエンコードが必要な場合の関数
	 *
	 * @param string $value 値
	 */
	protected function encode($value){
		return mb_convert_encoding($value,$this->ToEncoding,$this->FromEncoding);
	}

	/**
	 * メールを送信する
	 *
	 */
	public function send() {
		if (!isset($this->To)) {
			return false;
		}
		if (!isset($this->From)) {
			return false;
		}

		$from = $this->makeAddressText($this->From[0], $this->From[1]);
		$to = $this->makeAddressTextFromAddresses($this->To);

		$reply = NULL;
		if (isset($this->ReplyTo))
			$reply = $this->makeAddressText($this->ReplyTo[0], $this->ReplyTo[1]);

		$cc = $this->makeAddressTextFromAddresses($this->Cc);
		$bcc = $this->makeAddressTextFromAddresses($this->Bcc);
		
		$headers = '';
//		ヘッダーで文字コード(iso-2022-jp)を指定するとiOSで文字化けする
//		指定しないでシステムに任せるのが良さそう
		if ($this->ToEncoding !== mb_internal_encoding()) {
			$headers.= "MIME-Version: 1.0\n";
			$headers.= "Content-Transfer-Encoding: $this->Encoding\n";
			$headers.= "Content-Type : text/plain;\n";
			$headers.= "\tcharset=\"$this->ToEncoding\";\n";
		}
		$headers.= "From: ${from}\r\n";
		if (!empty($reply)) $headers .= "Reply-To: ${reply}\n";
		if (!empty($cc)) $headers .= "Cc: ${cc}\n";
		if (!empty($bcc)) $headers .= "Bcc: ${bcc}\n";
//		$headers .= "X-Mailer : PHP/" . phpversion();
		$parameters = NULL;
		if (isset($this->ReturnPath)) {
			$additionalParameters="-f ".$this->makeAddressText($this->ReturnPath[0], $this->ReturnPath[1]);
		}
		
		$headers = $this->convLinefeedCode($headers);
		$body = $this->convLinefeedCode($this->Body);
		return $this->sendMail($to, $this->Subject, $body, $headers, $parameters);
	}
	
	/**
	 * 改行コードを統一する
	 */
	protected function convLinefeedCode($string, $to = "\n") {
		return preg_replace("/\r\n|\r|\n/", $to, $string);
	}
	
	/**
	 * メールを送信ラッパー関数
	 */
	protected function sendMail($to, $subject, $body, $headers, $parameters) {
		return mb_send_mail($to, $subject, $body, $headers, $parameters);
	}
	
	/**
	 * メールアドレスのリストテキストを返す。
	 *
	 * @param array $addresses メールアドレスのリスト
	 * @return string メールアドレスのリストテキスト(例：sample@example.com <sample name>, sample1@example.com <sample1 name>)
	 */
	protected function makeAddressTextFromAddresses($addresses) {
		$text = "";
		foreach ($addresses as $value) {
			if (strlen($text) > 0) $text.= ",";
			$text .= $this->makeAddressText($value[0], $value[1]);
		}
		return $text;
	}

	/**
	 * メールアドレステキストを返す。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 * @return string メールアドレスのテキスト(例：sample@example.com <sample name>)
	 */
	protected function makeAddressText($address, $name) {
		if ($name != NULL && strlen($name) > 0) {
			return "$name <$address>";
		} else {
			return $address;
		}
	}
}