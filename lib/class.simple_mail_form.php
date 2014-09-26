<?php
require_once(dirname(__FILE__).'/class.encode_phpmailer.php');
require_once(dirname(__FILE__).'/class.mail_config.php');

/**
 * MailForm
 *
 * メールフォームを管理します。
 */
class SimpleMailForm {
	public $CharSet = 'UTF8';      // メールの文字コード
	public $FromEncoding = 'UTF8'; // メールの変換後のエンコード
	
	public $InputURL = 'input.php';       // 入力画面テンプレートのURL
	public $ConfirmURL = 'confirm.php';   // 確認画面テンプレートのURL
	public $CompleteURL = 'complete.php'; // 完了画面テンプレートのURL
	public $ErrorURL = 'input.php';       // エラー画面テンプレートのURL
	public $TmpDirectryPath = '';         // 添付ファイルを置く一時フォルダの絶対パス
	
	protected $MailConfigs = array(); // MailConfigの配列

	protected $ErrorCode = 0;            // エラーコード
	protected $ErrorMessages = array();  // エラーメッセージ
	
	protected $FormConfig = NULL;         // フォームの設定
	protected $RequestParam = NULL;       // ユーザが送信したフォームデータ(ユーザ送信データ)
	protected $Status = NULL;             // 状態
	
	// テンプレートの読み込みをクラス内でできない場合は、IncludeURLをtrueにして、
	// executeを呼び出した後、
	// include getURL();
	// をしてください。
	public $IncludeURL = true; // テンプレートファイルをクラス内で読み込みます。
	protected $URL = NULL;        // 次に読み込むテンプレートのURLです。
	
	const DEF_MAIL_CONFIG_KEY = 0;     // MailConfigsのデフォルトキー

	// ステータスパラメータの定義
	const STATUS_PARAM_INPUT = 'mailform-input-submit';
	const STATUS_PARAM_CONFIRM = 'mailform-confirm-submit';
	const STATUS_PARAM_SENDMAIL = 'mailform-sendmail-submit';
	const STATUS_PARAM_COMPLEATE = 'complete';
	
	// 状態
	const STATUS_NONE = 0;
	const STATUS_INPUT = 1;        // 入力画面
	const STATUS_CONFIRMATION = 2; // 確認画面
	const STATUS_SEND = 3;         // メール送信
	const STATUS_COMPLEATE = 4;    // 完了画面

	const BASE_ERROR_KEY = 'base';             // エラーメッセージのキーです。致命的なエラーの場合に使用します。

	// エラーコード
	const ERROR_CODE_OK = 0;
	
	// 致命的なエラー
	const ERROR_CODE_NOSET_TO = 11;            // 宛先が未設定
	const ERROR_CODE_INVALID_FORM_CONFIG = 12; // フォームの内容が不正

	const ERROR_CODE_CANNOT_SEND = 21;         // 送信に失敗した

	// 入力値が不正なエラー
	const ERROR_CODE_NOT_NULL_PARAM = 1001;      // 入力内容で、入力が必要な項目が設定されていない
	const ERROR_CODE_NEED_SELECT_PARAM = 1002;   // 入力内容で、入力が必要な項目が選択されていない
	const ERROR_CODE_INVALID_PARAM = 1003;       // 入力内容で、値が不正
	const ERROR_CODE_NOT_KANA_PARAM = 1005;      // 全角カタカナでない場合
	const ERROR_CODE_NOT_PLUS_PARAM = 1006;      // 半角数字でない場合
	const ERROR_CODE_NOT_EMAIL_PARAM = 1007;     // メールアドレスでない場合
	const ERROR_CODE_NOT_SAME_PARAM = 1008;      // 一致が必要な項目の値と一致しない場合
	const ERROR_CODE_INVALID_SELECT_PARAM = 1009;// select,radioの選択が正しくされてない場合
	const ERROR_CODE_NOT_MATCH = 1010;           // 正規表現とマッチしない場合
	const ERROR_CODE_NO_AGREEMENT = 2000;        // 同意していない場合
	
	// エラーメッセージの定義
	protected $ErrorCodeMessages = NULL;
	
	/**
	 * 空かどうかを判定します。
	 * 
	 * @return bool 空かどうか
	 */
	public static function isEmpty($value) {
		return (!isset($value) || empty($value));
	}
	
