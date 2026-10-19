<?php
require_once __DIR__ . '/class.simplemailer.php';
require_once __DIR__ . '/interface.smtpclient.php';

/**
 * SmtpMailer用のファクトリクラス。
 */
class SmtpMailerFactory implements MailerFactoryInterface
{
	protected $Host;
	protected $Port;
	protected $Encryption;
	protected $Helo;
	protected $Username;
	protected $Password;
	protected $AuthType;
	protected $Timeout;

	/**
	 * コンストラクタでSMTP接続および認証設定を受け取る
	 *
	 * @param string $host ホスト名 (例: 'smtp.example.com')
	 * @param int $port ポート番号 (例: 587, 465)
	 * @param string $encryption 暗号化方式 ('tls', 'ssl', '')
	 * @param string $username ユーザー名
	 * @param string $password パスワード
	 * @param string $authType 認証タイプ ('auto', 'login', 'plain', 'cram-md5')
	 * @param string $helo HELO/EHLO ドメイン名
	 * @param int $timeout タイムアウト時間（秒）
	 */
	public function __construct($host, $port = 587, $encryption = 'tls', $username = '', $password = '', $authType = 'auto', $helo = '', $timeout = 10)
	{
		$this->Host = $host;
		$this->Port = (int)$port;
		$this->Encryption = $encryption;
		$this->Username = $username;
		$this->Password = $password;
		$this->AuthType = $authType;
		$this->Helo = $helo;
		$this->Timeout = (int)$timeout;
	}

	/**
	 * 設定済みの SmtpMailer インスタンスを返す。
	 *
	 * @return SmtpMailer
	 */
	public function getInstance()
	{
		$mailer = new SmtpMailer();
		$mailer->setHost($this->Host);
		$mailer->setPort($this->Port);
		$mailer->setSMTPSecure($this->Encryption);
		$mailer->setHelo($this->Helo);
		$mailer->setTimeout($this->Timeout);

		if ($this->Username !== '') {
			$mailer->setUsername($this->Username);
			$mailer->setPassword($this->Password);
			$mailer->setAuthType($this->AuthType);
		}

		return $mailer;
	}
}

/**
 * SMTPメール送信クラス。
 *
 * SimpleMailer を拡張し、cURL（SMTPクライアント）を利用した
 * セキュリティ重視かつ堅牢なメール送信制御を行います。
 */
class SmtpMailer extends SimpleMailer
{
	const ERROR_NONE = '';
	const ERROR_CONFIGURATION = 'configuration'; // 設定エラー
	const ERROR_CONNECTION = 'connection';       // 接続エラー
	const ERROR_TLS = 'tls';                     // 暗号化・証明書エラー
	const ERROR_AUTHENTICATION = 'authentication'; // 認証エラー
	const ERROR_SENDER = 'sender';               // 送信元エラー
	const ERROR_RECIPIENT = 'recipient';         // 宛先エラー
	const ERROR_DATA = 'data';                   // データ送信エラー
	const ERROR_PROTOCOL = 'protocol';           // SMTPプロトコルエラー

	const ENCRYPTION_STARTTLS = 'tls'; // 明示的TLS (通常ポート 587)
	const ENCRYPTION_SMTPS = 'ssl';   // 透過的SSL/TLS (通常ポート 465)

	protected $Host = '';
	protected $Port = 587;
	protected $SMTPSecure = self::ENCRYPTION_STARTTLS;
	protected $Username = '';
	protected $Password = '';
	protected $AuthType = 'auto';
	protected $Timeout = 10;
	protected $Helo = '';
	protected $ErrorCode = self::ERROR_NONE;
	protected $SSLVerifyPeer = true;
	protected $SSLVerifyHost = true;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * ピア証明書の検証フラグを設定。
	 * @param bool $verify
	 */
	public function setSSLVerifyPeer($verify)
	{
		$this->SSLVerifyPeer = (bool)$verify;
	}

