<?php
/**
 * MailerInterface
 *
 * メール送信クラスのラッパークラス
 */
interface SessionInterface {
	/**
	 * セッションが開始されていない場合、セッションを開始する。
	 *
	 */
	public function startSession();
	
	/**
	 * セッションが開始されている場合、セッションを終了する。
	 *
	 */
	public function destroySession();
	
	/**
	 * セッションを開始しているかどうかを返す。
	 *
	 * @return bool セッション開始有無
	 */
	public function isStartSession();
	
	/**
	 * 指定されたセッションパラメータがあるかどうか調べる。
	 *
	 * @param string $name セッションパラメータ名
	 * @return bool 存在有無
	 */
	public function isSetParam($name);
	
	/**
	 * セッションパラメータを開放する。
	 *
	 * @param string $name セッションパラメータ名
	 */
	public function unnsetParam($name);
	
	/**
	 * セッションパラメータの値を設定する。
	 *
	 * @param string $name セッションパラメータ名
	 * @param string $value 値
	 * @return bool 成功有無
	 */	
	public function setParam($name, $value);
	
	/**
	 * セッションパラメータの値を取得する。
	 *
	 * @param string $name セッションパラメータ名
	 * @return obj 値
	 */
	public function getParamValue($name);
}

/**
 * SessionFactoryInterface
 *
 * Sessionクラスを作成するファクトリーインタフェース
 */
interface SessionFactoryInterface {
	public function getInstance();
}