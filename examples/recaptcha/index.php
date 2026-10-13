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
		<div id="content">
			<h1>お問い合わせサンプル</h1>
			<div id="formarea">
				<form method="post" action="/recaptcha/confirm.php" id="contactform">
					<?php
					if ($mailform->getErrorMessages()) { ?>
						<div class="errorbox">
							<p>エラー</p>
							<ul>
								<?php
								$errors = $mailform->getErrorMessages();
						foreach ($errors as $message) {
							__p("<li>".$message."</li>");
						}
						?>
							</ul>
						</div>
					<?php } ?>
					<div class="inputbox">
						<strong>※印は、必須</strong>
						<table>
							<tr>
								<th><p>お問い合わせの種類<strong>※</strong></p></th>
								<td>
									<select name="kind">
										<option value="">選択してください</option>
										<?php __p($mailform->getSelectOptionHtml('kind')) ?>
									</select>
								</td>
							</tr>
							<tr>
								<th><p>名前<strong>※</strong></p></th>
								<td><div><input type="text" name="username" value="<?php __e($mailform->getValue('username')) ?>" /></div></td>
							</tr>
							<tr>
								<th><p>フリガナ（カタカナ）<strong>※</strong></p></th>
								<td><div><input type="text" name="kana" value="<?php __e($mailform->getValue('kana')) ?>" /></div></td>
							</tr>
							<tr>
								<th><p>メールアドレス（半角英数字）<strong>※</strong></p></th>
								<td><div><input type="text" name="mail" value="<?php __e($mailform->getValue('mail')) ?>" /></div></td>
							</tr>
							<tr>
								<th><p>メールアドレス（確認）<strong>※</strong><br />（半角英数字）</p></th>
								<td><div><input type="text" name="mailconf" value="<?php __e($mailform->getValue('mailconf')) ?>" /></div></td>
							</tr>
							<tr>
								<th><p>電話番号（半角数字）</p></th>
								<td><input type="text" name="tel" value="<?php __e($mailform->getValue('tel')) ?>" /> <span class="caption">例）0312341234</span></td>
							</tr>
							<tr>
								<th><p>URL</p></th>
								<td><input type="text" name="url" value="<?php __e($mailform->getValue('url')) ?>" /></td>
							</tr>
							<tr>
								<th><p>お問い合わせ<strong>※</strong></p></th>
								<td>
									<textarea name="opinion" rows="5" cols="40"><?php __e($mailform->getValue('opinion')) ?></textarea>
								</td>
							</tr>
							<tr>
								<th><p>希望されるサンプル</p></th>
								<td>
									<?php
							$selectvalues = $mailform->getSelectValues('interesting_items');
$selectedvalues = $mailform->getValue('interesting_items');
if (!is_array($selectedvalues)) {
	$selectedvalues = [];
}
if (is_array($selectvalues)) {
	foreach ($selectvalues as $key => $value) {
		?>
											<label><input type="checkbox" name="interesting_items[]" value="<?php __e($key) ?>" <?php __pchecked(in_array($key, $selectedvalues)) ?> ><?php __p($value) ?></label><br />
											<?php
	}
}
?>
								</td>
							</tr>
							<tr>
								<th><p>ご希望の時間帯</p></th>
								<td>
									<label><input type="radio" name="time" value="1" <?php __pchecked($mailform->isSelectedValue('time', '1', true)) ?> /><?php echo $mailform->getSelectValue('time', '1') ?></label><br />
									<label><input type="radio" name="time" value="2" <?php __pchecked($mailform->isSelectedValue('time', '2')) ?> /><?php echo $mailform->getSelectValue('time', '2') ?></label><br />
									<label><input type="radio" name="time" value="3" <?php __pchecked($mailform->isSelectedValue('time', '3')) ?> /><?php echo $mailform->getSelectValue('time', '3') ?></label><br />
									<label><input type="radio" name="time" value="4" <?php __pchecked($mailform->isSelectedValue('time', '4')) ?> /><?php echo $mailform->getSelectValue('time', '4') ?></label><br />
								</td>
							</tr>
						</table>
					</div><!--/inputbox-->
					<div class="infobox">
						<div class="confirmbox">
							<p class="txt">※個人情報に関する確認事項を確認してください。</p>
							<p class="btn"><input type="checkbox" id="conf" name="conf" value="確認済" /><span><label for="conf">確認しました</label></span></p>
						</div>
						<input type="hidden" name="recaptchaResponse">
						<div class="btnbox">
							<input type="reset" class="btnreset" value="リセット">
							<input type="submit" class="btnconf" name="btnconf"  value="入力内容を確認する">
							<input type="hidden" name="mailform-confirm-submit" value="1">
						</div>
					</div><!--/infobox-->
				</form>
			</div><!--/formarea-->
		</div><!-- /content -->
		
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
