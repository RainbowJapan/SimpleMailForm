<?php
require_once(dirname(__FILE__)."/form.php");
$requestGuard = $mailform->getRequestGuard();
$recaptchaConfig = $requestGuard ? $requestGuard->getClientConfig("recaptcha") : null;
$siteKey = isset($recaptchaConfig["site_key"]) ? $recaptchaConfig["site_key"] : "";
?><!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<title>サンプル</title>
	</head>
	<body>
		<div id="contact">
			<h1>お問い合わせサンプル</h1>
			<div id="formarea">
				<div class="inputbox">
					<strong>※印は、必須</strong>
					<table>
						<tr>
							<th><p>お問い合わせの種類<strong>※</strong></p></th>
							<td>
								<?php __p($mailform->getSelectedValue('kind')) ?>
							</td>
						</tr>
						<tr>
							<th><p>名前<strong>※</strong></p></th>
							<td><div><?php __e($mailform->getValue('username')) ?></div></td>
						</tr>
						<tr>
							<th><p>フリガナ（カタカナ）<strong>※</strong></p></th>
							<td><div><?php __e($mailform->getValue('kana')) ?></div></td>
						</tr>
						<tr>
							<th><p>メールアドレス（半角英数字）<strong>※</strong></p></th>
							<td><div><p><?php __e($mailform->getValue('mail')) ?></p></div></td>
						</tr>
						<tr>
							<th><p>電話番号（半角数字）</p></th>
							<td><div><?php __e($mailform->getValue('tel')) ?></div></td>
						</tr>
						<tr>
							<th><p>URL</p></th>
							<td><div><?php __e($mailform->getValue('url')) ?></div></td>
						</tr>
						<tr>
							<th><p>お問い合わせ<strong>※</strong></p></th>
							<td>
								<?php __ebr($mailform->getValue('opinion')) ?>
							</td>
						</tr>
						<tr>
							<th><p>希望されるサンプル</p></th>
							<td>
								<?php __e($mailform->getMultiSelectedValue('interesting_items')) ?>
							</td>
						</tr>
						<tr>
							<th><p>ご希望の時間帯</p></th>
							<td>
								<?php __e($mailform->getSelectedValue('time')) ?>
							</td>
						</tr>
					</table>
				</div>
				<div style="display: flex; gap: 10px;">
					<form action="/recaptcha/index.php" method="post">
						<div>
							<input type="hidden" name="mailform-input-submit" value="1">
							<button type="submit">
								<span>前の画面に戻って編集</span>
							</button>
						</div>
					</form>
					<form action="/recaptcha/complete.php" method="post" id="contactform">
						<input type="hidden" name="recaptchaResponse">
						<div>
							<input type="hidden" name="mailform-complete-submit" value="1">
							<button type="submit">
								<span>送信する</span>
							</button>
						</div>
					</form>
				</div>
			</div><!--/formarea-->
		</div><!-- /contact -->
		
		<script src="https://www.google.com/recaptcha/api.js?render=<?php __e($siteKey); ?>"></script>
		<script>
			document.getElementById('contactform').addEventListener('submit', function(e) {
					e.preventDefault();
					var form = this;
					
					if (typeof grecaptcha !== 'undefined' && '<?php __e($siteKey); ?>') {
						grecaptcha.ready(function() {
								grecaptcha.execute('<?php __e($siteKey); ?>', { action: 'submit' }).then(function(token) {
										form.querySelector('[name="recaptchaResponse"]').value = token;
										form.submit();
								}).catch(function() {
										form.submit();
								});
						});
					} else {
						form.submit();
					}
			});
		</script>
	</body>
</html>
