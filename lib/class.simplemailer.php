<?php
require_once __DIR__ . '/interface.mailer.php';

/**
 * SimpleMailer用のファクトリクラス。
 */
class SimplemailerFactory implements MailerFactoryInterface
{
	public function getInstance()
	{
		return new SimpleMailer();
	}
}

/**
 * メールを送信するための簡易クラス。
 *
 * SMTPUTF8に依存せず、UTF-8本文・多言語件名・多言語表示名を
 * 指定されたContent-Transfer-Encodingで送信します。
 */
class SimpleMailer implements MailerInterface
{
	const CHARSET_UTF8 = 'UTF-8';
	const ENCODING_BASE64 = 'base64';
	const ENCODING_7BIT = '7bit';
	const ENCODING_8BIT = '8bit';
	const MAX_LINE_LENGTH = 76;

	protected $FromEncoding = self::CHARSET_UTF8;
	protected $CharSet = self::CHARSET_UTF8;
	protected $Encoding = self::ENCODING_BASE64;
	protected $From = null;
	protected $To = [];
	protected $Cc = [];
	protected $Bcc = [];
	protected $ReplyTo = null;
	protected $ReturnPath = null;
	protected $Subject = '';
	protected $Body = '';
	protected $ErrorInfo = '';

	public function __construct()
	{
		$encoding = mb_internal_encoding();
		$this->FromEncoding = $encoding ? $encoding : self::CHARSET_UTF8;
	}

	/**

	 * エンコード元の文字コードを設定する。
	 *
	 * @param string $value エンコード元の文字コード
	 */
	public function setFromEncoding($value)
	{
		$this->FromEncoding = $value ? $value : self::CHARSET_UTF8;
	}

	/**
	 * 本文の転送エンコードを設定する。
	 *
	 * @param string $value エンコード
	 */
	public function setEncode($value)
	{
		$this->Encoding = $value ? strtolower((string)$value) : self::ENCODING_BASE64;
	}

	/**

	 * 差出人を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 * @param bool $auto
	 */
	public function setFrom($address, $name = '', $auto = true)
	{
		$this->From = [$address, $name];
	}

	/**

	 * 送信先を追加する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addAddress($address, $name = '')
	{
		$this->To[] = [$address, $name];
	}

	/**
	 * CCを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addCc($address, $name = '')
	{
		$this->Cc[] = [$address, $name];
	}

	/**
	 * Bccを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addBcc($address, $name = '')
	{
		$this->Bcc[] = [$address, $name];
	}

	/**
	 * ReplyToを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setReplyTo($address, $name = '')
	{
		$this->ReplyTo = [$address, $name];
	}

	/**
	 * ReturnPathを設定する。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPath($address, $name = '')
	{
		$this->ReturnPath = [$address, $name];
	}

	/**
	 * CharSetを設定する。
	 * 空の場合はUTF-8を使います。
	 *
	 * @param string $charset CharSet
	 */
	public function setCharSet($charset)
	{
		$this->CharSet = $charset ? (string)$charset : self::CHARSET_UTF8;
	}

	/**
	 * サブジェクトを設定する。
	 *
	 * @param string $subject サブジェクト
	 */
	public function setSubject($subject)
	{
		$this->Subject = $subject;
	}

	/**
	 * 本文をセットする。
	 * HTML形式には対応していません。
	 *
	 * @param string $body 本文
	 * @param bool $isHtml HTML有無(任意)
	 */
	public function setBody($body, $isHtml = false)
	{
		$this->Body = $body;
	}

	/**
	 * 代替え本文をセットする。(非対応)
	 *
	 * @param string $altbody 本文
	 */
	public function setAltBody($altbody)
	{
		throw new BadMethodCallException('unimplemented');
	}

	/**
	 * 添付ファイルを追加する。(非対応)
	 *
	 * @param string $path パス
	 */
	public function addAttachment($path)
	{
		throw new BadMethodCallException('unimplemented');
	}

	/**
	 * カスタムヘッダーを追加する。
	 * 非対応
	 * @param string $key キー名
	 * @param string $value 値
	 */
	public function addHeader($key, $value)
	{
		throw new BadMethodCallException('unimplemented');
	}

