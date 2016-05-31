<?php
require_once(dirname(__FILE__).'/class.simplemailer.php');
require_once(dirname(__FILE__).'/class.mail_config.php');
require_once(dirname(__FILE__).'/interfece.mailer.php');
require_once(dirname(__FILE__).'/class.inner_session.php');
require_once(dirname(__FILE__).'/interfece.session.php');
require_once(dirname(__FILE__).'/common.php');
require_once(dirname(__FILE__).'/display_common.php');

/**
 * メールフォームを管理する。
 */
class MailForm {

	// ベースエラータグ
	const BASE_ERROR_KEY = 'base';

	// 設定ファイル・タイプ
	const MAILEFORM_FILE_TYPE_YAML = 1; // YAML
	const MAILEFORM_FILE_TYPE_JSON = 2; // JSON

	// ステータス
	const STATUS_NONE = 0;
	const STATUS_INPUT = 1;        // 入力画面
	const STATUS_CONFIRMATION = 2; // 確認画面
	const STATUS_COMPLEATE = 3;    // 送信
	const STATUS_COMPLEATED = 4;   // 完了

	// エラーコード
	const ERROR_CODE_OK = 0;
	// 致命的なエラー
	const ERROR_UNKNOWN = 11;                  // 不明なエラー
	const ERROR_CODE_NOSET_TO = 12;            // 宛先が未設定
	const ERROR_CODE_INVALID_FORM_CONFIG = 13; // フォームの内容が不正
	const ERROR_CODE_TEMP_FILE = 14;           // ローカルに保存した添付ファイルが無い又は保存できない

	const ERROR_CODE_CANNOT_SEND = 21;         // 送信に失敗した

	// 入力値が不正なエラー
	const ERROR_CODE_NOT_NULL_PARAM = 31;      // 入力内容で、入力が必要な項目が設定されていない
	const ERROR_CODE_NEED_SELECT_PARAM = 32;   // 入力内容で、入力が必要な項目が選択されていない
	const ERROR_CODE_INVALID_PARAM = 33;       // 入力内容で、値が不正
	const ERROR_CODE_UNKOWN_FILE_FORMAT = 34;  // 添付ファイルの形式が不正
	const ERROR_CODE_NOT_KANA_PARAM = 35;      // 全角カタカナでない場合
	const ERROR_CODE_NOT_PLUS_PARAM = 36;      // 半角数字でない場合
	const ERROR_CODE_TEL_PARAM = 37;           // 電話番号でない場合
	const ERROR_CODE_NOT_EMAIL_PARAM = 38;     // メールアドレスでない場合
	const ERROR_CODE_NOT_SAME_PARAM = 39;      // メールアドレスでない場合
	const ERROR_CODE_INVALID_SELECT_PARAM = 40;// selectの選択が正しくされてない場合
	const ERROR_CODE_NO_AGREEMENT = 41;        // 同意していない場合
	const ERROR_CODE_OVER_MAXLENGTH = 42;      // 文字列が最大長より長い場合
	const ERROR_CODE_NOT_YOMI_PARAM = 43;      // 全角ひらがなでない場合


	public $CharSet = 'utf8';                  // メールの文字コード
	public $MailEncoding = '7bit';             // メールのエンコード
	public $FromEncoding = 'utf8';             // フォームのエンコード

	public $InputURL = 'input.php';            // 入力画面テンプレートのURL
	public $ConfirmURL = 'confirm.php';        // 確認画面テンプレートのURL
	public $CompleteURL = 'complete.php';      // 完了画面テンプレートのURL
	public $ErrorURL = 'error.php';            // エラー画面テンプレートのURL
	public $TmpDirectryPath = '';              // 添付ファイルを置く一時フォルダの絶対パス

	public $MultiSelectedDelimiter = '／';     // 複数選択可能の項目を表示する時に使用するデリミタ

	const DEF_MAIL_CONFIG_KEY = 0;             // MailConfigsのデフォルトキー
	protected $MailConfigs = array();          // MailConfigの配列

	protected $ErrorCode = 0;                  // エラーコード
	protected $ErrorMessages = array();        // エラーメッセージ配列

	protected $FormConfig = NULL;              // フォームの設定
	protected $RequestParam = NULL;            // ユーザが送信したフォームデータ(ユーザ送信データ)
	protected $Status = NULL;                  // ステータス

	// テンプレートの読み込みをクラス内でできない場合は、IncludeTemplateURLをfalseにして、
	// executeを呼び出した後、
	// include getTemplateURL();
	// をしてください。
	public $IncludeTemplateURL = true;         // テンプレートファイルをクラス内で読み込みます。
	protected $TemplateURL = NULL;             // 次に読み込むテンプレートのURLです。

	public $DefaultEmailAddress = '';          // To, Fromのメールアドレスが設定されてない場合に使用するアドレス

	protected $MailerFactory = NULL;           // MailerInterfaceを実装したオブジェクト
	protected $SessionFactory = NULL;          // SessionInterfaceを実装したオブジェクト

	// エラーメッセージの定義
	protected $ErrorCodeMessages = array(
										self::ERROR_UNKNOWN  => 'エラーが発生しています。',
										self::ERROR_CODE_NOSET_TO  => 'システムエラーが発生しました。',
										self::ERROR_CODE_INVALID_FORM_CONFIG  => 'システムエラーが発生しました。',
										self::ERROR_CODE_TEMP_FILE  => 'システムエラーが発生しました。',
										self::ERROR_CODE_CANNOT_SEND  => 'メールを送ることができませんでした。',
										self::ERROR_CODE_NOT_NULL_PARAM  => '「%s」は必ず入力してください。',
										self::ERROR_CODE_NEED_SELECT_PARAM => '「%s」は必ず選択してください。',
										self::ERROR_CODE_INVALID_PARAM  => '「%s」の値は入力できない値です。',
										self::ERROR_CODE_UNKOWN_FILE_FORMAT  => 'アップロードファイル「%s」のファイル形式には対応してません。',
										self::ERROR_CODE_NOT_KANA_PARAM  => '「%s」はカタカナで入力してください。',
										self::ERROR_CODE_NOT_PLUS_PARAM => '「%s」は数字で入力してください。',
										self::ERROR_CODE_TEL_PARAM => '「%s」は電話番号を入力してください。',
										self::ERROR_CODE_NOT_EMAIL_PARAM => '「%s」は正しいメールアドレスを入力してください。',
										self::ERROR_CODE_NOT_SAME_PARAM => '「%s」は「%s」と同じ値を入力してください。',
										self::ERROR_CODE_INVALID_SELECT_PARAM => '「%s」に不正な値が入力されています。',
										self::ERROR_CODE_NO_AGREEMENT => '同意していただく必要があります。',
										self::ERROR_CODE_OVER_MAXLENGTH => '「%s」は、%s文字以内でご記入ください。',
										self::ERROR_CODE_NOT_YOMI_PARAM => '「%s」はひらがなで入力してください。',
								);

