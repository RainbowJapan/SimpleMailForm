<?php
/**
 * MailerInterface
 *
 * メール送信クラスのラッパークラス
 */
interface MailerInterface {

	/**
	 * エンコード元の文字コードを設定する。
	 *
	 * @param string $value エンコード元の文字コード
	 */
	public function setFromEncoding($value);
	
	/**
	 * 差出人を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setFrom($address, $name = '', $auto = true);
	
	/**
	 * 送信元を設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addAddress($address,$name='');
	
	/**
	 * CCを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addCc($address,$name='');
	
	
	/**
	 * Bccを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function addBcc($address,$name='');
	
	/**
	 * ReplyToを設定する。
	 *
	 * @param string $address メールアドレス
	 * @param string $name 名前
	 */
	public function setReplyTo($address,$name='');

	/**
	 * ReturnPathを設定する。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPath($address, $name = '');

	/**
	 * CharSetを設定する。
	 * メールの内容を設定する前に、必ず設定してください。
	 *
	 * @param string $charset CharSet
	 */
	public function setCharSet($charset);

	/**
	 * サブジェクトを設定する。
	 *
	 * @param string $subject サブジェクト
	 */
	public function setSubject($subject);

	/**
	 * 本文をセットする。
	 *
	 * @param string $body 本文
	 * @param string $isHtml HTML有無(任意)
	 */
	public function setBody($body, $isHtml=false);

	/**
	 * 代替え本文をセットする。
	 *
	 * @param string $altbody 本文
	 */
	public function setAltBody($altbody);

	/**
	 * カスタムヘッダーを追加する。
	 *
	 * @param string $key キー名
	 * @param string $value 値
	 */
	public function addHeader($key,$value);

	/**
	 * 添付ファイルを追加する
	 *
	 * @param string $path パス
	 */
	public function addAttachment($path);

	/**
	 * メールを送信する
	 *
	 * @return bool 成功有無
	 */
	public function send();
}

/**
 * MailerFactoryInterface
 *
 * Mailerを作成するファクトリーインタフェース
 */
interface MailerFactoryInterface {
	public function getInstance();
}