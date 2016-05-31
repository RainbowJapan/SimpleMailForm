<?php
require_once(dirname(__FILE__).'/../../lib/interfece.session.php');
/**
 * SimpleMailer用のファクトリクラス
 *
 */
class TestSessionFactory implements SessionFactoryInterface {
	private static $Instance = null; // インスタンス
	public function getInstance() {
		if (is_null(self::$Instance)) {
			self::$Instance = new TestSession;
		}
		return self::$Instance;
	}
}

/**
 * セッションを管理ラッパークラス
 *
 */
class TestSession implements SessionInterface {
	 protected $array = array();
	/**
	 * コンストラクタ
	 */
	public function __construct() {
	}
	
	/**
	 * セッションが開始されていない場合、セッションを開始する。
	 *
	 */
	public function startSession() {
	}

	/**
	 * セッションが開始されている場合、セッションを終了する。
	 *
	 */
	public function destroySession() {
	}
	
	/**
	 * セッションを開始しているかどうかを返す。
	 *
	 * @return bool セッション開始有無
	 */
	public function isStartSession() {
		return isset($this->array);
	}
	/**
	 * 指定されたセッションパラメータがあるかどうか調べる。
	 *
	 * @param string $name セッションパラメータ名
	 * @return bool 存在有無
	 */
	public function isSetParam($name) {
		if (!$this->isStartSession()) {
			return false;
		}
		return isset($this->array[$name]);
	}

	/**
	 * セッションパラメータを開放する。
	 *
	 * @param string $name セッションパラメータ名
	 */
	public function unnsetParam($name) {
		if (!$this->isStartSession()) {
			return;
		}
		unset($this->array[$name]);
	}

	/**
	 * セッションパラメータの値を設定する。
	 *
	 * @param string $name セッションパラメータ名
	 * @param string $value 値
	 * @return bool 成功有無
	 */	
	public function setParam($name, $value) {
		if (!$this->isStartSession()) {
			return false;
		}
		$this->array[$name] = $value;
		return true;
	}
	
	/**
	 * セッションパラメータの値を取得する。
	 *
	 * @param string $name セッションパラメータ名
	 * @return obj 値
	 */
	public function getParamValue($name) {
		if (!$this->isStartSession()) {
			return NULL;
		}
		if (!isset($this->array[$name])) {
			return NULL;
		}
		return $this->array[$name];
	}
}
?>