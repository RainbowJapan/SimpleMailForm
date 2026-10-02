<?php
require_once __DIR__ . '/interface.session.php';
/**
 * SessionFactory用のファクトリクラス
 */
class InnerSessionFactory implements SessionFactoryInterface
{
	private static $Instance = null; // インスタンス
	
	public function getInstance()
	{
		if (self::$Instance === null) {
			self::$Instance = new InnerSession();
		}
		return self::$Instance;
	}
}

/**
 * セッションを管理するラッパークラス
 */
class InnerSession implements SessionInterface
{
	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->startSession();
	}
	
	/**
	 * セッションが開始されていない場合、セッションを開始する。
	 */
	public function startSession()
	{
		if (!$this->isStartSession()) {
			session_cache_expire(0);
			session_cache_limiter('private_no_expire');
			session_start();
		}
	}

	/**
	 * セッションが開始されている場合、セッションを終了する。
	 */
	public function destroySession()
	{
		if ($this->isStartSession()) {
			$_SESSION = [];
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time() - 42000, '/');
			}
			session_destroy();
		}
	}

	/**
	 * セッションを開始しているかどうかを返す。
	 *
	 * @return bool セッション開始有無
	 */
	public function isStartSession()
	{
		return isset($_SESSION);
	}

	/**
	 * 指定されたセッションパラメータがあるかどうか調べる。
	 *
	 * @param string $name セッションパラメータ名
	 * @return bool 存在有無
	 */
	public function isSetParam($name)
	{
		if (!$this->isStartSession()) {
			return false;
		}
		return isset($_SESSION[$name]);
	}

	/**
	 * セッションパラメータを開放する。
	 *
	 * @param string $name セッションパラメータ名
	 */
	public function unsetParam($name)
	{
		if (!$this->isStartSession()) {
			return;
		}
		unset($_SESSION[$name]);
	}

	/**
	 * セッションパラメータの値を設定する。
	 *
	 * @param string $name セッションパラメータ名
	 * @param mixed $value 値
	 * @return bool 成功有無
	 */
	public function setParam($name, $value)
	{
		if (!$this->isStartSession()) {
			return false;
		}
		$_SESSION[$name] = $value;
		return true;
	}

	/**
	 * セッションパラメータの値を取得する。
	 *
	 * @param string $name セッションパラメータ名
	 * @return mixed
	 */
	public function getParamValue($name)
	{
		if (!$this->isStartSession()) {
			return null;
		}
		if (!isset($_SESSION[$name])) {
			return null;
		}
		return $_SESSION[$name];
	}
}
