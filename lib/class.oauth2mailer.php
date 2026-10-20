<?php
require_once __DIR__ . '/class.smtpmailer.php';

/**
 * OAuth2トークンを管理・提供するためのインターフェース。
 */
interface OAuth2TokenProviderInterface
{
	/**
	 * OAuth2アクセストークンを取得。
	 *
	 * @return string アクセストークン文字列
	 * @throws \RuntimeException トークンの取得や更新に失敗した場合
	 */
	public function getAccessToken();
}

/**
 * 呼び出し側から渡されたアクセストークンを使用するProvider。
 */
class OAuth2AccessTokenProvider implements OAuth2TokenProviderInterface
{
	protected $AccessToken; // アクセストークン

	/**
	 * コンストラクタ
	 *
	 * @param string $accessToken 初期化時に設定するアクセストークン
	 */
	public function __construct($accessToken)
	{
		$this->AccessToken = (string)$accessToken;
	}

	/**
	 * 保持している有効なOAuth2アクセストークンを取得。
	 *
	 * @return string アクセストークン文字列
	 * @throws \RuntimeException トークンが空（未設定）の場合
	 */
	public function getAccessToken()
	{
		if ($this->AccessToken === '') {
			throw new RuntimeException('OAuth2 access token is empty');
		}

		return $this->AccessToken;
	}
}

/**
 * OAuth2TokenProviderInterfaceの実装クラス（リフレッシュトークン対応）
 *
 * リフレッシュトークンを使用してトークンエンドポイントから新しいアクセストークンを自動取得し、
 * 有効期限を管理しながら提供するプロバイダークラスです。
 */
class OAuth2RefreshTokenProvider implements OAuth2TokenProviderInterface
{

	/**
	 * @var int トークンレスポンスの最大許容バイト数（セキュリティ対策として1MBに制限）
	 */
	const MAX_TOKEN_RESPONSE_BYTES = 1048576;

	protected $TokenEndpoint;    // トークンエンドポイントのURL
	protected $ClientId;         // クライアントID
	protected $ClientSecret;     // クライアントシークレット
	protected $RefreshToken;     // リフレッシュトークン
	protected $Timeout;          // HTTPリクエストのタイムアウト秒数
	protected $AccessToken = ''; // キャッシュされた現在のアクセストークン
	protected $ExpiresAt = 0;    // アクセストークンの有効期限（UNIXタイムスタンプ）

	/**
	 * コンストラクタ
	 *
	 * 各種設定値をプロパティに格納し、URLや必須項目のバリデーションを行います。
	 *
	 * @param string \$tokenEndpoint トークンエンドポイントのURL
	 * @param string \$clientId      クライアントID
	 * @param string \$clientSecret  クライアントシークレット
	 * @param string \$refreshToken  リフレッシュトークン
	 * @param int    \$timeout       HTTPリクエストのタイムアウト秒数（デフォルト: 10秒）
	 *
	 * @throws InvalidArgumentException 設定値が不正または不足している場合
	 */
	public function __construct($tokenEndpoint, $clientId, $clientSecret, $refreshToken, $timeout = 10)
	{
		$this->TokenEndpoint = (string)$tokenEndpoint;
		$this->ClientId = (string)$clientId;
		$this->ClientSecret = (string)$clientSecret;
		$this->RefreshToken = (string)$refreshToken;
		$this->Timeout = max(1, (int)$timeout);
		$this->validateConfiguration();
	}

	/**
	 * 有効なアクセストークンを取得する
	 *
	 * キャッシュされているトークンが有効（バッファとして残り60秒以上）であればそれを返し、
	 * 期限切れまたは存在しない場合はリフレッシュトークンを用いて再取得します。
	 *
	 * @return string アクセストークン
	 *
	 * @throws RuntimeException トークンエンドポイントから正しいトークンが取得できなかった場合
	 */
	public function getAccessToken()
	{
		if ($this->AccessToken !== '' && time() < $this->ExpiresAt - 60) {
			return $this->AccessToken;
		}

		$response = $this->requestToken();
		// レスポンスの検証
		if (!isset($response['access_token']) || !is_string($response['access_token']) || $response['access_token'] === '') {
			throw new RuntimeException('OAuth2 token endpoint did not return an access token');
		}

		// キャッシュ保存と有効期限の計算
		$this->AccessToken = $response['access_token'];
		$expiresIn = isset($response['expires_in']) ? (int)$response['expires_in'] : 3600;
		$this->ExpiresAt = time() + max(60, $expiresIn);

		return $this->AccessToken;
	}

