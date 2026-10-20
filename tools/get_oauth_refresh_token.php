<?php
/**
 * OAuth2.0 Refresh Token 取得スクリプト (汎用版)
 * 画面からの入力により、GoogleやMicrosoftなど任意のプロバイダのリフレッシュトークンを取得できます。
 */
session_start();

// 現在のスクリプト自身のURLを自動検出
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? 'https://' : 'http://';
$currentUrl = $protocol . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'])[0];

// ==========================================
// 各サービスからの認可コード（コールバック）受け取り処理
// ==========================================
if (isset($_GET['code'])) {
	// CSRF対策：stateの検証 念の為
	if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
		unset($_SESSION['oauth2state']);
		exit('エラー: stateが一致しません（セッション切れ、またはCSRFの可能性があります）。最初からやり直してください。');
	}

	$clientId     = $_SESSION['client_id'] ?? '';
	$clientSecret = $_SESSION['client_secret'] ?? '';
	$redirectUri  = $_SESSION['redirect_uri'] ?? $currentUrl;
	$tokenUrl     = $_SESSION['token_url'] ?? '';

	if (empty($clientId) || empty($clientSecret) || empty($tokenUrl)) {
		exit('エラー: セッション情報が見つかりません。最初からやり直してください。');
	}

	// 認可コードを使ってリフレッシュトークンを要求
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $tokenUrl);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
		'code'          => $_GET['code'],
		'client_id'     => $clientId,
		'client_secret' => $clientSecret,
		'redirect_uri'  => $redirectUri,
		'grant_type'    => 'authorization_code',
	]));

	$response = curl_exec($ch);
	$curlError = curl_error($ch);
	// 古いPHP環境向けに安全に閉じる（エラー制御演算子 @ を付与）
	if (is_resource($ch) || $ch instanceof CurlHandle) {
		@curl_close($ch);
	}

	$data = json_decode($response, true);
	?>
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<title>リフレッシュトークン取得結果</title>
		<style>
			body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; max-width: 760px; margin: 0 auto; color: #333; line-height: 1.6; }
			pre { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; overflow-x: auto; word-break: break-all; white-space: pre-wrap; }
			.btn { display: inline-block; padding: 10px 20px; background: #1a73e8; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
		</style>
	</head>
	<body>
		<h1>リフレッシュトークン取得結果</h1>
		<?php if (!empty($data['refresh_token'])): ?>
		<p style="color: #0f9d58; font-weight: bold; font-size: 1.1em;">
			成功しました！このリフレッシュトークンを保存して設定してください。
		</p>
<pre><strong>■ Refresh Token:</strong>
<?= htmlspecialchars($data['refresh_token'], ENT_QUOTES, 'UTF-8') ?>
<strong>■ Access Token (参考):</strong>
<?= htmlspecialchars($data['access_token'] ?? '', ENT_QUOTES, 'UTF-8') ?></pre>
		<?php elseif (!empty($data['access_token'])): ?>
		<p style="color: #ea4335; font-weight: bold; font-size: 1.1em;">
			アクセストークンは取得できましたが、リフレッシュトークンがありません。
		</p>

<pre><strong>■ Access Token:</strong>
<?= htmlspecialchars($data['access_token'], ENT_QUOTES, 'UTF-8') ?>

<strong>■ 全レスポンスデータ:</strong>
<?= htmlspecialchars(print_r($data, true), ENT_QUOTES, 'UTF-8') ?></pre>

		<?php else: ?>
		<p style="color: #d93025; font-weight: bold;">
			エラーが発生しました。トークンが取得できませんでした。
		</p>
		<pre><?= htmlspecialchars($response ?: $curlError, ENT_QUOTES, 'UTF-8') ?></pre>
		<?php endif; ?>
		<a href="<?= htmlspecialchars($redirectUri, ENT_QUOTES, 'UTF-8') ?>" class="btn">入力画面に戻る</a>
	</body>
</html>
<?php
		// セッションアンセット
		session_unset();
	exit;
}

