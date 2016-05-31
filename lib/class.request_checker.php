<?php
require_once(dirname(__FILE__).'/common.php');
require_once(dirname(__FILE__).'/class.inner_session.php');

/**
 * RequestChecker
 *
 * 簡単なリクエストチェックをするクラス
 * - リファラのチェック
 * - CSRF対策のトークンチェック
 * ※CSRFのチェックをする場合は、
 * 　getCSRFHiddenForm関数で、フォーム内にタグを入れる必要あります。
 */
class RequestChecker {
	const TOKEN_LENGTH = 64; // トークンのバイト数
	const CSRF_TOKEN_NAME = 'csrf_token'; // CSRFチェック用のタグ名

	protected $token  = NULL; // トークン
	protected $SessionFactory = NULL;          // SessionInterfaceを実装したオブジェクト
	
	function __construct() {
	}
	
	/**
	 * セッション管理クラスのファクトリーを設定する。
	 * SessionFactoryInterfaceを実装したオブジェクトを指定してください。
	 *
	 * @param string $factory SessionFactoryオブジェクト
	 */
	public function setSessionFactory($factory) {
		if (!($factory instanceof SessionFactoryInterface)) assert('Please set implement class of SessionFactoryInterface');
		$this->SessionFactory = $factory;
	}
	
	/**
	 * セッション管理クラスのインスタンスを取得します。
	 * @return SessionFactoryInterfaceを実装したオブジェクト
	 */
	protected function getSession() {
		if (!$this->SessionFactory) {
			$this->SessionFactory = new InnerSessionFactory();
		}
		return $this->SessionFactory->getInstance();
	}
	
	/**
	 * トークンを作成する。
	 *
	 * @return string トークン
	 */
	protected function createCsrfToken() {
		$bytes = openssl_random_pseudo_bytes(self::TOKEN_LENGTH/2);
		return bin2hex($bytes);
	}

	/**
	 * トークンを返す。
	 *
	 * @return string トークン
	 */
	protected function getCsrfToken() {
		if (!$this->token) {
			if ($this->getSession()->isSetParam(self::CSRF_TOKEN_NAME)) {
				$this->token = $this->getSession()->getParamValue(self::CSRF_TOKEN_NAME);
			}
			if (!$this->token) {
				$this->token = $this->createCsrfToken();
				$this->getSession()->setParam(self::CSRF_TOKEN_NAME, $this->token);
			}
		}
		return $this->token;
	}
	
	/**
	 * リファラをチェックする。
	 *
	 * @return bool 正常かどうか
	 */
	public function checkReferer() {
		if (strpos($_SERVER['HTTP_REFERER'], __getBaseUrl()) === 0) {
			return true;
		}
		return false;
	}

	/**
	 * 全チェックをする
	 *
	 * @param array $param 通常_POSTパラメータを設定する
	 * @return bool 正常かどうか
	 */
	public function check($param) {
		if (empty($param)) {
			return true;
		}

		if (!$this->checkReferer()) {
			return false;
		}

		if (!isset($param[self::CSRF_TOKEN_NAME])) {
			return false;
		}

		if ($param[self::CSRF_TOKEN_NAME] !== $this->getCsrfToken()) {
			return false;
		}
		return true;
	}

	/**
	 * HTMLに入れ込むフォームタグ
	 *
	 * @return string フォームタグ
	 */
	public function getCSRFHiddenForm() {
		return '<input type="hidden" name="'.self::CSRF_TOKEN_NAME.'" value="'.$this->getCsrfToken().'" >';
	}
}
?>