	/**
	 * トークンエンドポイントへリフレッシュトークンを送信し、新規トークンを要求する。
	 *
	 * @return array トークンエンドポイントからのJSONレスポンス（連想配列）
	 *
	 * @throws RuntimeException リクエスト失敗、サイズ超過、または不正なJSONレスポンスの場合
	 */
	protected function requestToken()
	{
		$parts = parse_url($this->TokenEndpoint);
		$host = isset($parts['host']) ? $parts['host'] : '';

		// POSTデータをURLエンコードされたクエリ文字列として生成
		$content = http_build_query(
			[
				'grant_type' => 'refresh_token',
				'client_id' => $this->ClientId,
				'client_secret' => $this->ClientSecret,
				'refresh_token' => $this->RefreshToken,
			],
			'',
			'&'
		);

		// ストリームコンテキストの設定
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => implode("\r\n", [
					'Content-Type: application/x-www-form-urlencoded',
					'Accept: application/json',
					'Content-Length: ' . strlen($content),
					'Connection: close',
				]),
				'content' => $content,
				'timeout' => $this->Timeout,
				'ignore_errors' => true,
				'follow_location' => 0,
			],
			'ssl' => [
				'verify_peer' => true,
				'verify_peer_name' => true,
				'peer_name' => $host,
				'CN_match' => $host,
				'SNI_enabled' => true,
				'SNI_server_name' => $host,
				'allow_self_signed' => false,
			],
		]);

		$body = @file_get_contents(
			$this->TokenEndpoint,
			false,
			$context,
			0,
			self::MAX_TOKEN_RESPONSE_BYTES + 1
		);
		if (function_exists('http_get_last_response_headers')) {
			$headers = http_get_last_response_headers();
		} else {
			$varName = 'http_response_header';
			$headers = isset($$varName) ? $$varName : [];
		}
		$status = $this->getHttpStatus($headers);
		if (strlen($body) > self::MAX_TOKEN_RESPONSE_BYTES) {
			throw new RuntimeException('OAuth2 token response is too large');
		}
		if ($status < 200 || $status >= 300) {
			$errorDesc = isset($response['error_description']) ? $response['error_description'] : (isset($response['error']) ? $response['error'] : 'HTTP status ' . $status);
			throw new RuntimeException('OAuth2 token endpoint returned HTTP Status [' . $status . '] ' . $errorDesc);
		}

		$response = json_decode($body, true);
		if (!is_array($response)) {
			throw new RuntimeException('OAuth2 token endpoint returned invalid JSON');
		}

		return $response;
	}

	/**
	 * HTTPレスポンスヘッダーからステータスコードを抽出する。
	 *
	 * 配列内の `HTTP/... 200 OK` のような文字列を前方一致で検索します。
	 *
	 * @param array headers http_response_header等のヘッダー配列
	 * @return int HTTPステータスコード（取得失敗時は0）
	 */
	protected function getHttpStatus($headers)
	{
		$status = 0;
		foreach ($headers as $header) {
			if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $match)) {
				$status = (int)$match[1];
			}
		}

		return $status;
	}

	/**
	 * 内部設定およびトークンエンドポイントURLのバリデーションを行う
	 *
	 * セキュリティ担保のため、URLがHTTPS（ポート443）であること、および
	 * 必須パラメーターが空でないことを確認します。
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException URLがHTTPSでない場合、または必須項目が不足している場合
	 */
	protected function validateConfiguration()
	{
		$parts = parse_url($this->TokenEndpoint);
		if ($parts === false
			|| !isset($parts['scheme'], $parts['host'])
			|| strtolower($parts['scheme']) !== 'https'
			|| isset($parts['user'])
			|| isset($parts['pass'])
			|| (isset($parts['port']) && (int)$parts['port'] > 0)) {
			throw new InvalidArgumentException('The OAuth2 token endpoint must be a valid HTTPS URL without user info or a port number');
		}
		if ($this->ClientId === '' || $this->ClientSecret === '' || $this->RefreshToken === '') {
			throw new InvalidArgumentException('OAuth2 clientID, client secret, and refresh token are required');
		}
	}
}

/**
 * OAuth2Mailer用のファクトリクラス。
 */
class OAuth2MailerFactory implements MailerFactoryInterface
{
	protected $Host;
	protected $Port;
	protected $Encryption;
	protected $Username;
	protected $TokenProvider;
	protected $AccessToken;
	protected $Helo;
	protected $Timeout;