	/**

	 * メールを送信する。
	 *
	 * @return bool 成功有無
	 */
	public function send()
	{
		$this->ErrorInfo = '';

		if (empty($this->To) || !isset($this->From)) {
			$this->ErrorInfo = 'You must provide at least one recipient email address and From address';
			return false;
		}

		if (!$this->validateEncoding($this->Encoding)) {
			$this->ErrorInfo = 'Invalid encoding: ' . $this->Encoding;
			return false;
		}

		$from = $this->formatAddress($this->From);
		$to = $this->formatAddressList($this->To);
		if ($from === false || $to === false || $to === '') {
			$this->ErrorInfo = 'Invalid From or To address format';
			return false;
		}
		if ($this->hasHeaderBreaks($this->Subject)) {
			$this->ErrorInfo = 'Header injection attempt detected in subject';
			return false;
		}

		$reply = null;
		if (isset($this->ReplyTo)) {
			$reply = $this->formatAddress($this->ReplyTo);
			if ($reply === false) {
				$this->ErrorInfo = 'Invalid Reply-To address format';
				return false;
			}
		}

		$cc = $this->formatAddressList($this->Cc);
		$bcc = $this->formatAddressList($this->Bcc);
		if ($cc === false || $bcc === false) {
			$this->ErrorInfo = 'Invalid CC or BCC address format';
			return false;
		}

		$subject = $this->encodeHeaderText($this->convertToCharset($this->Subject));
		$body = $this->encodeBody($this->convertToCharset($this->Body));
		if ($body === false) {
			return false;
		}

		// Windows環境は CRLF (\r\n)、Unix/Linux環境は LF (\n) に変換する
		$headerEol = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "\r\n" : "\n";

		$headers = [];
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-Type: text/plain; charset=' . $this->CharSet;
		$headers[] = 'Content-Transfer-Encoding: ' . $this->Encoding;
		$headers[] = 'From: ' . $from;
		if (!empty($reply)) {
			$headers[] = 'Reply-To: ' . $reply;
		}
		if (!empty($cc)) {
			$headers[] = 'Cc: ' . $cc;
		}
		if (!empty($bcc)) {
			$headers[] = 'Bcc: ' . $bcc;
		}
		$headers[] = 'X-Mailer: PHP/' . phpversion();

		$senderAddress = isset($this->ReturnPath) ? $this->ReturnPath[0] : $this->From[0];
		$returnPath = $this->normalizeAddress($senderAddress);
		if ($returnPath === false) {
			$this->ErrorInfo = 'Invalid Return-Path address format';
			return false;
		}
		$parameters = '-f' . escapeshellarg($returnPath);

		$result = $this->sendMail(
			$to,
			$subject,
			$this->normalizeBreaks($body, "\n"),
			$this->normalizeBreaks(implode("\n", $headers), $headerEol),
			$parameters
		);

		if (!$result && empty($this->ErrorInfo)) {
			$this->ErrorInfo = 'mail() function returned false';
		}

		return $result;
	}

	/**
	 * 入力文字列を送信用文字コードへ変換する。
	 *
	 * @param string $value 値
	 *
	 * @return string
	 */
	protected function convertToCharset($value)
	{
		return mb_convert_encoding((string)$value, $this->CharSet, $this->FromEncoding);
	}

	/**
	 * 本文を指定されたContent-Transfer-Encodingへ変換する。
	 *
	 * @param string $value 本文
	 *
	 * @return string|false
	 */
	protected function encodeBody($value)
	{
		switch ($this->Encoding) {
			case self::ENCODING_BASE64:
				return chunk_split(base64_encode($value), self::MAX_LINE_LENGTH, "\n");
			case self::ENCODING_7BIT:
				if ($this->has8bitChars($value)) {
					$this->ErrorInfo = '7bit encoding cannot contain 8bit characters';
					return false;
				}
				// Fall through: 7bit and 8bit only require normalized line breaks.
				// no break
			case self::ENCODING_8BIT:
				$value = $this->normalizeBreaks($value);
				if (substr($value, -1) !== "\n") {
					$value .= "\n";
				}
				return $value;
		}
		return false;
	}

	/*
	 * 本文の転送エンコードを検証する。
	 *
	 * @param string $encoding エンコード
	 *
	 * @return bool
	 */
	protected function validateEncoding($encoding)
	{
		return in_array(
			strtolower((string)$encoding),
			[self::ENCODING_7BIT, self::ENCODING_8BIT, self::ENCODING_BASE64],
			true
		);
	}