	/**
	 * テンプレートURLを取得する。
	 *
	 * @return string テンプレートURL
	 */
	public function getTemplateURL() {
		return $this->TemplateURL;
	}

	/**
	 * 現在日時を返す。
	 *
	 * @return string 現在日時
	 */
	public function getDateTime() {
		return date ('Y/m/d(D) H:i');
	}

	/**
	 * リクエストパラメータをエスケープする。
	 *
	 * @param array $param リクエストパラメータ
	 * @param string $name キー名
	 * @return string 指定されたキーの変換した値
	 */
	public static function getParamater($param, $name) {
		if (!isset($param[$name])) {
			return NULL;
		}
		return MailForm::getEscape($param[$name]);
	}

	/**
	 * 必要ない文字を削除する。
	 *
	 * @param string $value 値
	 * @return string 変換した値
	 */
	public static function getEscape($value) {
		$value = str_replace("\0", '' , $value);
		return $value;
	}

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->FromEncoding = mb_internal_encoding();
		//$this->CharSet = 'iso-2022-jp';
		$this->CharSet = mb_internal_encoding();
		$this->Status = self::STATUS_NONE;
		$this->TmpDirectryPath = $_SERVER['DOCUMENT_ROOT'] . '/tmp/';
	}

	/**
	 * メール送信クラスのファクトリーを設定する。
	 * MailerFactoryInterfaceを実装したオブジェクトを指定してください。
	 *
	 * @param string $factory MailerFactoryオブジェクト
	 */
	public function setMailerFactory($factory) {
		if (!($factory instanceof MailerFactoryInterface)) assert('Please set implement class of MailerFactoryInterface');
		$this->MailerFactory = $factory;
	}
	
	/**
	 * メール送信クラスのファクトリーから、Mailerオブジェクトを取得する。
	 *
	 * @return Mailerオブジェクト
	 */
	protected function getMailer() {
		if ($this->MailerFactory == NULL) {
			$this->MailerFactory = new SimpleMailerFactory();
		}
		
		return $this->MailerFactory->getInstance();
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
	 * エラーメッセージを設定します。
	 *
	 * @param int $key キー
	 * @param string $message メッセージ
	 */
	public function setErrorCodeMessage($key, $message) {
		$this->ErrorCodeMessages[$key] = $message;
	}

	/**
	 * ステータスを返す。
	 *
	 * @return ステータス
	 */
	public function getStatus() {
		return $this->Status;
	}

	/**
	 * エラーが発生しているかの有無を返す。
	 *
	 * @return bool エラー有無
	 */
	public function isError() {
		if ($this->ErrorCode === self::ERROR_CODE_OK) {
			return false;
		}
		return true;
	}

	/*
	 * エラーメッセージとエラーコードを設定する。
	 *
	 * @param string $num エラーコード
	 * @param string $message エラーメッセージ
	 */
	public function setErrorMessage() {
		$argv = func_get_args();
		$key = array_shift( $argv );
		$num = array_shift( $argv );
		if ($num === self::ERROR_CODE_OK || $this->isCriticalError() ) {
			return;
		}

		$this->ErrorCode = $num;
		$message = '';
		if (!isset($this->ErrorCodeMessages[$this->ErrorCode])) {
			$message = $this->ErrorCodeMessages[self::ERROR_UNKNOWN];
		} else {
			$message = $this->ErrorCodeMessages[$this->ErrorCode];
		}

		if ($this->isCriticalError()) {
			$this->ErrorMessages = array();
		}
		$message = vsprintf( $message, $argv );

		$this->ErrorMessages[$key] = $message;
	}

	/**
	 * クリティカルなエラーが発生しているかの有無を返す。
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
	 * 指定されたフィールドがエラーかどうかを返す。
	 *
	 * @return array エラーメッセージ配列
	 */
	public function isErrorByFieldName($key) {
		return isset($this->ErrorMessages[$key]);
	}

	/**
	 * エラーメッセージを返す。
	 * 毎回エラーメッセージの配列を作り直す。
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
	 * エラーメッセージ配列を返す。
	 * 毎回エラーメッセージの配列を作り直す。
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
	 * 指定されたキーのメール設定を返す。
	 * メール設定を取得する時は、必ずこの関数を呼び出してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @return MailConfig メール設定
	 */
	protected function getMailConfigByIndex($index) {
		if (!isset($this->MailConfigs[$index])) {
			$this->MailConfigs[$index] = new MailConfig();
		}
		return $this->MailConfigs[$index];
	}

	/**
	 * 送信先のメールアドレスと名前を設定する。
	 *
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function addTo($address,$name='') {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->ToAdresses[$address] = $name;
	}

	/**
	 * 送信先のメールアドレスと名前を設定する。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function addToByIndex($index, $address, $name='') {
		$config = $this->getMailConfigByIndex($index);
		$config->ToAdresses[$address] = $name;
	}

	/**
	 * 宛先のバッファ(array)を返す。
	 *
	 * @return array 宛先のバッファ
	 */
	protected function getToArray() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->ToAdresses;
	}

	/**
	 * 宛先のバッファ(array)を返す。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param string $index メール設定のindex
	 * @return array 宛先のバッファ
	 */
	protected function getToArrayByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->ToAdresses;
	}

	/**
	 * 差出人のメールアドレスと名前を設定する。
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
	 * 差出人のバッファ(array)を返す。
	 *
	 * @return array 差出人のバッファ
	 */
	protected function getFromArray() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->FromAdress;
	}

	/**
	 * 差出人のメールアドレスと名前を設定する。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setFromByIndex($index, $address,$name='') {
		$config = $this->getMailConfigByIndex($index);
		$config->FromAdress = array();
		$config->FromAdress[$address] = $name;
	}

	/**
	 * 差出人のバッファ(array)を返す。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @return array 差出人のバッファ
	 */
	protected function getFromArrayByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->FromAdress;
	}

	/**
	 * ReturnPathメールアドレスと名前を設定する。
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
	 * ReturnPathメールアドレスと名前を設定する。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 *
	 * @param mixed $index メール設定のindex
	 * @param string $address アドレス
	 * @param string $name 名前
	 */
	public function setReturnPathByIndex($index, $address,$name='') {
		$config = $this->getMailConfigByIndex($index);
		$config->ReturnPath = array();
		$config->ReturnPath[$address] = $name;
	}

	/**
	 * ReturnPathのバッファ(array)を返す。
	 *
	 * @return array 差出人のバッファ
	 */
	protected function getReturnPathArray() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->ReturnPath;
	}

	/**
	 * ReturnPathのバッファ(array)を返す。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @return array 差出人のバッファ
	 */
	protected function getReturnPathArrayByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->ReturnPath;
	}

	/**
	 * メール本文のテンプレートを設定する。
	 *
	 * @param string $text メール本文のテンプレート
	 */
	public function setMailBodyTemplate($text) {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->MailBodyTemplate = $text;
	}

	/**
	 * メール本文のテンプレートを設定する。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @param string $text メール本文のテンプレート
	 */
	public function setMailBodyTemplateByIndex($index, $text) {
		$config = $this->getMailConfigByIndex($index);
		$config->MailBodyTemplate = $text;
	}

	/**
	 * メール本文のテンプレートを返す。
	 *
	 * @return string メール本文のテンプレート
	 */
	protected function getMailBodyTemplate() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->MailBodyTemplate;
	}

	/**
	 * メール本文のテンプレートを返す。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @return string メール本文のテンプレート
	 */
	protected function getMailBodyTemplateByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->MailBodyTemplate;
	}

	/**
	 * メール件名のテンプレートを設定する。
	 *
	 * @param string $text メール件名のテンプレート
	 */
	public function setMailSubjectTemplate($text) {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->MailSubjectTemplate = $text;
	}

	/**
	 * メール件名のテンプレートを設定する。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @param string $text メール件名のテンプレート
	 */
	public function setMailSubjectTemplateByIndex($index, $text) {
		$config = $this->getMailConfigByIndex($index);
		$config->MailSubjectTemplate = $text;
	}

	/**
	 * メール件名のテンプレートを返す。
	 *
	 * @return string メール件名のテンプレート
	 */
	protected function getMailSubjectTemplate() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->MailSubjectTemplate;
	}

	/**
	 * メール件名のテンプレートを返す。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前。
	 *
	 * @param mixed $index メール設定のindex
	 * @return string メール件名のテンプレート
	 */
	protected function getMailSubjectTemplateByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->MailSubjectTemplate;
	}
	
	/**
	 * メールに添付を許可するかどうかのフラグを設定する。
	 *
	 * @param bool $flag メールに添付を許可するかどうかのフラグ
	 */
	public function setArrowTempFiles($flag) {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		$config->ArrowTempFiles = $flag;
	}

	/**
	 * メールに添付を許可するかどうかのフラグを設定する。
	 * indexは、メールの設定を認識するための名前。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 *
	 * @param mixed $index メール設定のindex
	 * @param bool $flag メールに添付を許可するかどうかのフラグ
	 */
	public function setArrowTempFilesByIndex($index, $flag) {
		$config = $this->getMailConfigByIndex($index);
		$config->ArrowTempFiles = $flag;
	}

	/**
	 * メールに添付を許可するかどうかのフラグを返す。
	 *
	 * @return bool メールに添付を許可するかどうかのフラグ
	 */
	protected function getArrowTempFiles() {
		$config = $this->getMailConfigByIndex(self::DEF_MAIL_CONFIG_KEY);
		return $config->ArrowTempFiles;
	}

	/**
	 * メールに添付を許可するかどうかのフラグを返す。
	 * 複数のフォーマットでメールを送信する時に使用してください。
	 * indexは、メールの設定を認識するための名前。
	 *
	 * @param mixed $index メール設定のindex
	 * @return bool メールに添付を許可するかどうかのフラグ
	 */
	protected function getArrowTempFilesByIndex($index) {
		$config = $this->getMailConfigByIndex($index);
		return $config->ArrowTempFiles;
	}
	
	/**
	 * フォーマットにしたがって、設定テキストをパースする。
	 *
	 * @param string $text 設定テキスト
	 * @return array パースした結果
	 */
	public function settingTextParse($setting, $format) {
		switch ($format) {
		case self::MAILEFORM_FILE_TYPE_YAML:
			return yaml_parse($setting);
		case self::MAILEFORM_FILE_TYPE_JSON:
			return json_decode($setting, true);
		}
		return NULL;
	}

	/**
	 * フォーム内容をファイルから読み込む。
	 *
	 * @param string $setting フォーム内容(yaml形式 or json形式)
	 * @param string $templateDirectoryPath テンプレートファイルフォルダのパス
	 * @param int $format ファイル・タイプ
	 * @return bool 読み込めたかどうか
	 */
	public function loadFormConfig($filepath, $templateDirectoryPath=NULL, $format=self::MAILEFORM_FILE_TYPE_YAML) {
		$formconfig = file_get_contents($filepath);
		return $this->setFormConfig($formconfig, $templateDirectoryPath, $format);
	}

	/**
	 * フォーム内容を設定する。
	 * 細かいフォーマットはチェックしないので注意。
	 *
	 * @param string $setting フォーム内容(yaml形式 or json形式)
	 * @param string $templateDirectoryPath テンプレートファイルフォルダのパス
	 * @param int $format ファイル・タイプ
	 * @return bool 読み込めたかどうか
	 */
	public function setFormConfig($setting, $templateDirectoryPath=NULL, $format=self::MAILEFORM_FILE_TYPE_YAML) {
		$this->FormConfig = $this->settingTextParse($setting, $format);
		if (!$this->FormConfig) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			return false;
		}

		if (isset($this->FormConfig['mailconf'])) {
			$items = $this->FormConfig['mailconf'];
			foreach ($items as $key => &$values) {
				if (!isset($values)) continue;
				if (isset($values['from'])) {
					$t = $this->parseMailAddressList($values['from']);
					$this->setFromByIndex($key, $t[0], $t[1]);
				}
				if (isset($values['to'])) {
					$to = $values['to'];
					if (is_array($to)) {
						foreach ($to as $v) {
							$t = $this->parseMailAddressList($v);
							$this->addToByIndex($key, $t[0], $t[1]);
						}
					} else {
						$t = $this->parseMailAddressList($to);
						$this->addToByIndex($key, $t[0], $t[1]);
					}
				}
				if (isset($values['subject']))
					$this->setMailSubjectTemplateByIndex($key, $values['subject']);
				if (isset($values['body-template-path'])) {
					$body = NULL;
					if ($templateDirectoryPath == NULL) {
						$body = file_get_contents($values['body-template-path']);
					} else {
						$body = file_get_contents($templateDirectoryPath.'/'.$values['body-template-path']);
					}
					$this->setMailBodyTemplateByIndex($key, $body);
				}
				if (isset($values['arrow-temp-files'])) {
					$this->setArrowTempFilesByIndex($key, $values['arrow-temp-files']);
				}
			}
		}
		return true;
	}

	/**
	 * 設定ファイルのメールアドレスの設定をパースする。
	 *
	 * @param string $address アドレス文字列
	 * @return array email, nameの配列
	 */
	protected function parseMailAddressList($address) {
		$start = strpos($address,'<');
		$end = strpos($address,'>');
		if($start === false || $end === false ||
		$end <= $start){
			return array(trim($address), '');
		}
		$email = substr($address, $start+1, $end-$start-1);
		$name = substr($address, 0, $start);
		return array(trim($email), trim($name));
	}

	/**
	 * $_POSTから、ステータスを取得する。
	 *
	 * @param array $param $_POSTを指定
	 * @return integer ステータス
	 */
	protected function getStatusFromPostParam($param) {
		if (isset($param['mailform-confirm-submit'])) {
			return self::STATUS_CONFIRMATION;
		}
		if (isset($param['mailform-complete-submit'])) {
			return self::STATUS_COMPLEATE;
		}
		if (isset($param['mailform-input-submit'])) {
			return self::STATUS_INPUT;
		}
		return self::STATUS_NONE;
	}

	/**
	 * メールで文字化けする文字を変換する。
	 * UTF8の場合のみ対応、他の文字コードに対応する場合は、
	 * オーバーライドしてください。
	 *
	 * @param string $value 変換する文字列
	 * @return string 変換後の文字列
	 */
	public function replaceGreekingText($value) {
		if (mb_internal_encoding()==='UTF-8') {
			return __replaceGreekingText($value);
		}
		return $value;
	}

	/**
	 * $_POSTを設定する。
	 *
	 * @param array $param $_POSTを指定
	 */
	public function setRequestParameter($param) {
		if (empty($this->FormConfig)) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			return;
		}
		$this->RequestParam = array();
		$items = $this->FormConfig['items'];
		foreach ($items as $key => &$values) {
			$value = MailForm::getParamater($param, $key);
			if (is_null($value)) {
				continue;
			}
			$this->RequestParam[$key] = $this->replaceGreekingText($param[$key]);
		}
		$this->Status = $this->getStatusFromPostParam($param);
	}

	/**
	 * 入力画面を表示する。
	 *
	 */
	public function showInputPage() {
		$this->getSession()->setParam('mailform_status', self::STATUS_INPUT);
		$this->setTemplatePage($this->InputURL);
	}

	/**
	 * 確認画面を表示する。
	 *
	 */
	protected function showConfirmPage() {
		$this->getSession()->setParam('mailform_status', self::STATUS_CONFIRMATION);
		$this->setTemplatePage($this->ConfirmURL);
	}

	/**
	 * 完了画面を表示する。
	 *
	 */
	protected function showCompletePage() {
		$this->unsetSession();
		$this->getSession()->setParam('mailform_status', self::STATUS_COMPLEATED);
		$this->setTemplatePage($this->CompleteURL);
	}

	/**
	 * テンプレート画面を表示する。
	 * ただし、IncludeTemplateURLがfalseの場合は、includeしません。
	 */
	protected function setTemplatePage($url) {
		$this->TemplateURL = $url;
		if ($this->IncludeTemplateURL) {
			$mailform = $this;
			include_once($url);
		}
	}

	/**
	 * エラー画面を表示する。
	 *
	 */
	protected function showErrorPage() {
		$this->setTemplatePage($this->ErrorURL);
	}

	/**
	 * 正しいアクセスかどうかをチェックする。
	 *
	 * @return bool 正常なアクセスかどうか
	 */
	protected function isCorrectAccess() {
		if (!$this->getSession()->isSetParam('mailform_status')) {
			if ($this->Status === self::STATUS_INPUT ||
				$this->Status === self::STATUS_NONE ||
				$this->Status === self::STATUS_COMPLEATED) {
				return true;
			}
			return false;
		}
		$settionStatus = $this->getSession()->getParamValue('mailform_status');
		if ($this->Status === self::STATUS_NONE) {
			return true; // どこからでもOK
		} else if ($this->Status === self::STATUS_INPUT) {
			return true; // どこからでもOK
		} else if ($this->Status === self::STATUS_COMPLEATED) {
			return true; // どこからでもOK
		} else if ($this->Status === self::STATUS_CONFIRMATION) {
			if (!isset($this->RequestParam)) {
				return false;
			}
			if ($settionStatus === self::STATUS_INPUT ||
				$settionStatus === self::STATUS_CONFIRMATION ||
				$settionStatus === self::STATUS_COMPLEATE) {
				return true;
			}
		} else if ($this->Status === self::STATUS_COMPLEATE) {
			if (!isset($this->RequestParam) || !$this->getSession()->isSetParam('item_values')) {
				return false;
			}
			if ($settionStatus === self::STATUS_CONFIRMATION ||
				$settionStatus === self::STATUS_COMPLEATE) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 必要なデータをセッションに保存する。
	 *
	 */
	protected function setParamToSession() {
		if ($this->getSession()->isSetParam('item_values')) {
			$param = $this->getSession()->getParamValue('item_values');
			$items = $this->FormConfig['items'];
			foreach ($items as $key => &$values) {
				if (!isset($values['value']) || $values['value'] !== 'file') {
					continue;
				}
				// ファイルは前のデータをマージ
				if (!isset($this->RequestParam[$key]) || empty($this->RequestParam[$key])) {
					if ( isset($param[$key]) && !empty($param[$key]) ) {
						$this->RequestParam[$key] = $param[$key];
					}
				}
			}
		}

		$this->getSession()->setParam('item_values', $this->RequestParam);
		$this->getSession()->setParam('error_messages', $this->ErrorMessages);
	}


	/**
	 * 必要なデータをセッションからロードする。
	 *
	 */
	protected function loadParamFromSession() {
		if ($this->getSession()->isSetParam('item_values'))
			$this->RequestParam = $this->getSession()->getParamValue('item_values');
		if ($this->getSession()->isSetParam('error_messages'))
			$this->ErrorMessages = $this->getSession()->getParamValue('error_messages');
	}

	/**
	 * 添付ファイル一時フォルダのパスをセッションに保存する。
	 *
	 */
	protected function setTempDirPathToSession() {
		$date = new DateTime();
		$filebasepath = $date->format('YmdHis');
		$time = microtime();
		$time_list = explode(' ',$time);
		$time_micro = explode('.',$time_list[0]);
		$filebasepath = $this->TmpDirectryPath.$filebasepath.substr($time_micro[1],0,3).'/';
		$this->getSession()->setParam('mailform_temp_dir_path', $filebasepath);
	}

	/**
	 * 添付ファイル一時フォルダのパスを取得する。
	 * まだセッションに設定してない場合は設定する。
	 *
	 * @return string 添付ファイル一時フォルダのパス
	 */
	protected function getTempDirPathFromSession() {
		if (!$this->getSession()->isSetParam('mailform_temp_dir_path')) {
			$this->setTempDirPathToSession();
		}
		return $this->getSession()->getParamValue('mailform_temp_dir_path');
	}

	/**
	 * 添付ファイルがあるか確認し、添付ファイルを添付ファイル一時フォルダ
	 * に保存する。
	 *
	 */
	protected function moveUploadFile() {
		$filebasepath = $this->getTempDirPathFromSession();
		$items = $this->FormConfig['items'];
		
		foreach ($items as $key => &$values) {
			if (!isset($values['value']) || $values['value'] !== 'file') {
				continue;
			}
			if ($_FILES && is_uploaded_file($_FILES[$key]['tmp_name'])) {
				if (!is_dir($filebasepath)) {
					if (!mkdir($filebasepath, 0700, true)) {
						$this->setErrorMessage($key, self::ERROR_CODE_TEMP_FILE);
					}
				}
				$value = MailForm::getEscape($_FILES[$key]['name']);
				$path = $filebasepath.$value;
				if (!move_uploaded_file($_FILES[$key]['tmp_name'], $path)) {
					$this->setErrorMessage($key, self::ERROR_CODE_TEMP_FILE);
				}
				$this->RequestParam[$key]=$value;
			}
		}
	}

	/**
	 * セッションに保存したデータをクリアする。
	 * 添付ファイル一時フォルダも削除する。
	 *
	 */
	protected function unsetSession() {
		$filebasepath = $this->getTempDirPathFromSession();
		if (is_dir($filebasepath)) {
			system("rm -rf {$filebasepath}");
		}
		$this->getSession()->unnsetParam('item_values');
		$this->getSession()->unnsetParam('mailform_temp_dir_path');
	}
	
	/**
	 * 指定されたURLにリダイレクトする。
	 *
	 * @param string $url URL
	 */
	protected function redirect($url) {
		header('Location: ' . $url);
	}
	
	/**
	 * 処理実行する。
	 *
	 */
	public function execute() {
		$url = $_SERVER['SCRIPT_NAME'];
		if ($this->IncludeTemplateURL) {
			$this->main();
		} else {
			if (substr($url, 0, strcspn($url,'?')) === $this->ErrorURL ) {
				$this->loadParamFromSession();
				return;
			}
			if ($this->Status !== self::STATUS_COMPLEATE && substr($url, 0, strcspn($url,'?')) === $this->CompleteURL ) {
				$this->showCompletePage();
				return;
			}
			$this->main();
			// TemplateURLが変わった場合、入力ページに飛ばす
			if (substr($url, 0, strcspn($url,'?')) !== $this->getTemplateURL() ) {
				$this->redirect($this->getTemplateURL());
				return;
			}
		}
	}
	
	/**
	 * 処理実行する。
	 *
	 */
	protected function main() {
		if (empty($this->FormConfig)) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			$this->showErrorPage();
			return;
		}
		if (!$this->isCorrectAccess()) {
			// 不正な場合はクエリ無しでリロード
			$this->unsetSession();
			$this->redirect($_SERVER['REQUEST_URI']);
			return;
		}
		if ($this->Status === self::STATUS_NONE) {
			$this->Status = self::STATUS_INPUT;
			$this->unsetSession();
		}

		if ($this->Status === self::STATUS_CONFIRMATION) {
			$this->moveUploadFile();
			$this->checkRequestParams();
			$this->setParamToSession();
		} else if ($this->Status === self::STATUS_COMPLEATE) {
			$this->loadParamFromSession();
			if ($this->checkRequestParams() === true) {
				$this->sendMail();
			}
		} else {
			$this->loadParamFromSession();
		}

		if ($this->isError()) {
			$this->setParamToSession();
			$this->showErrorPage();
			return;
		}

		if ($this->Status === self::STATUS_CONFIRMATION) {
			$this->showConfirmPage();
		} else if ($this->Status === self::STATUS_COMPLEATE) {
			$this->showCompletePage();
		} else {
			$this->showInputPage();
		}
	}

	/**
	 * ユーザ送信データの指定されたキー名の値を返す。
	 * ※ HTMLに表示する時は、エスケープが必要です。
	 *
	 * @param string $name キー名
	 * @return string 指定されたキーの値
	 */
	public function getValue($name) {
		if (!isset($this->RequestParam[$name])) {
			return '';
		}
		return $this->RequestParam[$name];
	}

	/**
	 * 指定されたselectvalues設定を返す。
	 *
	 * @param string $name キー名
	 * @return array selectvaluesの配列
	 */
	public function getSelectValues($name) {
		if (!isset($this->FormConfig['items']) ||
			!isset($this->FormConfig['items'][$name]) ||
			!isset($this->FormConfig['items'][$name]['selectvalues'])) {
			return NULL;
		}
		return $this->FormConfig['items'][$name]['selectvalues'];
	}

	/**
	 * 指定されたselectvaluesの項目の値を返す。
	 *
	 * @param string $name キー名
	 * @param string $value selectvaluesの項目名
	 * @return string 値
	 */
	public function getSelectValue($name, $key) {
		$selectvalues = $this->getSelectValues($name);
		if (!$selectvalues || !isset($selectvalues[$key])) return '';
		return $selectvalues[$key];
	}

	/**
	 * 指定された選択フォームのselect optionフォームのHTMLを作成する。
	 *
	 * 設定されているselectvaluesの値から下記のようなHTMLを作成する。
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 * <option value="キー">値</option>
	 *
	 * @param string $name キー名
	 * @return string select optionフォームのHTML
	 */
	public function getSelectOptionHtml($name) {
		$selectvalues = $this->getSelectValues($name);
		if (!$selectvalues) return '';

		$selectedvalue = $this->getValue($name);
		$ret = '';
		foreach($selectvalues as $key => $value) {
			if ($key == $selectedvalue) {
				$ret.= '<option value="'.$key.'" selected="selected" >'.$value."</option>\n";
			} else {
				$ret.= '<option value="'.$key.'">'.$value."</option>\n";
			}
		}
		return $ret;
	}

	/**
	 * 選択項目の中にキーが存在するかを返す。
	 *
	 * @param string $value キー名
	 * @param string $name 項目名
	 * @return bool 存在有無
	 */
	public function isExistKeyInSelectValues($value, $name) {
		$selectvalues = $this->getSelectValues($value);
		if (!$selectvalues) return false;
		return array_key_exists($name, $selectvalues);
	}

	/**
	 * 指定された選択フォームの値を返す。
	 *
	 * @param string $name キー名
	 * @return string 指定された選択フォームの値
	 */
	public function getSelectedValue($name) {
		$key = $this->getValue($name);
		if ( empty($key) ) {
			$key = 'empty';
		}
		$selectvalues = $this->getSelectValues($name);
		if (!$selectvalues || !isset($selectvalues[$key])) return '';

		return $selectvalues[$key];
	}

	/**
	 * 指定された複数選択フォームの値をデリミタで結合して返す。
	 * デリミタを変更した場合は、MultiSelectedDelimiterを設定してください。
	 *
	 * @param string $name キー名
	 * @return string 指定された複数選択フォームの値
	 */
	public function getMultiSelectedValue($name) {
		$selectvalues = $this->getSelectValues($name);
		if (!$selectvalues) return '';

		$keys = $this->getValue($name);
		if (!is_array($keys)) return '';

		$values = '';
		foreach ($keys as $key) {
			if (!isset($selectvalues[$key])) {
				continue;
			}
			if (strlen($values) > 0) {
				$values .= $this->MultiSelectedDelimiter;
			}
			$values .= $selectvalues[$key];
		}
		return $values;
	}

	/**
	 * 選択されたキーかどうかを返します。
	 *
	 * @param string $name キー名
	 * @param string $value selectvaluesの項目名
	 * @param bool $default デフォルトで選択済みにする項目かどうか
	 * @return bool 選択の有無
	 */
	public function isSelectedValue($name, $key, $default=false) {
		$keys = $this->getValue($name);
		if (!$keys) {
			return $default;
		}
		if (!is_array($keys)) {
			return $keys === $key;
		}
		return in_array($key, $keys);
	}

	/**
	 * 正数かどうかを返す。
	 *
	 * @param string $value 確認する値
	 * @return bool 正数の場合、true
	 */
	protected function isPlusNumber($value) {
		return preg_match('/^[0-9]+$/',strval($value)) == 1;
	}

	/**
	 * メールアドレスかどうかを返す。
	 *
	 * @param string $value 確認する値
	 * @return bool メールアドレスの場合、true
	 */
	protected function isEmailAddress($value) {
		if (!$value) return false;
		$value = trim($value);
		return filter_var($value, FILTER_VALIDATE_EMAIL) == $value;
	}

	/**
	 * カナかどうかを返す。
	 *
	 * @param string $value 確認する値
	 * @return bool カナの場合、true
	 */
	protected function isKana($value) {
		return preg_match('/^[ァ-ヾ 　]+$/u',$value) == 1;
	}

	/**
	 * よみかどうかを返す。
	 *
	 * @param string $value 確認する値
	 * @return bool よみの場合、true
	 */
	protected function isYomi($value) {
		return preg_match('/^[ぁ-ん　]+$/u',$value) == 1;
	}

	/**
	 * 電話番号かどうかをかえす
	 *
	 * @param string $value 確認する値
	 * @return bool 電話番号の場合、true
	 */
	protected function isTel($value) {
		return preg_match('/^[0-9+-]+/u',$value) == 1;
	}

	/**
	 * 全角に変換する
	 *
	 * @param string $value 変換元
	 * @return bool 変換後の値
	 */
	protected function convToTwoByte($value) {
		return mb_convert_kana($value, 'KVRNA');
	}

	/**
	 * 半角に変換する
	 *
	 * @param string $value 変換元
	 * @return bool 変換後の値
	 */
	protected function convToOneByte($value) {
		$value = mb_convert_kana($value, 'as');
		return str_replace('ー', '-', $value);
	}

	/**
	 * 数字のみに変換する。
	 * 数字以外は削除する。
	 *
	 * @param string $value 変換元
	 * @return bool 変換後の値
	 */
	protected function convToPlus($value) {
		$value = mb_convert_kana($value, 'rn');
		return mb_ereg_replace('[^0-9]', '', $value);
	}

	/**
	 * ユーザ送信データの中身をconvパラメータにしたがって変換する。
	 *
	 * @param string $type タイプ
	 * @param string $value 値
	 * @return string 変換結果
	 */
	protected function convertRequestParam($type, &$value) {
		if ($type === 'two-byte') {
			return $this->convToTwoByte($value);
		} else if ($type === 'one-byte') {
			return $this->convToOneByte($value);
		} else if ($type === 'plus') {
			return $this->convToPlus($value);
		}
		return $value;
	}

	/**
	 * ユーザ送信データの中身をチェックする。
	 *
	 * @return bool 正常かどうか
	 */
	protected function checkRequestParams() {
		$ret = true;
		$items = $this->FormConfig['items'];
		foreach ($items as $key => &$values) {
			if (!$this->checkRequestParam($ret, $key, $values)) {
				return false;
			}
		}
		return $ret;
	}

	/**
	 * ユーザ送信データの各値をチェックする。
	 *
	 * @return bool 正常かどうか
	 */
	protected function checkRequestParam(&$ret, $key, &$config) {
		if (!isset($config['value']) || !isset($config['empty']) || !isset($config['title'])) {
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
			return false;
		}

		$inputValue = '';
		if (isset($this->RequestParam[$key])) {
			$inputValue = $this->RequestParam[$key];
		}

		$type = $config['value'];
		if (!isset($inputValue) || empty($inputValue)) {
			if ($type === 'agreement') {
				$this->setErrorMessage($key, self::ERROR_CODE_NO_AGREEMENT, $config['title']);
				$ret = false;
			} else if ($config['empty'] === false) {
				if ($type == 'select' || $type == 'multi-select') {
					$this->setErrorMessage($key, self::ERROR_CODE_NEED_SELECT_PARAM, $config['title']);
				} else {
					$this->setErrorMessage($key, self::ERROR_CODE_NOT_NULL_PARAM, $config['title']);
				}
				$ret = false;
			}
			return true;
		}

		if (gettype($this->RequestParam[$key]) !== 'string' &&
			(!is_array($this->RequestParam[$key]) || $type !== 'multi-select')) {
			$this->setErrorMessage($key, self::ERROR_CODE_INVALID_PARAM, $config['title']);
			$ret = false;
			return true;
		}

		if (isset($config['conv'])) {
			$inputValue = $this->convertRequestParam($config['conv'], $inputValue);
			$this->RequestParam[$key] = $inputValue;
		}

		if (isset($config['same'])) {
			$keyname = $config['same'];
			$sameInputValue = '';
			if (isset($this->RequestParam[$keyname])) {
				$sameInputValue = $this->RequestParam[$keyname];
			}
			if ($inputValue !== $sameInputValue) {
				$sameTitle = $this->FormConfig['items'][$keyname]['title'];
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_SAME_PARAM, $config['title'], $sameTitle);
				$ret = false;
				return true;
			}
		}

		if (isset($config['maxlength'])) {
			if (mb_strlen($inputValue) > $config['maxlength']) {
				$this->setErrorMessage($key, self::ERROR_CODE_OVER_MAXLENGTH, $config['title'], $config['maxlength']);
				$ret = false;
				return true;
			}
		}

		if ($type === 'select') {
			if (!isset($config['selectvalues'])) {
				$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
				return false;
			}
			if (!isset($config['selectvalues'][$inputValue])) {
				$this->setErrorMessage($key, self::ERROR_CODE_INVALID_SELECT_PARAM, $config['title']);
				$ret = false;
			}
			return true;
		}
		if ($type === 'multi-select') {
			if (!isset($config['selectvalues'])) {
				$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
				return false;
			}
			$array = $this->RequestParam[$key];
			if (!is_array($array)) {
				$this->setErrorMessage($key, self::ERROR_CODE_INVALID_SELECT_PARAM, $config['title']);
				$ret = false;
				return true;
			} 
			foreach ($array as $value) {
				if (!isset($config['selectvalues'][$value])) {
					$this->setErrorMessage($key, self::ERROR_CODE_INVALID_SELECT_PARAM, $config['title']);
					$ret = false;
				}
			}
			return true;
		}
		if ($type === 'kana') { // kana
			if(!$this->isKana($inputValue)) {
				// 全角カナでない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_KANA_PARAM, $config['title']);
				$ret = false;
			}
			return true;
		} 
		if ($type === 'yomi') { // kana
			if(!$this->isYomi($inputValue)) {
				// 全角かなでない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_YOMI_PARAM, $config['title']);
				$ret = false;
			}
			return true;
		} 
		if ($type === 'plus') { // plus
			if(!$this->isPlusNumber($inputValue)) {
				// 正数でない場合
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_PLUS_PARAM, $config['title']);
				$ret = false;
			}
			return true;
		} 
		if ($type === 'tel') { // tel
			if(!$this->isTel($inputValue)) {
				$this->setErrorMessage($key, self::ERROR_CODE_TEL_PARAM, $config['title']);
				$ret = false;
			}
			return true;
		} 
		if ($type === 'email') { // email
			if (!$this->isEmailAddress($inputValue)) {
				$this->setErrorMessage($key, self::ERROR_CODE_NOT_EMAIL_PARAM, $config['title']);
				$ret = false;
			}
			return true;
		}
		if ($type === 'file') {
			$filebasepath = $this->getTempDirPathFromSession();
			$filepath = $filebasepath.$inputValue;
			if (!file_exists($filepath)) {
				$this->setErrorMessage($key, self::ERROR_CODE_TEMP_FILE);
				return false;
			}
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if($finfo === false) {
				$this->setErrorMessage($key, self::ERROR_CODE_TEMP_FILE);
				return false;
			}

			$mimetype = finfo_file($finfo, $filepath);
			finfo_close($finfo);
			if (isset($config['filetypes']) && !empty($config['filetypes']) && !in_array($mimetype, $config['filetypes'])) {
				$this->setErrorMessage($key, self::ERROR_CODE_UNKOWN_FILE_FORMAT, $this->getValue($key));
				$ret = false;
			}
			return true;
		} 
		if ($type === 'agreement') {
			if (!isset($config['equal'])) {
				$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_INVALID_FORM_CONFIG);
				return false;
			}
			if ($inputValue !== $config['equal']) {
				$this->setErrorMessage($key, self::ERROR_CODE_NO_AGREEMENT, $config['title']);
				$ret = false;
			}
			return true;
		}
		return true;
	}

	/**
	 * ユーザ送信データからデータを作成しメールを送信する。
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
	 * ユーザ送信データからデータを作成しメールを送信する。
	 *
	 * @param integer $index メール設定のindex
	 * @return bool 成功有無
	 */
	protected function sendMailByIndex($index) {
		$mail = $this->getMailer();
		$mail->setFromEncoding($this->FromEncoding);
		$mail->setCharSet($this->CharSet);
		$mail->setEncode( $this->MailEncoding);

		$from = $this->getFromArrayByIndex($index);
		if (count($from) > 0) {
			foreach($from as $address => &$name) {
				$address = $this->replaceFieldTag($address);
				if(strlen($address) > 0) {
					$mail->setFrom($address, $name);
				}
			}
		} else if ( $this->DefaultEmailAddress ) {
			$mail->setFrom( $this->DefaultEmailAddress );
		}

		$returnpaths = $this->getReturnPathArrayByIndex($index);
		if (count($returnpaths) > 0) {
			foreach($returnpaths as $address => &$name) {
				$mail->setReturnPath($address, $name);
			}
		}

		$tos = $this->getToArrayByIndex($index);
		$count = 0;
		foreach($tos as $address => &$name) {
			$address = $this->replaceFieldTag($address);
			if (isset($address)) {
				$mail->addAddress($address, $name);
				$count++;
			}
		}
		if ($count === 0) {
			if ( $this->DefaultEmailAddress ) {
				$mail->addAddress($this->DefaultEmailAddress);
			} else {
				$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_NOSET_TO);
				return false;
			}
		}

		$mail->setSubject($this->getCompleteMailSubjectByIndex($index));
		$body = $this->getCompleteMailBodyByIndex($index);
		$mail->setBody($body);
		
		$allowTempFiles = $this->getArrowTempFilesByIndex($index);
		if ($allowTempFiles) {
			$filebasepath = $this->getTempDirPathFromSession();
			$items = $this->FormConfig['items'];
			foreach ($items as $key => &$values) {
				if (!isset($values['value']) || $values['value'] !== 'file' ||
					!isset($this->RequestParam[$key])) {
					continue;
				}
				$filepath = $filebasepath.$this->RequestParam[$key];
				$mail->AddAttachment($filepath);
			}
		}

		if (!$mail->send()){
			$this->setErrorMessage(self::BASE_ERROR_KEY, self::ERROR_CODE_CANNOT_SEND);
			return false;
		}
		return true;
	}


	/**
	 * メール件名のテンプレートからメール件名を作成する。
	 *
	 * @param integer $index メール設定のindex
	 * @return string メール件名
	 */
	protected function getCompleteMailSubjectByIndex($index) {
		$subject = $this->getMailSubjectTemplateByIndex($index);
		return $this->getCompleteMailSubject($subject);
	}

	/**
	 * メール件名のテンプレートからメール件名を作成する。
	 *
	 * @param integer $subject メール件名のテンプレート
	 * @return string メール件名
	 */
	protected function getCompleteMailSubject($subject) {
		return $this->replaceFieldTag($subject);
	}

	/**
	 * ユーザ送信データからメッセージ本文を作成する。
	 *
	 * @param integer $index メール設定のindex
	 * @return string メッセージ本文
	 */
	protected function getCompleteMailBodyByIndex($index) {
		$body = $this->getMailBodyTemplateByIndex($index);
		return $this->getCompleteMailBody($body);
	}

	/**
	 * データタイプとキーから、値を取得する。
	 *
	 * @param string $key キー名
	 * @param string $type データータイプ
	 * @return string 指定されたキーの値
	 */
	protected function getValueByType($key, $type) {
		if ($type === 'select') {
			return $this->getSelectedValue($key);
		} else if ($type === 'multi-select') {
			return $this->getMultiSelectedValue($key);
		} else {
			return $this->getValue($key);
		}
	}

	/**
	 * ユーザ送信データからメッセージ本文を作成する。
	 *
	 * @param string $body メッセージ本文のテンプレート
	 * @return string メッセージ本文
	 */
	protected function getCompleteMailBody($body) {
		return $this->replaceFieldTag($body);
	}

	/**
	 * {{{tag}}}の部分をフィールドの値に書きかえる。
	 *
	 * @param string $value 書きかえる文字列
	 * @return 置換した文字列
	 */
	protected function replaceFieldTag($value) {
		$value = str_replace('{{{datetime}}}', $this->getDateTime(), $value);
		$items = $this->FormConfig['items'];

		$tags = $this->getFieldTags($value);
		foreach ($tags as &$tag) {
			if (!isset($items[$tag])) continue;
			$item = $items[$tag];
			$value = str_replace("{{{{$tag}}}}", $this->getValueByType($tag, $item['value']), $value);
		}
		return $value;
	}

	/**
	 * valueを解析して、{{{tag}}}のtagの配列を返します。
	 *
	 * @param string $value 解析する文字列
	 * @return array タグ配列
	 */
	protected function getFieldTags($value) {
		preg_match_all('/\{\{\{([\w\-\/:\[\]~@]+)\}\}\}/',$value,$matches);
		return $matches[1];
	}
}
?>