	/**
	 * ホスト名の検証フラグを設定。
	 * @param bool|int $verify
	 */
	public function setSSLVerifyHost($verify)
	{
		$this->SSLVerifyHost = (bool)$verify;
	}

	/**
	 * SMTPサーバーのホスト名を設定。
	 * @param string $host
	 */
	public function setHost($host)
	{
		$this->Host = trim((string)$host);
	}

	/**
	 * SMTPサーバーのポート番号を設定。
	 * @param int $port
	 */
	public function setPort($port)
	{
		$this->Port = (int)$port;
	}

	/**
	 * 暗号化方式を設定。
	 * @param string $secure
	 */
	public function setSMTPSecure($secure)
	{
		$this->SMTPSecure = strtolower((string)$secure);
	}

	/**
	 * SMTP認証ユーザー名を設定。
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->Username = (string)$username;
	}

	/**
	 * SMTP認証パスワードを設定。
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->Password = (string)$password;
	}

	/**
	 * SMTP認証方式を設定。
	 * @param string $authType
	 */
	public function setAuthType($authType)
	{
		$this->AuthType = strtolower((string)$authType);
	}

	/**
	 * タイムアウト秒数を設定。
	 * @param int $seconds
	 */
	public function setTimeout($seconds)
	{
		$this->Timeout = (int)$seconds;
	}

	/**
	 * HELOホスト名を設定。
	 * @param string $hostname
	 */
	public function setHelo($hostname)
	{
		$this->Helo = trim((string)$hostname);
	}

	/**
	 * 現在のエラーコードを取得。
	 * @return string
	 */
	public function getErrorCode()
	{
		return $this->ErrorCode;
	}

	/**
	 * 現在のエラー詳細情報を取得。
	 * @return string
	 */
	public function getErrorInfo()
	{
		return $this->ErrorInfo;
	}