	/**
	 * コンストラクタでOAuth2接続およびトークン設定を受け取る
	 *
	 * @param string $host ホスト名 (例: 'smtp.gmail.com')
	 * @param int $port ポート番号 (例: 587, 465)
	 * @param string $encryption 暗号化方式 ('tls', 'ssl')
	 * @param string $username 差出人メールアドレス/ユーザー名
	 * @param OAuth2TokenProviderInterface|string $tokenOrProvider OAuth2TokenProviderInterface または アクセストークン文字列
	 * @param string $helo HELO/EHLO ドメイン名
	 * @param int $timeout タイムアウト時間（秒）
	 */
	public function __construct($host, $port = 587, $encryption = 'tls', $username = '', $tokenOrProvider = null, $helo = '', $timeout = 10)
	{
		$this->Host = $host;
		$this->Port = (int)$port;
		$this->Encryption = $encryption;
		$this->Username = $username;
		$this->Helo = $helo;
		$this->Timeout = (int)$timeout;

		if ($tokenOrProvider instanceof OAuth2TokenProviderInterface) {
			$this->TokenProvider = $tokenOrProvider;
		} elseif (is_string($tokenOrProvider)) {
			$this->AccessToken = $tokenOrProvider;
		}
	}

	/**
	 * 設定済みの OAuth2Mailer インスタンスを返す
	 *
	 * @return OAuth2Mailer
	 */
	public function getInstance()
	{
		$mailer = new OAuth2Mailer();
		$mailer->setHost($this->Host);
		$mailer->setPort($this->Port);
		$mailer->setSMTPSecure($this->Encryption);
		$mailer->setHelo($this->Helo);
		$mailer->setTimeout($this->Timeout);
		$mailer->setUsername($this->Username);

		if ($this->TokenProvider !== null) {
			$mailer->setOAuth2TokenProvider($this->TokenProvider);
		} elseif ($this->AccessToken !== null) {
			$mailer->setAccessToken($this->AccessToken);
		}

		return $mailer;
	}
}

/**
 * XOAUTH2認証SMTPメール送信クラス
 *
 * SmtpMailerを拡張し、通常のパスワード認証の代わりに
 * OAuth2（XOAUTH2）アクセストークンを用いたセキュアなSMTP認証を提供するクラス。
 */
class OAuth2Mailer extends SmtpMailer
{
	protected $OAuth2TokenProvider; // アクセストークンを管理・提供するプロバイダ

	/**
	 * OAuth2トークンプロバイダを設定する
	 *
	 * 自動リフレッシュ機能などを持つプロバイダオブジェクトを外部から注入します。
	 *
	 * @param OAuth2TokenProviderInterface $provider トークンプロバイダのインスタンス
	 * @return void
	 */
	public function setOAuth2TokenProvider($provider)
	{
		$this->OAuth2TokenProvider = $provider;
	}

	/**
	 * 固定のアクセストークンを直接設定する。
	 *
	 * 単一のアクセストークンをそのまま利用するための簡易プロバイダ（OAuth2AccessTokenProvider）
	 * を内部で生成してセットします。
	 *
	 * @param string $accessToken 直接指定するアクセストークン文字列
	 * @return void
	 */
	public function setAccessToken($accessToken)
	{
		$this->OAuth2TokenProvider = new OAuth2AccessTokenProvider($accessToken);
	}

	/**
	 * SMTPクライアントに対してXOAUTH2認証を実行する。
	 *
	 * プロバイダから最新のアクセストークンを安全に取得し、
	 * ユーザー名（メールアドレス）と組み合わせて認証処理を行います。
	 *
	 * @param object $client 認証を実行するSMTPクライアントインスタンス
	 * @return void
	 *
	 * @throws SmtpClientException アクセストークンの取得に失敗した場合
	 */
	protected function authenticateClient($client)
	{
		try {
			$accessToken = $this->OAuth2TokenProvider->getAccessToken();
		} catch (Exception $exception) {
			throw new SmtpClientException('authentication', 'Unable to obtain an OAuth2 access token');
		}
		$client->authenticateXOAuth2($this->Username, $accessToken);
	}

	/**
	 * OAuth2認証に必要な設定値のバリデーションを行う。
	 *
	 * 送信元（ユーザー名）が設定されているか、および
	 * トークンプロバイダが正しくセットされているかをチェックします。
	 *
	 * @return bool|mixed 設定が正常な場合は true、異常な場合はエラー設定メソッド（setFailure）の戻り値
	 */
	protected function validateAuthenticationConfiguration()
	{
		if ($this->Username === '') {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'OAuth2 user email is required');
		}
		if (!$this->OAuth2TokenProvider instanceof OAuth2TokenProviderInterface) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'OAuth2 token provider is required');
		}

		return true;
	}
}