	/**
	 * ヘッダー値をRFC 2047形式へエンコードする。
	 *
	 * @param string $value 値
	 *
	 * @return string
	 */
	protected function encodeHeaderText($value)
	{
		$value = (string)$value;
		if ($value === '') {
			return '';
		}
		return mb_encode_mimeheader($value, $this->CharSet, 'B', "");
	}

	/**
	 * アドレス配列をヘッダー用文字列に変換する。
	 *
	 * @param array $addressPair アドレス・表示名
	 *
	 * @return string|false
	 */
	protected function formatAddress($addressPair)
	{
		$address = $this->normalizeAddress($addressPair[0]);
		if ($address === false) {
			return false;
		}

		$name = isset($addressPair[1]) ? $addressPair[1] : '';
		if ($name !== null && (string)$name !== '') {
			$name = $this->convertToCharset($name);
			if ($this->hasHeaderBreaks($name)) {
				return false;
			}
			return $this->encodeHeaderText($name) . ' <' . $address . '>';
		}

		return $address;
	}

	/**
	 * アドレス配列リストをヘッダー用文字列に変換する。
	 *
	 * @param array $addresses アドレスリスト
	 *
	 * @return string|false
	 */
	protected function formatAddressList($addresses)
	{
		$result = [];
		foreach ($addresses as $address) {
			$formatted = $this->formatAddress($address);
			if ($formatted === false) {
				return false;
			}
			$result[] = $formatted;
		}

		return implode(', ', $result);
	}

	/**
	 * メールアドレスをSMTPUTF8なしで扱える形に正規化する。
	 *
	 * @param string $address メールアドレス
	 *
	 * @return string|false
	 */
	protected function normalizeAddress($address)
	{
		$address = trim((string)$address);
		if ($address === '' || $this->hasHeaderBreaks($address)) {
			return false;
		}

		$pos = strrpos($address, '@');
		if ($pos === false) {
			return false;
		}

		$local = substr($address, 0, $pos);
		$domain = substr($address, $pos + 1);
		if ($local === '' || $domain === '') {
			return false;
		}

		if ($this->has8bitChars($local)) {
			$this->ErrorInfo = 'SMTPUTF8 is required for Unicode local parts';
			return false;
		}

		if ($this->has8bitChars($domain)) {
			return false;
		}

		$normalized = $local . '@' . $domain;
		if (!$this->validateAddress($normalized)) {
			return false;
		}

		return $normalized;
	}

	/**
	 * メールアドレスを検証する。
	 *
	 * @param string $address メールアドレス
	 *
	 * @return bool
	 */
	protected function validateAddress($address)
	{
		return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * 8bit文字を含むか確認する。
	 *
	 * @param string $value 値
	 *
	 * @return bool
	 */
	protected function has8bitChars($value)
	{
		return preg_match('/[\x80-\xff]/', (string)$value) > 0;
	}

	/**
	 * ヘッダー値に改行が含まれるか確認する。
	 *
	 * @param string $value 値
	 *
	 * @return bool
	 */
	protected function hasHeaderBreaks($value)
	{
		return strpos((string)$value, "\r") !== false || strpos((string)$value, "\n") !== false;
	}

	/**
	 * 改行コードを統一する。
	 *
	 * @param string $string 文字列
	 * @param string $to 改行コード
	 *
	 * @return string
	 */
	protected function normalizeBreaks($string, $to = "\n")
	{
		return preg_replace("/\r\n|\r|\n/", $to, (string)$string);
	}

	/**

	 * メールを送信するラッパー。
	 *
	 * @param string $to 宛先
	 * @param string $subject 件名
	 * @param string $body 本文
	 * @param string $headers ヘッダー
	 * @param string|null $parameters 追加パラメータ
	 * @return bool
	 */
	protected function sendMail($to, $subject, $body, $headers, $parameters)
	{
		if ($parameters === null || $parameters === '') {
			return mail($to, $subject, $body, $headers);
		}

		return mail($to, $subject, $body, $headers, $parameters);
	}

	/**
	 * エラー情報を取得する。
	 *
	 * @return string
	 */
	public function getErrorInfo()
	{
		return $this->ErrorInfo;
	}
}
