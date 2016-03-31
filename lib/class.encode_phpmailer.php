<?php
require(dirname(__FILE__).'/../external/PHPMailer/class.phpmailer.php');

/**
 * EncodePHPMailer
 * 
 * PHPMailerを継承した文字コード変換用のクラスです。
 */
class EncodePHPMailer extends PHPMailer {
	public $ToEncoding = 'UTF8';   // 変換元のエンコード
	public $FromEncoding = 'UTF8'; // 変換後のエンコード
	
	public function __construct($exceptions = false) {
		parent::__construct($exceptions);
		$this->FromEncoding = mb_internal_encoding();
		$this->ToEncoding = 'UTF8';
	}
	
	public function setFrom($address, $name = '', $auto = true){
		if ($name){
			$name = $this->encodeForMime($this->convertEncoding($name));
		}
		parent::setFrom($address,$name,$auto);
	}
	
	public function addAddress($address,$name='') {
		if ($name){
			$name = $this->encodeForMime($this->convertEncoding($name));
		}
		parent::addAddress($address,$name);
	}
	
	public function addCc($address,$name='') {
		if ($name){
			$name = $this->encodeForMime($this->convertEncoding($name));
		}
		parent::addCc($address,$name);
	}

	public function addBcc($address,$name='') {
		if ($name){
			$name = $this->encodeForMime($this->convertEncoding($name));
		}
		parent::addBcc($address,$name);
	}

	public function addReplyTo($address,$name='') {
		if ($name){
			$name = $this->encodeForMime($this->convertEncoding($name));
		}
		parent::addReplyTo($address,$name);
	}
	
	/**
	 * ReturnPathを設定します。
	 * 
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPath($address, $name = ''){
		if ($name){
			$name = $this->encodeForMime($this->convertEncoding($name));
		}
		$this->Sender = $name.'<'.$address.'>';
	}
	
	/**
	 * CharSetを設定します。
	 * メールの内容を設定する前に、必ず設定してください。
	 * 
	 * @param string $charset CharSet
	 */
	public function setCharSet($charset) {
		$this->ToEncoding = $charset;
		$this->CharSet = $charset;
	}
	
	/**
	 * サブジェクトを設定します。
	 * 
	 * @param string $subject サブジェクト
	 */
	public function setSubject($subject){
		$this->Subject = $this->encodeForMime($this->convertEncoding($subject));
	}

	/**
	 * 本文をセットします。
	 * 
	 * @param string $body 本文
	 * @param string $isHtml HTML有無(任意)
	 */
	public function setBody($body, $isHtml=false){
		$this->Body = $this->convertEncoding($body);
		$this->IsHtml(false);
	}

	/**
	 * 代替え本文をセットします。
	 * 
	 * @param string $altbody 本文
	 */
	public function setAltBody($altbody){
		$this->AltBody = $this->convertEncoding($altbody);
	}
	
	/**
	 * カスタムヘッダーを追加します。
	 * 
	 * @param string $key キー名
	 * @param string $value 値
	 */
	public function addHeader($key,$value){
		if (!$value){
			return;
		}
		$this->addCustomHeader($key.':'.$this->encodeForMime($this->convertEncoding($name)));
	}
	
	/**
	 * Mimeエンコードをします。
	 * 
	 * @param string $value 値
	 * @return string エンコードの文字列
	 */
	protected function encodeForMime($value){
	 
		return mb_encode_mimeheader($value, $this->ToEncoding);
	}
	
	/**
	 * 文字コードを変換します。
	 * 
	 * @param string $value 値
	 * @return string エンコードの文字列
	 */
	protected function convertEncoding($value){
	 
		return mb_convert_encoding($value,$this->ToEncoding,$this->FromEncoding);
	}
}
