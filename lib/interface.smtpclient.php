<?php

/**
 * SMTPクライアントインターフェース。
 */
interface SmtpClientInterface
{
	/**
	 * SMTPサーバーへ接続する。
	 *
	 * @param string $host       接続先のホスト名またはIPアドレス
	 * @param int    $port       ポート番号（例: 25, 465, 587）
	 * @param string $encryption 暗号化方式（例: 'tls', 'ssl', 'starttls', ''）
	 * @param string $helo       HELO/EHLOコマンドで送信する自ホスト名
	 * @param int    $timeout    接続タイムアウト時間（秒単位、デフォルトは10秒）
	 * @return bool 接続および初期通信が成功した場合はtrue
	 */
	public function connect($host, $port, $encryption, $helo, $timeout = 10);

	/**
	 * ユーザー名とパスワードを使用した認証設定。
	 *
	 * @param string $username ユーザー名
	 * @param string $password パスワード
	 * @param string $authType 認証方式（'auto', 'login', 'plain', 'cram-md5' 等、デフォルトは'auto'）
	 * @return bool 認証が成功した場合はtrue
	 */
	public function authenticatePassword($username, $password, $authType = 'auto');

	/**
	 * OAuth 2.0 アクセストークンを使用した認証設定。
	 *
	 * @param string $username    メールアドレス（アカウント名）
	 * @param string $accessToken OAuth 2.0 アクセストークン
	 * @return bool 認証が成功した場合はtrue
	 */
	public function authenticateXOAuth2($username, $accessToken);

	/**
	 * メールメッセージを送信する。
	 *
	 * @param string       $from       差出人のメールアドレス
	 * @param string|array $recipients 宛先メールアドレス（単一の文字列または配列）
	 * @param string       $message    送信するメールデータ（ヘッダーおよび本文を含むRAWデータ）
	 * @param array        $parameters 送信時オプション・追加パラメーター
	 * @return bool 送信が成功した場合はtrue
	 */
	public function sendMessage($from, $recipients, $message, $parameters = []);

	/**
	 * 接続先サーバーが特定のSMTP拡張機能（EHLO機能）をサポートしているか確認する。
	 *
	 * @param string $name 確認したい拡張機能名（例: 'STARTTLS', 'AUTH', '8BITMIME'）
	 * @return bool サポートしている場合はtrue
	 */
	public function hasExtension($name);

	/**
	 * QUITコマンドを送信し、SMTPセッションを正常に終了する。
	 *
	 * @return bool セッション終了処理が成功した場合はtrue
	 */
	public function quit();

	/**
	 * ソケット接続を強制的に閉じ、リソースを解放する。
	 *
	 * @return void
	 */
	public function close();
}

/**
 * SMTPクライアント処理における例外クラス。
 */
class SmtpClientException extends Exception
{
	protected $Stage;
	
	/**
	 * コンストラクタ
	 *
	 * @param string         $stage    エラーが発生したステージ
	 * @param string         $message  エラーメッセージ
	 * @param int            $code     エラーコード
	 * @param Exception|null $previous 遷移前の例外オブジェクト (デフォルト: null)
	 */
	public function __construct($stage, $message, $code = 0, $previous = null)
	{
		$this->Stage = $stage;
		parent::__construct($message, $code, $previous);
	}
	
	/**
	 * エラーが発生したステージを取得する
	 *
	 * @return string
	 */
	public function getStage()
	{
		return $this->Stage;
	}
}

/**
 * SmtpClientファクトリクラス。
 *
 * cURL拡張が入っている環境だけ対応
 */
class SmtpClientFactory
{
	/**
	 * SmtpClientInterface実装オブジェクトを返す。
	 *
	 * @return SmtpClientInterface
	 * @throws RuntimeException cURL拡張が無効な場合
	 */
	public static function create()
	{
		if (function_exists('curl_init')) {
			require_once __DIR__ . '/class.smtpcurlclient.php';
			return new SmtpCurlClient();
		}

		throw new RuntimeException('cURL extension is required but not installed/enabled.');
	}
}
