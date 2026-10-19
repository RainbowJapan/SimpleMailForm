<?php
require_once __DIR__ . '/interface.smtpclient.php';

/**
 * cURLのSMTP機能を使用したSMTPクライアント。
 */
class SmtpCurlClient implements SmtpClientInterface
{
	protected $Host;
	protected $Port;
	protected $Encryption;
	protected $Helo;
	protected $Timeout = 10;
	protected $Username = '';
	protected $Password = '';
	protected $AuthType = 'auto';
	protected $AccessToken = '';
	protected $IsOAuth = false;
	protected $SSLVerifyPeer = true;
	protected $SSLVerifyHost = 2;

	/**
	 * SSL/TLS証明書の検証を行うかどうかを設定する。
	 *
	 * @param bool $verify
	 * @return void
	 */
	public function setSSLVerifyPeer($verify)
	{
		$this->SSLVerifyPeer = (bool)$verify;
	}

	/**
	 * SSL/TLSのホスト名検証（CURLOPT_SSL_VERIFYHOST）を設定する。
	 *
	 * @param bool|int $verify true/2で検証、false/0で無効化
	 * @return void
	 */
	public function setSSLVerifyHost($verify)
	{
		if (is_bool($verify)) {
			$this->SSLVerifyHost = $verify ? 2 : 0;
		} else {
			$this->SSLVerifyHost = (int)$verify;
		}
	}

	/**
	 * 接続設定を保持。（cURLでは実際の通信はsendMessage時に行う）。
	 *
	 * @param string $host       ホスト名
	 * @param int    $port       ポート番号
	 * @param string $encryption 暗号化方式
	 * @param string $helo       HELO/EHLO名
	 * @param int    $timeout    タイムアウト（秒）
	 * @return bool 常にtrue
	 */
	public function connect($host, $port, $encryption, $helo, $timeout = 10)
	{
		$this->Host = $host;
		$this->Port = (int)$port;
		$this->Encryption = strtolower((string)$encryption);
		$this->Helo = $helo;
		$this->Timeout = max(1, (int)$timeout);
		return true;
	}

	/**
	 * ユーザー名／パスワード認証情報を設定。
	 *
	 * @param string $username ユーザー名
	 * @param string $password パスワード
	 * @param string $authType 認証方式
	 * @return void
	 */
	public function authenticatePassword($username, $password, $authType = 'auto')
	{
		$this->Username = $username;
		$this->Password = $password;
		$this->AuthType = $authType;
		$this->IsOAuth = false;
	}

	/**
	 * OAuth 2.0 (XOAuth2) 認証情報を設定。
	 *
	 * @param string $username    ユーザー名（メールアドレス）
	 * @param string $accessToken アクセストークン
	 * @return void
	 */
	public function authenticateXOAuth2($username, $accessToken)
	{
		$this->Username = $username;
		$this->AccessToken = $accessToken;
		$this->IsOAuth = true;
	}

	/**
	 * cURLのSMTP機能を使用してメールを送信する。
	 *
	 * @param string       $from       送信者アドレス
	 * @param array|string $recipients 受信者アドレス（配列または単一の文字列）
	 * @param string       $message    送信するメッセージ全文（RAW）
	 * @param array        $parameters 追加パラメータ
	 * @throws SmtpClientException
	 * @return void
	 */
	public function sendMessage($from, $recipients, $message, $parameters = [])
	{
		// 多層防御: SMTPコマンドインジェクション防止
		if (preg_match('/[\r\n]/', $from)) {
			throw new SmtpClientException('sender', 'Sender address contains a line break');
		}

		// 宛先のバリデーション
		$formattedRecipients = [];
		$recipients = is_array($recipients) ? $recipients : [$recipients];
		foreach ($recipients as $r) {
			if (preg_match('/[\r\n]/', $r)) {
				throw new SmtpClientException('recipient', 'Recipient address contains a line break');
			}
			$formattedRecipients[] = '<' . $r . '>';
		}

		$isSsl = ($this->Encryption === 'ssl' || $this->Encryption === 'smtps');
		$isTls = ($this->Encryption === 'tls' || $this->Encryption === 'starttls');

		$scheme = $isSsl ? 'smtps' : 'smtp';
		$url = $scheme . '://' . $this->Host . ':' . $this->Port;

		$ch = curl_init();
		if ($ch === false) {
			throw new SmtpClientException('connection', 'Failed to initialize cURL');
		}
		
		$fp = fopen('php://temp', 'r+');
		if ($fp === false) {
			curl_close($ch);
			throw new SmtpClientException('internal', 'Failed to create memory stream');
		}
		
		try {
			fwrite($fp, $message);
			rewind($fp);
			
			$messageSize = strlen($message);

			$options = [
				CURLOPT_URL            => $url,
				CURLOPT_TIMEOUT        => $this->Timeout,
				CURLOPT_SSL_VERIFYPEER => $this->SSLVerifyPeer,
				CURLOPT_SSL_VERIFYHOST => $this->SSLVerifyHost,
				CURLOPT_MAIL_FROM      => '<' . $from . '>',
				CURLOPT_MAIL_RCPT      => $formattedRecipients,
				CURLOPT_UPLOAD         => true,
				CURLOPT_INFILESIZE     => $messageSize,
				CURLOPT_INFILE         => $fp,
			];

			// STARTTLS の明示的指定
			if ($isTls) {
				$options[CURLOPT_USE_SSL] = defined('CURLUSESSL_ALL') ? constant('CURLUSESSL_ALL') : 3;
			}

			// 認証設定
			if ($this->IsOAuth) {
				$options[CURLOPT_USERNAME] = $this->Username;
				if (defined('CURLOPT_XOAUTH2_BEARER')) {
					$options[CURLOPT_XOAUTH2_BEARER] = $this->AccessToken;
				}
				if (defined('CURLOPT_LOGIN_OPTIONS')) {
					$options[CURLOPT_LOGIN_OPTIONS] = 'AUTH=XOAUTH2';
				}
			} elseif ($this->Username !== '') {
				$options[CURLOPT_USERNAME] = $this->Username;
				$options[CURLOPT_PASSWORD] = $this->Password;

				// AuthTypeに応じたcURLの認証方式指定 (cURL 7.34.0+)
				if (defined('CURLOPT_LOGIN_OPTIONS') && $this->AuthType !== 'auto') {
					// PHP 5.6などの古い環境ではこの中を通らないので安全
					$loginOptionsConst = constant('CURLOPT_LOGIN_OPTIONS');

					$authMethods = [
					'plain'    => 'AUTH=PLAIN',
					'login'    => 'AUTH=LOGIN',
					'cram-md5' => 'AUTH=CRAM-MD5',
					];
					if (isset($authMethods[$this->AuthType])) {
						$options[$loginOptionsConst] = $authMethods[$this->AuthType];
					}
				}
			}

			curl_setopt_array($ch, $options);
			$result = curl_exec($ch);
			$errno = curl_errno($ch);
			$errorMsg = curl_error($ch);

			if (!$result || $errno !== 0) {
				$stage = $this->determineErrorStage($errno);
				throw new SmtpClientException($stage, 'cURL SMTP request failed (' . $errno . '): ' . $errorMsg);
			}
		} finally {
			// 古いPHP環境向けに安全に閉じる（エラー制御演算子 @ を付与）
			if (is_resource($ch) || $ch instanceof CurlHandle) {
				@curl_close($ch);
			}
			fclose($fp);
		}
	}