	/**
	 * テンプレートURLを取得します。
	 * 
	 * @return string テンプレートURL
	 */
	public function getURL() {
		return $this->URL;
	}
	
	/**
	 * 現在日時を返します。
	 * 
	 * @return string 現在日時
	 */
	public function getDateTime() {
		return date ('Y/m/d(D) H:i');
	}
	
	/**
	* 改行をHTML用に<br />に変更します。
	 * 
	 * @param string $value 値
	 * @return string 変換した値
	 */
	public static function replaceLinefeedCodeForHTML($value) {
		return str_replace('\n', '<br />' , $value);
	}
	
	/**
	* リクエストパラメータをエスケープします。
	 * 
	 * @param array $param リクエストパラメータ
	 * @param string $name フォーム項目名
	 * @return string 指定されたキーの変換した値
	 */
	public static function getParamater($param, $name) {
		if (!isset($param[$name])) {
			return NULL;
		}
		return self::getEscape($param[$name]);
	}
	
	/**
	 * 必要ない文字を削除します。
	 * 
	 * @param string $value 値
	 * @return string 変換した値
	 */
	public static function getEscape($value) {
		$value = str_replace('\0', '' , $value);
		return $value;
	}
	
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->FromEncoding = mb_internal_encoding();
		$this->CharSet = mb_internal_encoding();
		$this->Status = self::STATUS_NONE;
		$this->TmpDirectryPath = $_SERVER['DOCUMENT_ROOT'] . '/tmp/';
		$this->setErrorMessages();
	}
	
	/**
	 * エラーメッセージの定義を変更する場合は、
	 * この関数をオーバーライドして、
	 * $this->ErrorCodeMessagesを編集してください。
	 * arrayを作りなおしても大丈夫です。
	 */
	protected function setErrorMessages() {

		$this->ErrorCodeMessages = array(
			self::ERROR_CODE_NOSET_TO  => 'システムエラーが発生しました。', 
			self::ERROR_CODE_INVALID_FORM_CONFIG  => 'システムエラーが発生しました。',
			self::ERROR_CODE_CANNOT_SEND  => 'メールを送ることができませんでした。',
			self::ERROR_CODE_NOT_NULL_PARAM  => '%sは、必ず入力してください。',
			self::ERROR_CODE_NEED_SELECT_PARAM  => '%sを選択してください。',
			self::ERROR_CODE_NOT_KANA_PARAM  => '%sは、全角カタカナで入力してください。',
			self::ERROR_CODE_NOT_PLUS_PARAM  => '%sは、半角数字で入力してください。',
			self::ERROR_CODE_NOT_EMAIL_PARAM  => '%sは、半角英数字で正しく入力してください。',
			self::ERROR_CODE_NOT_SAME_PARAM  => '%sが一致しません。',
			self::ERROR_CODE_INVALID_SELECT_PARAM  => '%sが正しく選択されていません。',
			self::ERROR_CODE_INVALID_PARAM  => '%sが正しく入力されていません。', // 未使用
			self::ERROR_CODE_NO_AGREEMENT => '%sに同意していただける場合にのみ、送信が可能です。',
			self::ERROR_CODE_NOT_MATCH => '%sは不正な値です。'
									);
	}
	
	/**
	 * クリティカルなエラーが発生しているかの有無を返します。
	 * 
	 * @return bool クリティカルエラー有無
	 */
	public function isCriticalError() {
		if ($this->ErrorCode !== self::ERROR_CODE_OK && $this->ErrorCode < self::ERROR_CODE_CANNOT_SEND) {
			return true;
		}
		return false;
	}
	
	/**
	 * ステータスを返します。
	 * 
	 * @return ステータス
	 */
	public function getStatus() {
		return $this->Status;
	}
	
	/**
	 * エラーが発生しているかの有無を返します。
	 * 
	 * @return bool エラー有無
	 */
	public function isError() {
		if ($this->ErrorCode === self::ERROR_CODE_OK) {
			return false;
		}
		return true;
	}
	/**
	 * エラーメッセージとエラーコードを設定します。
	 *
	 * @param string $num エラーコード
	 * @param string $message エラーメッセージ
	 */
	protected function setErrorMessage() {
		$argv = func_get_args();
		$key = array_shift( $argv );
		$num = array_shift( $argv );
		
		if ($num === self::ERROR_CODE_OK || $this->isCriticalError() ) {
			return;
		}

		$this->ErrorCode = $num;
		$message = '';
		if (!isset($this->ErrorCodeMessages[$this->ErrorCode])) {
			$message = '申し訳ありません。<br/>エラーが発生しております。';
		}
		$message = $this->ErrorCodeMessages[$this->ErrorCode];

		if ($this->isCriticalError()) {
			$this->ErrorMessages = array();
		}

		$message = vsprintf( $message, $argv );
		
		$this->ErrorMessages[$key] = $message;
	}

	/**
	 * 指定されたフォーム項目名の値がエラーかどうかを返します。
	 *
	 * @return array エラーメッセージ配列
	 */
	public function isErrorValue($name) {
		return isset($this->ErrorMessages[$key]);
	}
	/**
	 * エラーメッセージを返します。
	 * 毎回エラーメッセージの配列を作り直します。
	 *
	 * @return string エラーメッセージ
	 */
	public function getErrorMessage() {
		$message = '';
		$messages = $this->getErrorMessages();
		foreach ($messages as &$value) {
			$message .= '\n'.$value;
		}
		return $message;
	}

	/**
	 * エラーメッセージ配列を返します。
	 * 毎回エラーメッセージの配列を作り直します。
	 *
	 * @return array エラーメッセージ配列
	 */
	public function getErrorMessages() {
		$messages = array();
		foreach ($this->ErrorMessages as $key =>  &$value) {
			// メッセージは重複させない
			if (in_array($value , $messages))
				continue;
			$messages[] = $value;
		}
		return $messages;
	}

	/**
	 * 表示するエラーメッセージの内容を変更します。
	 *
	 * @param integer $errorcode エラーコード
	 * @param string $message エラーメッセージ
	 */
	public function setConstErrorMessage($errorcode, $message) {
		$this->ErrorCodeMessages[$errorcode] = $message;
	}

	/**
	 * 指定されたキーのメール設定を返します。
	 * メール設定を取得する時は、必ずこの関数を呼び出してください。
	 *
	 * @param integer $index メール設定のindex
	 */
	protected function getMailConfigByIndex($index) {
		if (!isset($this->MailConfigs[$index])) {
			$this->MailConfigs[$index] = new MailConfig();
		}
		return $this->MailConfigs[$index];
	}

	/**
	 * 送信先のメールアドレスと名前を設定します。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function addTo($address,$name='') {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->ToAdresses[$address] = $name;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * 送信先のメールアドレスと名前を設定します。
	 *
	 * @param integer $index メール設定のindex
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function addToByIndex($index, $address, $name='') {
		$config = $this->getMailConfigByIndex($index);
		$config->ToAdresses[$address] = $name;
	}
	
	/**
	 * 宛先のバッファ(array)を返します。
	 *
	 * @return array 宛先のバッファ
	 */
	protected function getToArray() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->ToAdresses;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * 宛先のバッファ(array)を返します。
	 *
	 * @param integer $index メール設定のindex
	 * @return array 宛先のバッファ
	 */
	protected function getToArrayByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->ToAdresses;
	}

	/**
	 * 差出人のメールアドレスと名前を設定します。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setFrom($address,$name='') {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->FromAdress = array();
		$config->FromAdress[$address] = $name;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * 差出人のメールアドレスと名前を設定します。
	 *
	 * @param integer $index メール設定のindex
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setFromByIndex($index, $address,$name='') {
		$config = $this->getMailConfigByIndex($index);
		$config->FromAdress = array();
		$config->FromAdress[$address] = $name;
	}

	/**
	 * 差出人のバッファ(array)を返します。
	 *
	 * @return array 差出人のバッファ
	 */
	protected function getFromArray() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->FromAdress;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * 差出人のバッファ(array)を返します。
	 *
	 * @param integer $index メール設定のindex
	 * @return array 差出人のバッファ
	 */
	protected function getFromArrayByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->FromAdress;
	}

	/**
	 * ReturnPathメールアドレスと名前を設定します。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPath($address,$name='') {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->ReturnPath = array();
		$config->ReturnPath[$address] = $name;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * ReturnPathメールアドレスと名前を設定します。
	 *
	 * @param integer $index メール設定のindex
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPathByIndex($index, $address,$name='') {
		$config = $this->getMailConfigByIndex($index);
		$config->ReturnPath = array();
		$config->ReturnPath[$address] = $name;
	}

	/**
	 * ReturnPathのバッファ(array)を返します。
	 *
	 * @return array 差出人のバッファ
	 */
	protected function getReturnPathArray() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->ReturnPath;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * ReturnPathのバッファ(array)を返します。
	 *
	 * @param integer $index メール設定のindex
	 * @return array 差出人のバッファ
	 */
	protected function getReturnPathArrayByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->ReturnPath;
	}

	/**
	 * メール本文のテンプレートを設定します。
	 *
	 * @param string $text メール本文のテンプレート
	 */
	public function setMailBodyTemplate($text) {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->MailBodyTemplate = $text;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * メール本文のテンプレートを設定します。
	 *
	 * @param integer $index メール設定のindex
	 * @param string $text メール本文のテンプレート
	 */
	public function setMailBodyTemplateByIndex($index, $text) {
		$config = $this->getMailConfigByIndex($index);
		$config->MailBodyTemplate = $text;
	}

	/**
	 * メール本文のテンプレートを返します。
	 *
	 * @return string メール本文のテンプレート
	 */
	protected function getMailBodyTemplate() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->MailBodyTemplate;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * メール本文のテンプレートを返します。
	 *
	 * @param integer $index メール設定のindex
	 * @return string メール本文のテンプレート
	 */
	protected function getMailBodyTemplateByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->MailBodyTemplate;
	}

	/**
	 * メール件名のテンプレートを設定します。
	 *
	 * @param string $text メール件名のテンプレート
	 */
	public function setMailSubjectTemplate($text) {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->MailSubjectTemplate = $text;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * メール件名のテンプレートを設定します。
	 *
	 * @param integer $index メール設定のindex
	 * @param string $text メール件名のテンプレート
	 */
	public function setMailSubjectTemplateByIndex($index, $text) {
		$config = $this->getMailConfigByIndex($index);
		$config->MailSubjectTemplate = $text;
	}

	/**
	 * メール件名のテンプレートを返します。
	 *
	 * @return string メール件名のテンプレート
	 */
	protected function getMailSubjectTemplate() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->MailSubjectTemplate;
	}

	/**
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前です。
	 *
	 * メール件名のテンプレートを返します。
	 *
	 * @param integer $index メール設定のindex
	 * @return string メール件名のテンプレート
	 */
	protected function getMailSubjectTemplateByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->MailSubjectTemplate;
	}

	/**
	 * フォーム内容を設定します。
	 *
	 * @param string $yaml フォーム内容(yaml形式)
	 */
	public function setFormConfig($yaml) {
		$parsed = yaml_parse($yaml);
		if (!$parsed) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			return;
		}
		$this->FormConfig = $parsed;
	}
	
	/**
	 * $_POSTから、状態を取得します。
	 *
	 * @param array $request リクエストデータ
	 * @return int 状態
	 */
	protected function getStatusFromPostParam(&$request) {
		if (isset($request[self::STATUS_PARAM_CONFIRM])) {
			return self::STATUS_CONFIRMATION;
		}
		if (isset($request[self::STATUS_PARAM_COMPLEATE])) {
			return self::STATUS_COMPLEATE;
		}
		if (isset($request[self::STATUS_PARAM_SENDMAIL])) {
			return self::STATUS_SEND;
		}
		if (isset($request[self::STATUS_PARAM_INPUT])) {
			return self::STATUS_INPUT;
		}
		return self::STATUS_NONE;
	}

	/**
	 * リクエストパラメータの設定とステータスの設定をします。
	 *
	 */
	public function setRequestParameter() {
		if (empty($this->FormConfig)) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			return '';
		}
		$request = $_REQUEST;
		$this->RequestParam = array();
		$items = $this->FormConfig['items'];
		foreach ($items as $key => &$values) {
			$value = self::getParamater($request, $key);
			if (is_null($value)) {
				continue;
			}
			$this->RequestParam[$key] = $request[$key];
		}
		$this->Status = $this->getStatusFromPostParam($request);
	}

	/**
	 * 入力画面を表示します。
	 *
	 */
	public function showInputPage() {
		$this->setTemplatePage($this->InputURL);
	}

	/**
	 * 確認画面を表示します。
	 *
	 */
	protected function showConfirmPage() {
		$this->setTemplatePage($this->ConfirmURL);
	}

	/**
	 * 完了画面を表示します。
	 *
	 */
	protected function showCompletePage() {
		$this->setTemplatePage($this->CompleteURL);
	}

	/**
	 * テンプレート画面を設定します。
	 * ただし、IncludeURLがfalseの場合は、includeしません。
	 */
	protected function setTemplatePage($url) {
		$this->URL = $url;
		if ($this->IncludeURL) {
			$mailfrom = $this;
			include_once($url);
		}
	}

	/**
	 * エラー画面を表示します。
	 *
	 */
	protected function showErrorPage() {
		$this->setTemplatePage($this->ErrorURL);
	}
	
	/**
	 * 正しいアクセスかどうかをチェックします。
	 * 
	 * @return bool 正常なアクセスかどうか
	 */
	protected function isCorrectAccess() {
		return true;
	}

	/**
	 * メールフォームの処理実行します。
	 * 
	 */
	public function execute() {
		$this->setRequestParameter();
		if (empty($this->FormConfig)) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			$this->showErrorPage();
			return;
		}
		
		if (!$this->isCorrectAccess()) {
			// 不正な場合はクエリ無しでリロード
			header('Location: ' . $_SERVER['REQUEST_URI']);
			exit();
		}
		
		if ($this->Status === self::STATUS_NONE) {
			$this->Status = self::STATUS_INPUT;
		}
		
		// もう一度完了ページを表示した場合
		if ($this->Status == self::STATUS_COMPLEATE) {
			$this->showCompletePage();
			return;
		}
		
		if ($this->Status === self::STATUS_CONFIRMATION) {
			$this->checkRequestParams();
		} else if ($this->Status === self::STATUS_SEND) {
			if ($this->checkRequestParams()) {
				$this->sendMail();
			}
		}
			
		
		if ($this->isError()) {
			$this->showErrorPage();
			return;
		}
		
		if ($this->Status === self::STATUS_CONFIRMATION) {
			$this->showConfirmPage();
		} else if ($this->Status === self::STATUS_SEND) {
			$completeUrl = $_SERVER['REQUEST_URI'];
			$param = self::STATUS_PARAM_COMPLEATE.'=1';
			if (strstr($completeUrl, '?')) {
				$completeUrl.= '&'.$param;
			} else {
				$completeUrl.= '?'.$param;
			}
			header('Location: ' . $completeUrl);
			exit();
		} else {
			$this->showInputPage();
		}
	}
	
	/**
	 * ユーザ送信データの指定されたフォーム項目名の値を返します。
	 * ※ HTMLに表示する時は、getValueForHTML関数を使ってください。
	 *
	 * @param string $name フォーム項目名
	 * @return string 指定されたキーの値
	 */
	protected function getValue($name) {
		if (!isset($this->RequestParam[$name])) {
			return '';
		}
		return $this->RequestParam[$name];
	}
	
	/**
	 * ユーザ送信データの指定されたフォーム項目名の値を返します。
	 * 
	 * @param string $name フォーム項目名
	 * @return string 指定されたキーの値
	 */
	public function getValueForHTML($name) {
		return htmlspecialchars($this->getValue($name), ENT_QUOTES);
	}
	
	/**
	 * ユーザ送信データの指定されたフォーム項目名の値を返します。
	 * 
	 * @param string $name フォーム項目名
	 * @return string 指定されたキーの値
	 */
	public function echoValueForHTML($name) {
		echo $this->getValueForHTML($name);
	}
	
	/**
	 * ユーザ送信データの指定されたフォーム項目名の値を返します。
	 * 改行を<br \>に変換します。
	 * 
	 * @param string $name フォーム項目名
	 * @return string 指定されたキーの値
	 */
	public function getBRValueForHTML($name) {
		$data = htmlspecialchars($this->getValue($name), ENT_QUOTES);
		return str_replace("\n", '<br \>', $data);
	}
	
	/**
	 * ユーザ送信データの指定されたフォーム項目名の値を出力します。
	 * 改行を<br \>に変換します。
	 * 
	 * @param string $name フォーム項目名
	 */
	public function echoBRValueForHTML($name) {
		echo $this->getBRValueForHTML($name);
	}
	
	/**
	 * 指定された選択フォームのselect optionフォームのHTMLを作成します。
	 *
	 * 設定されているselectvaluesの値から下記のようなHTMLを作成します。
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 *
	 * @param string $name フォーム項目名
	 */
	public function getSelectOptionHtml($name) {
		if (!is_null($value)) return $value;
		if (!isset($this->FormConfig['items']) ||
			!isset($this->FormConfig['items'][$name]) ||
			!isset($this->FormConfig['items'][$name]['selectvalues'])) {
			return '';
		}
		
		$selectvalues = $this->FormConfig['items'][$name]['selectvalues'];
		$selectedvalue = $this->getValue($name);
		$ret = '';
		foreach($selectvalues as $key => &$value) {
			if ($key == $selectedvalue) {
				$ret.= "<option value=\"".$key."\" selected=\"selected\" >".$value."</option>\n";
			} else {
				$ret.= "<option value=\"".$key."\">".$value."</option>\n";
			}
		}
		return $ret;
	}
	
	
	/**
	 * 指定された選択フォームのselect optionフォームのHTMLを出力します。
	 *
	 * 設定されているselectvaluesの値から下記のようなHTMLを作成します。
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 *
	 */
	public function echoSelectOptionHtml($name) {
		echo $this->getSelectOptionHtml($name);
	}
	
	/**
	 * 確認画面用の入力画面で入力された値のhidden inputタグを出力します。
	 */
	public function echoConfirmHiddenForm() {
		$items = $this->RequestParam;
		foreach ($items as $key => &$value) {
			echo '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'" />'."\n";
		}
	}
	
	/**
	 * 指定された選択フォームの表示名を返します。
	 *
	 * @param string $name フォーム項目名
	 * @return string 表示名
	 */
	public function getSelectedValue($name) {
		$key = $this->getValue($name);
		$v = $this->getSelectValue($name, $key);
		if (self::isEmpty($v)) {
			if (!isset($this->FormConfig['items']) ||
				!isset($this->FormConfig['items'][$name]) ||
				!isset($this->FormConfig['items'][$name]['deftext'])) {
				return '';
			}
			return $this->FormConfig['items'][$name]['deftext'];
		}
		return $v;
	}
	
	/**
	 * 指定された選択フォームの表示名を出力します。
	 *
	 * @param string $name フォーム項目名
	 */
	public function echoSelectedValue($name) {
		echo $this->getSelectedValue($name);
	}


	/**
	 * 指定された選択フォームの設定一覧(selectvalues)からkeyの表示名を返します。
	 *
	 * @param string $name フォーム項目名
	 * @param string $key キー名
	 * @return string 表示名
	 */
	public function getSelectValue($name, $key) {
		if (!isset($this->FormConfig['items']) ||
			!isset($this->FormConfig['items'][$name]) ||
			!isset($this->FormConfig['items'][$name]['selectvalues']) ||
			!isset($this->FormConfig['items'][$name]['selectvalues'][$key])) {
			return '';
		}
		return $this->FormConfig['items'][$name]['selectvalues'][$key];
	}

	/**
	 * 指定された選択フォームの設定一覧(selectvalues)を返します。
	 *
	 * @param string $name フォーム項目名
	 * @return string 設定一覧
	 */
	public function getSelectValues($name) {
		if (!isset($this->FormConfig['items']) ||
			!isset($this->FormConfig['items'][$name]) ||
			!isset($this->FormConfig['items'][$name]['selectvalues'])) {
			return '';
		}
		return $this->FormConfig['items'][$name]['selectvalues'];
	}

	/**
	 * 指定された選択フォームまたはラジオボタンが選択されているかを返します。
	 *
	 * @param string $name フォーム項目名
	 * @param string $value 値
	 */
	public function isSelected($name, $value, $def=false) {
		$key = $this->getValue($name);
		if (!isset($this->FormConfig['items']) ||
			!isset($this->FormConfig['items'][$name])) {
			return false;
		}

		if ($key == '')
			return $def;
		return ($key == $value);
	}

	/**
	 * 正数かどうかを返します。
	 *
	 * @param string $value 確認する値
	 * @return bool 正数の場合、true
	 */
	protected function isPlusNumber($value) {
		return preg_match('/^[0-9]+$/',$value);
	}

	/**
	 * 正規表現とマッチするかどうかを返します。
	 *
	 * @param string $value 確認する値
	 * @param string $pattern 正規表現
	 * @return bool 正数の場合、true
	 */
	protected function isRegularMatch($value, $pattern) {
		return preg_match($pattern,$value);
	}
	
	/**
	 * メールアドレスかどうかを返します。
	 *
	 * @param string $value 確認する値
	 * @return bool メールアドレスの場合、true
	 */
	protected function isEmailAddress($value) {
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * カナかどうかを返します。
	 *
	 * @param string $value 確認する値
	 * @return bool カナの場合、true
	 */
	protected function isKana($value) {
		return preg_match('/^[ァ-ヾ 　]+$/u',$value);
	}

	/**
	 * ユーザ送信データの中身をチェックします。
	 *
	 * @return bool 正常かどうか
	 */
	public function checkRequestParams() {
		$ret = true;
		$this->ErrorMessages = array();
		$items = $this->FormConfig['items'];
		$groups = array();
		foreach ($items as $key => &$values) {
			
			if (!$this->checkRequestParam($key, $values, $this->RequestParam[$key], $ret)) {
				return false;
			}
		}
		return $ret;
	}
	
	/**
	 * 指定された入力値をチェックします。
	 * 値が不正であった場合、$retにfalseを設定してください。
	 * チェックが続けられないようなエラーが発生した場合は、
	 * falseで復帰してください。
	 *
	 * @param string $key フォーム項目名
	 * @param string $config 指定されたフォーム項目名の設定
	 * @param string $value 入力値
	 * @param bool &$ret 値が正常化どうか
	 * @return bool チェックを続けられるかどうか
	 */
	public function checkRequestParam(&$key, &$config, &$value, &$ret) {
		$type = $config['value'];
		if (!isset($type) || !isset($config['empty']) || !isset($config['title'])) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			return false;
		}
		
		$inputValue = $this->RequestParam[$key];
		
		if (self::isEmpty($inputValue)) {
			if ($type === 'agreement') {
				$this->setErrorMessage($key, self::ERROR_CODE_NO_AGREEMENT, $config['title']);
				$ret = false;
			} else if ($config['empty'] === false) {
				if ($type == 'select' || $type == 'radio') {
					$this->setErrorMessage($key, self::ERROR_CODE_NEED_SELECT_PARAM, $config['title']);
				} else {
					$this->setErrorMessage($key, self::ERROR_CODE_NOT_NULL_PARAM, $config['title']);
				}
				$ret = false;
			} else if (isset($config['same']) && isset($this->RequestParam[$config['same']])) {
				$sameParamValue = $this->RequestParam[$config['same']];
				if (isset($sameParamValue) && !empty($sameParamValue)) {
					$this->setErrorMessage($key, self::ERROR_CODE_NOT_NULL_PARAM, $config['title']);
					$ret = false;
				}
			}
			return true;
		}


		if (isset($config['same'])) { // 位置を調整
			$keyname = $config['same'];
			if ($inputValue !== $this->RequestParam[$keyname]) {
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_SAME_PARAM, $config['title']);
				$ret = false;
			}
		}

		if (gettype($this->RequestParam[$key]) !== 'string') {
			$this->setErrorMessage($key, self::ERROR_CODE_INVALID_PARAM, $config['title']);
			$ret = false;
			return true;
		}
		
		// 値のチェック
		if ($type === 'select') { // select
			if (!isset($config['selectvalues'])) {
				$this->setErrorMessage($key, self::ERROR_CODE_INVALID_FORM_CONFIG);
				return false;
			}
			if (!isset($config['selectvalues'][$inputValue])) {
				$this->setErrorMessage($key, self::ERROR_CODE_INVALID_SELECT_PARAM, $config['title']);
				return true;
			}
		} else if ($type === 'radio') { // radio
			if (!isset($config['selectvalues'])) {
				$this->setErrorMessage($key, self::ERROR_CODE_INVALID_FORM_CONFIG);
				return false;
			}
			if (!isset($config['selectvalues'][$inputValue])) {
				$this->setErrorMessage($key, self::ERROR_CODE_INVALID_SELECT_PARAM, $config['title']);
				return true;
			}
		} else if ($type === 'kana') { // kana
			if(!$this->isKana($inputValue)) {
				// 全角カナでない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_KANA_PARAM, $config['title']);
				$ret = false;
				return true;
			}
		} else if ($type === 'plus') { // plus
			if(!$this->isPlusNumber($inputValue)) {
				// 正数でない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_PLUS_PARAM, $config['title']);
				$ret = false;
				return true;
			}
		} else if ($type === 'email') { // email
			if (!$this->isEmailAddress($inputValue)) {
				// メールアドレスでない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_EMAIL_PARAM, $config['title']);
				$ret = false;
				return true;
			}
		} else if ($type === 'regular') { // regular
			if (!$this->isRegularMatch($inputValue, $config['pattern'])) {
				// 正規表現と一致しない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_MATCH, $config['title']);
				$ret = false;
				return true;
			}
		}
		return true;
	}
	
	/**
	 * ユーザ送信データからデータを作成しメールを送信します。
	 *
	 * @return bool 成功有無
	 */
	protected function sendMail() {
		$ret = true;
		$configs = $this->MailConfigs;
		foreach ($configs as $index => &$value) {
			if (!$this->sendMailByIndex($index)) {
				$ret = false;
			}
		}
		return $ret;
	}

	/**
	 * 指定されたindexのメール設定で、
	 * ユーザ送信データからデータを作成しメールを送信します。
	 *
	 * @param integer $index メール設定のindex
	 * @return bool 成功有無
	 */
	protected function sendMailByIndex($index) {
		$mail = new EncodePHPMailer();

		$mail->FromEncoding = $this->FromEncoding;
		$mail->setCharSet($this->CharSet);
		$mail->Encoding = "7bit";

		$array = $this->getFromArrayByIndex($index);
		if (count($array) > 0) {
			foreach($array as $address => &$name) {
				$address = $this->replaceFieldTag($address);
				if (strlen($address) > 0) {
					$mail->setFrom($address, $name);
				}
			}
		}

		$array = $this->getReturnPathArrayByIndex($index);
		if (count($array) > 0) {
			foreach($array as $address => &$name) {
				$mail->setReturnPath($address, $name);
			}
		}

		$array = $this->getToArrayByIndex($index);
		$count = 0;
		foreach($array as $address => &$name) {
			$address = $this->replaceFieldTag($address);
			if (!self::isEmpty($address)) {
				$mail->AddAddress($address, $name);
				$count++;
			}
		}
		if ($count === 0) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_NOSET_TO);
			return false;
		}

		$mail->setSubject($this->getCompleteMailSubjectByIndex($index));
		$mail->setBody($this->getCompleteMailBodyByIndex($index));

		if (!$mail->Send()){
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_CANNOT_SEND);
			return false;
		}
		return true;
	}

	/**
	 * メール件名のテンプレートからメール件名を作成します。
	 *
	 * @param integer $index メール設定のindex
	 * @return string メール件名
	 */
	protected function getCompleteMailSubjectByIndex($index) {
		$subject = $this->getMailSubjectTemplateByIndex($index);
		return $this->getCompleteMailSubject($subject);
	}
	
	/**
	 * メール件名のテンプレートからメール件名を作成します。
	 *
	 * @param integer $subject メール件名のテンプレート
	 * @return string メール件名
	 */
	protected function getCompleteMailSubject($subject) {
		$items = $this->FormConfig['items'];
		foreach ($items as $key => &$values) {
			$tag = '{{{'.$key.'}}}';
			$subject = str_replace($tag, $this->getValueForHTML($key), $subject);
		}
		return $subject;
	}

	/**
	 * ユーザ送信データからメッセージ本文を作成します。
	 *
	 * @param integer $index メール設定のindex
	 * @return string メッセージ本文
	 */
	protected function getCompleteMailBodyByIndex($index) {
		$body = $this->getMailBodyTemplateByIndex($index);
		return $this->getCompleteMailBody($body);
		return $body;
	}
	
	/**
	 * ユーザ送信データからメッセージ本文を作成します。
	 *
	 * @param string $body メッセージ本文のテンプレート
	 * @return string メッセージ本文
	 */
	protected function getCompleteMailBody($body) {
		$items = $this->FormConfig['items'];
		$body = str_replace('{{{datetime}}}', $this->getDateTime(), $body);

		foreach ($items as $key => &$values) {
			$tag = '{{{'.$key.'}}}';

			if ($values['value'] === 'select' || $values['value'] === 'radio') {
				$body = str_replace($tag, $this->getSelectedValue($key), $body);
			} else {
				$body = str_replace($tag, $this->getValueForHTML($key), $body);
			}
		}
		return $body;
	}
	
	/**
	 * {{{tag}}}の部分をフィールドの値に書きかえる。
	 *
	 * @return string $value 書きかえる文字列
	 */
	protected function replaceFieldTag($value) {
		preg_match('/^\{\{\{(.*)\}\}\}$/',$value,$matches);
		if (count($matches) > 1) {
			$key = $matches[1];
			return $this->getValueForHTML($key);
		}
		return $value;
	}
}
?>