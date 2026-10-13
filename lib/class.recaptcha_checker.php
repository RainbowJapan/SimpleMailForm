<?php
require_once __DIR__ . '/interface.request_checker.php';
/**
 * ReCaptchaCheckerクラス。
 *
 * reCAPTCHA V3に対応。
 */
class ReCaptchaChecker implements RequestCheckerInterface
{
	const ERROR_CODE_NO_CONNECT_RECAPTCHA = 101;  // reCAPTCHAのAPIエラー
	const ERROR_CODE_DUPLICATE_RECAPTCHA = 102;   // reCAPTCHAで２重リクエストした場合
	const ERROR_CODE_RECAPTCHA = 103;             // reCAPTCHAでロボットと判断された場合
	
	const DEFAULT_SCORE_THRESHOLD = 0.5;
	
	private $secretKey;
	private $siteKey;
	private $scoreThreshold;
	private $errorId = null;
	
	/**
	 * コンストラクタ
	 *
	 * @param string $secretKey シークレットキー
	 * @param string $siteKey サイトキー
	 * @param float $scoreThreshold スコア閾値（nullの場合、デフォルト: 0.5）
	 */
	public function __construct($secretKey, $siteKey, $scoreThreshold)
	{
		$this->secretKey = $secretKey;
		$this->siteKey = $siteKey;
		$this->scoreThreshold = ($scoreThreshold <=0) ? self::DEFAULT_SCORE_THRESHOLD : $scoreThreshold;
	}
	
	/**
	 * フロントエンドに渡したい公開データだけを配列で返す。
	 *
	 * @return array フロントエンド用設定配列
	 */
	public function getClientConfig()
	{
		return [
			'site_key' => $this->siteKey,
		];
	}
	
	/**
	 * reCAPTCHA認証リクエストを検証する。
	 *
	 * @param array $request リクエスト配列
	 * @return bool 検証成功時true、失敗時false
	 */
	public function check($request)
	{
		$token = isset($request['recaptchaResponse']) ? $request['recaptchaResponse'] : '';
		
		$data = [
		'secret' => $this->secretKey,
		'response' => $token
		];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		
		$response = $this->executeCurl($ch);
		if (PHP_VERSION_ID < 80000) {
			curl_close($ch);
		}
		
		if ($response === false) {
			$this->errorId = self::ERROR_CODE_NO_CONNECT_RECAPTCHA;
			return false;
		}

		$res = json_decode($response, true);
		if (!is_array($res) || empty($res["success"])) {
			if (isset($res["error-codes"]) && is_array($res["error-codes"]) && in_array('timeout-or-duplicate', $res["error-codes"], true)) {
				$this->errorId = self::ERROR_CODE_DUPLICATE_RECAPTCHA;
			} else {
				$this->errorId = self::ERROR_CODE_NO_CONNECT_RECAPTCHA;
			}
			return false;
		} elseif (isset($res["score"]) && $res["score"] < $this->scoreThreshold) {
			$this->errorId = self::ERROR_CODE_RECAPTCHA;
			return false;
		}
		return true;
	}
	
	/**
	 * エラーIDを取得する。
	 *
	 * @return int|null エラーID
	 */
	public function getErrorId()
	{
		return $this->errorId;
	}

	/**
	 * cURLリクエストを実行する。（テストでモック可能にするための保護メソッド）
	 *
	 * @param resource $ch cURLハンドル
	 * @return string|bool レスポンス文字列または失敗時false
	 */
	protected function executeCurl($ch)
	{
		return curl_exec($ch);
	}
	
	/**
	 * スコア閾値を取得する。
	 *
	 * @return float
	 */
	public function getScoreThreshold()
	{
		return $this->scoreThreshold;
	}
}