	/**
	 * メールの送信処理を実行する
	 *
	 * @return bool 送信成功時は true、失敗時は false
	 */
	public function send()
	{
		$this->ErrorCode = self::ERROR_NONE;
		$this->ErrorInfo = '';

		// バリデーションチェック
		if (!$this->validateConfiguration()) {
			return false;
		}
		if (empty($this->To) || !isset($this->From)) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'From and at least one To address are required');
		}
		if (!$this->validateEncoding($this->Encoding)) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'Invalid encoding: ' . $this->Encoding);
		}

		$prepared = $this->prepareSmtpMessage();
		if ($prepared === false) {
			if ($this->ErrorCode === self::ERROR_NONE) {
				$this->ErrorCode = self::ERROR_CONFIGURATION;
			}
			return false;
		}

		$client = $this->createSmtpClient();

		// SmtpCurlClient などのクライアントへSSL設定
		if (method_exists($client, 'setSSLVerifyPeer')) {
			$client->setSSLVerifyPeer($this->SSLVerifyPeer);
		}
		if (method_exists($client, 'setSSLVerifyHost')) {
			$client->setSSLVerifyHost($this->SSLVerifyHost);
		}

		try {
			$client->connect($this->Host, $this->Port, $this->SMTPSecure, $this->getHeloName(), $this->Timeout);
			$this->authenticateClient($client);

			$parameters = [];
			if ($prepared['uses8bit']) {
				if ($client->hasExtension('8BITMIME')) {
					$parameters[] = 'BODY=8BITMIME';
				}
			}

			$client->sendMessage($prepared['envelopeFrom'], $prepared['recipients'], $prepared['message'], $parameters);
			$client->quit();

			return true;
		} catch (SmtpClientException $exception) {
			$client->close();
			return $this->setFailure($this->mapErrorStage($exception->getStage()), $exception->getMessage());
		} catch (Exception $exception) {
			$client->close();
			return $this->setFailure(self::ERROR_PROTOCOL, 'Unexpected SMTP error: ' . $exception->getMessage());
		}
	}

	/**
	 * クライアントのパスワード認証を実行
	 * @param mixed $client
	 */
	protected function authenticateClient($client)
	{
		$client->authenticatePassword($this->Username, $this->Password, $this->AuthType);
	}

	/**
	 * SMTPクライアントオブジェクトをファクトリから生成
	 * @return mixed
	 */
	protected function createSmtpClient()
	{
		return SmtpClientFactory::create();
	}

	/**
	 * SMTP配信用にメッセージデータ（ヘッダーと本文）を準備・成形する
	 *
	 * @return array|false 準備済みデータ配列、失敗時は false
	 */
	protected function prepareSmtpMessage()
	{
		$from = $this->formatAddress($this->From);
		$to = $this->formatAddressList($this->To);
		$cc = $this->formatAddressList($this->Cc);
		if ($from === false || $to === false || $to === '' || $cc === false) {
			return false;
		}

		$reply = null;
		if (isset($this->ReplyTo)) {
			$reply = $this->formatAddress($this->ReplyTo);
			if ($reply === false) {
				return false;
			}
		}

		$envelopeFromPair = isset($this->ReturnPath) ? $this->ReturnPath : $this->From;
		$envelopeFrom = $this->normalizeAddress($envelopeFromPair[0]);
		$recipients = $this->normalizeEnvelopeRecipients();
		if ($envelopeFrom === false || $recipients === false || empty($recipients)) {
			return false;
		}

		$convertedSubject = $this->convertToCharset($this->Subject);
		if ($this->hasHeaderBreaks($convertedSubject)) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'Subject contains a line break');
		}
		$subject = $this->encodeHeaderText($convertedSubject);
		$convertedBody = $this->convertToCharset($this->Body);
		$body = $this->encodeBody($convertedBody);
		if ($body === false) {
			return false;
		}

		$headers = [];
		$headers[] = 'Date: ' . date('r');
		$headers[] = 'Message-ID: <' . $this->createMessageId($envelopeFrom) . '>';
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-Type: text/plain; charset=' . $this->CharSet;
		$headers[] = 'Content-Transfer-Encoding: ' . $this->Encoding;
		$headers[] = 'From: ' . $from;
		$headers[] = 'To: ' . $to;
		if ($cc !== '') {
			$headers[] = 'Cc: ' . $cc;
		}
		if (!empty($reply)) {
			$headers[] = 'Reply-To: ' . $reply;
		}
		$headers[] = 'Subject: ' . $subject;

		$message = implode("\r\n", $headers) . "\r\n\r\n";
		$message .= $this->normalizeBreaks($body, "\r\n");

		return [
			'envelopeFrom' => $envelopeFrom,
			'recipients' => $recipients,
			'message' => $message,
			'uses8bit' => $this->Encoding === self::ENCODING_8BIT && $this->has8bitChars($convertedBody),
		];
	}

	/**
	 * To, Cc, Bcc からエンベロープ宛先（実際の配送先）を重複なしの配列で抽出・正規化する
	 *
	 * @return array|false 宛先メールアドレスのリスト、失敗時は false
	 */
	protected function normalizeEnvelopeRecipients()
	{
		$recipients = [];
		foreach ([$this->To, $this->Cc, $this->Bcc] as $group) {
			foreach ($group as $pair) {
				$address = $this->normalizeAddress($pair[0]);
				if ($address === false) {
					return false;
				}
				$recipients[strtolower($address)] = $address;
			}
		}
		return array_values($recipients);
	}

	/**
	 * UniqueなメッセージID（Message-ID）を生成する
	 *
	 * @param string $envelopeFrom エンベロープ送信元メールアドレス
	 * @return string 生成された Message-ID（例: "sha1文字列@example.com"）
	 */
	protected function createMessageId($envelopeFrom)
	{
		$domain = substr(strrchr($envelopeFrom, '@'), 1);
		$id = sha1(uniqid('', true) . mt_rand());
		return $id . '@' . $domain;
	}

	/**
	 * 送信前の各種設定（ホスト、文字コード、タイムアウト等）の妥当性を検証する
	 *
	 * @return bool 有効な設定であれば true、問題があれば false
	 */
	protected function validateConfiguration()
	{
		if ($this->Host === '' || !preg_match('/^[A-Za-z0-9.-]+$/', $this->Host)) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'A valid SMTP host is required');
		}
		if (!preg_match('/^[A-Za-z0-9._-]+$/', $this->CharSet)) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'Invalid message charset');
		}
		if ($this->Timeout < 1) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'SMTP timeout must be greater than zero');
		}
		if (!in_array($this->AuthType, ['auto', 'cram-md5', 'login', 'plain'], true)) {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'Invalid SMTP authentication type');
		}
		if (!$this->validateAuthenticationConfiguration()) {
			return false;
		}
		return true;
	}

	/**
	 * SMTP認証に関する設定（ユーザー名、パスワード）の妥当性を検証する。
	 *
	 * @return bool 設定が揃っていれば true、不足があれば false
	 */
	protected function validateAuthenticationConfiguration()
	{
		if ($this->Username === '' || $this->Password === '') {
			return $this->setFailure(self::ERROR_CONFIGURATION, 'SMTP username and password are required');
		}
		return true;
	}

	/**
	 * HELO/EHLO コマンドでサーバーに伝えるべきホスト名を取得・自動解決する。
	 *
	 * @return string ホスト名（解決できない場合は 'localhost.localdomain' をフォールバック）
	 */
	protected function getHeloName()
	{
		if ($this->Helo !== '') {
			return $this->Helo;
		}
		$hostname = function_exists('gethostname') ? gethostname() : false;
		return $hostname && preg_match('/^[A-Za-z0-9.-]+$/', $hostname) ? $hostname : 'localhost.localdomain';
	}

	/**
	 * 処理が失敗した際のエラーコードとエラーメッセージをクラスにセットする。
	 *
	 * @param string $code    クラス内で定義されている ERROR_* 定数
	 * @param string $message 発生したエラーメッセージ
	 * @return false 呼び出し元が return false しやすいように常に false を返す
	 */
	protected function setFailure($code, $message)
	{
		$this->ErrorCode = $code;
		$this->ErrorInfo = $this->sanitizeErrorMessage($message);
		return false;
	}

	/**
	 * エラーメッセージ内からユーザー名やパスワード（Base64版含む）を検知しマスクする。
	 *
	 * @param string $message 生のエラーメッセージ
	 * @return string マスク処理後の安全なエラーメッセージ
	 */
	protected function sanitizeErrorMessage($message)
	{
		$message = (string)$message;
		$secrets = [];

		// パスワードが短すぎる場合に汎用的なエラーメッセージまで置換されるのを防ぐ
		if (strlen($this->Password) >= 4) {
			$secrets[] = $this->Password;
			$secrets[] = base64_encode($this->Password);
		}
		if (strlen($this->Username) >= 4) {
			$secrets[] = $this->Username;
			$secrets[] = base64_encode($this->Username);
		}

		foreach ($secrets as $secret) {
			if ($secret !== '') {
				$message = str_replace($secret, '[redacted]', $message);
			}
		}
		return $message;
	}

	/**
	 * cURL（SmtpClient）から返ってきたエラーの発生ステージを、当クラス専用のエラー定数に変換する。
	 *
	 * @param string $stage cURLクライアントが検知したエラーの発生ステージ
	 * @return string マッピングされたクラスの ERROR_* 定数（未定義のものは ERROR_PROTOCOL にフォールバック）
	 */
	protected function mapErrorStage($stage)
	{
		$known = [
			self::ERROR_CONFIGURATION,
			self::ERROR_CONNECTION,
			self::ERROR_TLS,
			self::ERROR_AUTHENTICATION,
			self::ERROR_SENDER,
			self::ERROR_RECIPIENT,
			self::ERROR_DATA,
			self::ERROR_PROTOCOL,
		];
		return in_array($stage, $known, true) ? $stage : self::ERROR_PROTOCOL;
	}
}
