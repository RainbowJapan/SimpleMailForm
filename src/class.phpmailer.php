<?php
require_once(dirname(__FILE__).'/interfece.mailer.php');
require(dirname(__FILE__).'/../external/phpmailer/class.phpmailer.php');

/**
 * EncodePHPMailer用のファクトリクラス
 *
 */
class EncodePHPMailerFactory implements MailerFactoryInterface {
	public function getInstance() {
		return new EncodePHPMailer();
	}
}

/**
 * EncodePHPMailer
 *
 * PHPMailerのラッパー主に文字コード変換用
 * コメントが無い関数は、overwriteした関数
 */
class EncodePHPMailer extends PHPMailer implements MailerInterface {
	public $ToEncoding = 'UTF8';   // 変換元のエンコード
	public $FromEncoding = 'UTF8'; // 変換後のエンコード

	public function __construct($exceptions = false) {
		parent::__construct($exceptions);
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
	 * 差出人を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setFrom($address, $name = '', $auto = true){
		if ($name){
			$name = $this->encode($name);
		}
		parent::setFrom($address,$name,$auto);
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
		parent::addAddress($address,$name);
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
		parent::addCc($address,$name);
	}
	
	/**
	 * Bccを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addBcc($address,$name='') {
		if ($name){
			$name = $this->encode($name);
		}
		parent::addBcc($address,$name);
	}
	
	/**
	 * ReplyToを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setReplyTo($address,$name='') {
		if ($name){
			$name = $this->encode($name);
		}
		parent::setReplyTo($address,$name);
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
		$this->Sender = $name.'<'.$address.'>';
	}

	/**
	 * CharSetを設定する。
	 * メールの内容を設定する前に、必ず設定してください。
	 *
	 * @param string $charset CharSet
	 */
	public function setCharSet($charset) {
		$this->ToEncoding = $charset;
		$this->CharSet = $charset;
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
	 *
	 * @param string $body 本文
	 * @param string $isHtml HTML有無(任意)
	 */
	public function setBody($body, $isHtml=false){
		$this->Body = $this->encode($body);
		$this->IsHtml($isHtml);
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
	 * 代替え本文をセットする。
	 *
	 * @param string $altbody 本文
	 */
	public function setAltBody($altbody){
		$this->AltBody = mb_convert_encoding($altbody,$this->ToEncoding,$this->FromEncoding);
	}

	/**
	 * カスタムヘッダーを追加する。
	 *
	 * @param string $key キー名
	 * @param string $value 値
	 */
	public function addHeader($key,$value){
		if (!$value){
			return;
		}
		$this->addCustomHeader($key.':'.$this->encodeForMime(mb_convert_encoding($value,$this->ToEncoding,$this->FromEncoding)));
	}

	/**
	 * カスタムでエンコードが必要な場合の関数
	 *
	 * @param string $value 値
	 */
	protected function encodeForMime($value){
		return $value;
	}


	protected function attachAll($disposition_type, $boundary)
	{
		// Return text of body
		$mime = array();
		$cidUniq = array();
		$incl = array();

		// Add all attachments
		foreach ($this->attachment as $attachment) {
			// Check if it is a valid disposition_filter
			if ($attachment[6] == $disposition_type) {
				// Check for string attachment
				$string = '';
				$path = '';
				$bString = $attachment[5];
				if ($bString) {
					$string = $attachment[0];
				} else {
					$path = $attachment[0];
				}

				$inclhash = md5(serialize($attachment));
				if (in_array($inclhash, $incl)) {
					continue;
				}
				$incl[] = $inclhash;
				$name = $attachment[2];
				$encoding = $attachment[3];
				$type = $attachment[4];
				$disposition = $attachment[6];
				$cid = $attachment[7];
				if ($disposition == 'inline' && isset($cidUniq[$cid])) {
					continue;
				}
				$cidUniq[$cid] = true;

				$mime[] = sprintf("--%s%s", $boundary, $this->LE);
				$mime[] = sprintf(
					"Content-Type: %s;%s name=\"%s\"%s",
					$type,
					$this->LE,
					$this->encodeHeader($this->secureHeader($name)),
					$this->LE
				);
				$mime[] = sprintf("Content-Transfer-Encoding: %s%s", $encoding, $this->LE);

				if ($disposition == 'inline') {
					$mime[] = sprintf("Content-ID: <%s>%s", $cid, $this->LE);
				}

				// If a filename contains any of these chars, it should be quoted,
				// but not otherwise: RFC2183 & RFC2045 5.1
				// Fixes a warning in IETF's msglint MIME checker
				// Allow for bypassing the Content-Disposition header totally
				if (!(empty($disposition))) {
				// ここを書き換えてます
				/*
					if (preg_match('/[ \(\)<>@,;:\\"\/\[\]\?=]/', $name)) {
						$mime[] = sprintf(
							"Content-Disposition: %s; filename=\"%s\"%s",
							$disposition,
							$this->encodeHeader($this->secureHeader($name)),
							$this->LE . $this->LE
						);
					} else {
						$mime[] = sprintf(
							"Content-Disposition: %s; filename=%s%s",
							$disposition,
							$this->encodeHeader($this->secureHeader($name)),
							$this->LE . $this->LE
						);
					}*/
					$mime[] = sprintf(
						"Content-Disposition: %s; %s%s%s",
						$disposition,
						$this->LE,
						$this->encodeHeaderFilename($this->secureHeader($name)),
						//$this->encodeHeader($this->secureHeader($name)),
						$this->LE . $this->LE
					);
				} else {
					$mime[] = $this->LE;
				}

				// Encode as string attachment
				if ($bString) {
					$mime[] = $this->encodeString($string, $encoding);
					if ($this->isError()) {
						return '';
					}
					$mime[] = $this->LE . $this->LE;
				} else {
					$mime[] = $this->encodeFile($path, $encoding);
					if ($this->isError()) {
						return '';
					}
					$mime[] = $this->LE . $this->LE;
				}
			}
		}

		$mime[] = sprintf("--%s--%s", $boundary, $this->LE);

		return implode("", $mime);
	}

	/*
	 * マルチバイトの長いファイル名が途中で切れるので、RFC 2231に基づいて
	 * filenameの部分を実装
	 */
	protected function encodeHeaderFilename($name)
	{
		if (function_exists('mb_strlen') && $this->hasMultiBytes($name)) {
			$encode = urlencode($name);

			if (strlen(' filename*='.$this->CharSet."''".$encode) > 74) {
				$base = $encode;
				$encode = '';
				$start = 0;
				$base_length = strlen($base);
				$count = 0;
				while($base_length >= $start) {
					$filename = ' filename*'.$count.'*=';
					if ($count === 0) {
						$filename.= "UTF-8''";
					}
					$len = 74 - strlen($filename);
					$filename.= substr($base, $start, $len);

					$encode.= $filename;
					$start += $len;
					if ($base_length > $start) {
						$encode.= ";";
					}
					$encode.= "\n";
					$count++;
				}
			} else {
				$encode = ' filename*='.$this->CharSet."''".$encode;
			}
		} else {
			$encode = " filename=".$this->encodeHeader($name);
		}
		return $encode;
	}

}