	/**
	 * SMTP拡張のサポート状況を確認する。
	 *
	 * @param string $name 拡張機能名
	 * @return bool
	 */
	public function hasExtension($name)
	{
		// cURL は内部で SMTPUTF8 などをある程度ハンドリングしますが、
		// SMTPUTF8パラメーターを明示的に送出するため、EAI対応版として振る舞うなら true にする制御も可能です。
		// 今回は基本機能を優先してそのままにします。
		if (strcasecmp($name, 'SMTPUTF8') === 0 || strcasecmp($name, '8BITMIME') === 0) {
			// SMTPUTF8等パラメータをcURLで自力追加するのは困難なので false とし、cURLに任せる
			return false;
		}
		return false;
	}

	/**
	 * QUITコマンド処理（cURLが自動管理するため何もしない）
	 *
	 * @return void
	 */
	public function quit()
	{
		// cURL は接続終了を自動管理するため不要
	}

	/**
	 * 接続の明示的な終了処理（cURLが自動管理するため何もしない）
	 *
	 * @return void
	 */
	public function close()
	{
		// リソースはメソッド内で閉じているため不要
	}

	/**
	 * cURLのエラー番号から適切なエラーのステージを判定する
	 */
	protected function determineErrorStage($errno)
	{
		// 認証エラー（ログイン拒否）
		$loginDeniedCode = defined('CURLE_LOGIN_DENIED') ? constant('CURLE_LOGIN_DENIED') : 67;
		if ($errno === $loginDeniedCode) {
			return 'authentication';
		}

		// TLS関連エラーの判定（環境によって存在しない定数を安全にチェック）
		$tlsErrors = [];
		if (defined('CURLE_PEER_FAILED_VERIFICATION')) {
			$tlsErrors[] = constant('CURLE_PEER_FAILED_VERIFICATION');
		}
		if (defined('CURLE_SSL_CONNECT_ERROR')) {
			$tlsErrors[] = constant('CURLE_SSL_CONNECT_ERROR');
		}
		if (defined('CURLE_SSL_CACERT_BADFILE')) {
			$tlsErrors[] = constant('CURLE_SSL_CACERT_BADFILE');
		}
		if (defined('CURLE_SSL_CACERT')) {
			$tlsErrors[] = constant('CURLE_SSL_CACERT');
		}

		if (in_array($errno, $tlsErrors, true)) {
			return 'tls';
		}

		// 接続自体のエラー（ホスト名未解決、拒否、タイムアウト）
		$connectionErrors = [];
		if (defined('CURLE_COULDNT_RESOLVE_HOST')) {
			$connectionErrors[] = constant('CURLE_COULDNT_RESOLVE_HOST');
		}
		if (defined('CURLE_COULDNT_CONNECT')) {
			$connectionErrors[] = constant('CURLE_COULDNT_CONNECT');
		}
		if (defined('CURLE_OPERATION_TIMEDOUT')) {
			$connectionErrors[] = constant('CURLE_OPERATION_TIMEDOUT');
		}

		if (in_array($errno, $connectionErrors, true)) {
			return 'connection';
		}

		// データの送受信失敗（認証エラーやSMTPコマンド拒否などを含む汎用エラー）
		$dataErrors = [];
		if (defined('CURLE_SEND_ERROR')) {
			$dataErrors[] = constant('CURLE_SEND_ERROR');
		}
		if (defined('CURLE_RECV_ERROR')) {
			$dataErrors[] = constant('CURLE_RECV_ERROR');
		}

		if (in_array($errno, $dataErrors, true)) {
			return 'data';
		}
		return 'protocol';
	}
}