// ==========================================
// フォーム送信時：認証情報保持＆認可画面へリダイレクト
// ==========================================
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$clientId     = trim($_POST['client_id'] ?? '');
	$clientSecret = trim($_POST['client_secret'] ?? '');
	$redirectUri  = trim($_POST['redirect_uri'] ?? $currentUrl);
	$authUrl      = trim($_POST['auth_url'] ?? '');
	$tokenUrl     = trim($_POST['token_url'] ?? '');
	$scope        = trim($_POST['scope'] ?? '');

	if (empty($clientId) || empty($clientSecret) || empty($redirectUri) || empty($authUrl) || empty($tokenUrl) || empty($scope)) {
		$error = 'すべての項目を入力してください。';
	} else {
		$_SESSION['oauth2state']    = bin2hex(random_bytes(16));
		$_SESSION['client_id']      = $clientId;
		$_SESSION['client_secret']  = $clientSecret;
		$_SESSION['redirect_uri']   = $redirectUri;
		$_SESSION['token_url']      = $tokenUrl;

		$authParams = [
			'client_id'     => $clientId,
			'redirect_uri'  => $redirectUri,
			'response_type' => 'code',
			'scope'         => $scope,
			'state'         => $_SESSION['oauth2state'],
			// ※Googleでリフレッシュトークンを確実に取得するためのパラメータ（他サービスでも大抵無視されるか許容されます）
			'access_type'   => 'offline',
			'prompt'        => 'consent',
		];

		header('Location: ' . $authUrl . '?' . http_build_query($authParams));
		exit;
	}
}

// ==========================================
// 初期アクセス：入力フォーム画面
// ==========================================
?>
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>汎用 OAuth2.0 リフレッシュトークン取得ツール</title>
		<style>
			body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px 20px; max-width: 700px; margin: 0 auto; color: #333; }
			.form-section { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; border-radius: 6px; margin-bottom: 24px; }
			.form-section h2 { margin-top: 0; font-size: 16px; color: #555; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 16px; }
			.form-group { margin-bottom: 16px; }
			label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 14px; }
			input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; font-family: monospace; }
			.alert { background: #fde8e8; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
			.btn-submit { width: 100%; background: #1a73e8; color: white; border: none; padding: 14px; font-size: 16px; font-weight: bold; border-radius: 4px; cursor: pointer; }
			.btn-submit:hover { background: #1557b0; }
			.info-box { background: #e8f0fe; border: 1px solid #d2e3fc; padding: 15px; border-radius: 6px; margin-bottom: 24px; font-size: 13px; color: #174ea6; }
		</style>
	</head>
	<body>
		<h1>OAuth2.0 リフレッシュトークン取得ツール</h1>

		<div class="info-box">
			<strong>現在のリダイレクト URI (プロバイダ側に登録するURL):</strong><br>
			<code style="word-break: break-all; font-weight: bold; font-size: 14px;"><?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?></code>
		</div>

		<?php if ($error): ?>
		<div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
		<?php endif; ?>

		<form method="POST" action="">

			<div class="form-section">
				<h2>1. アプリケーション認証情報</h2>
				<div class="form-group">
					<label for="client_id">Client ID</label>
					<input type="text" id="client_id" name="client_id" required placeholder="プロバイダから発行されたIDを入力">
				</div>

				<div class="form-group">
					<label for="client_secret">Client Secret</label>
					<input type="text" id="client_secret" name="client_secret" required placeholder="プロバイダから発行されたSecretを入力">
				</div>

				<div class="form-group">
					<label for="redirect_uri">Redirect URI</label>
					<input type="text" id="redirect_uri" name="redirect_uri" value="<?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?>" required>
				</div>
			</div>

			<div class="form-section">
				<h2>2. OAuth2.0 エンドポイント設定（初期値はGoogle Gmail用）</h2>
				<div class="form-group">
					<label for="auth_url">認可エンドポイントURL (Auth URL)</label>
					<input type="text" id="auth_url" name="auth_url" value="https://accounts.google.com/o/oauth2/v2/auth" required>
				</div>

				<div class="form-group">
					<label for="token_url">トークンエンドポイントURL (Token URL)</label>
					<input type="text" id="token_url" name="token_url" value="https://oauth2.googleapis.com/token" required>
				</div>

				<div class="form-group">
					<label for="scope">スコープ (Scope)</label>
					<input type="text" id="scope" name="scope" value="https://mail.google.com/" required>
				</div>
			</div>
			<button type="submit" class="btn-submit">認証プロバイダへ移動して取得</button>
		</form>
	</body>
</html>