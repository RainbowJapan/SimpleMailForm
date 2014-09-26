<?php global $mailform; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="content-script-type" content="text/javascript" />
<title>サンプル</title>
</head>
<body>
<div id="content">
	<h1>お問い合わせサンプル</h1>
	<div id="formarea">
		<form method="post" action="form.php" name="contactform">
<?php 
	if($mailform->isError()) { ?>
			<div class="errorbox">
				<p>エラー</p>
				<ul>
<?php
	$errors = $mailform->getErrorMessages();
	foreach ($errors as $message) {
		echo "					<li>".$message."</li>";
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
								<?php $mailform->echoSelectOptionHtml('kind') ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><p>名前<strong>※</strong></p></th>
						<td><div><input type="text" name="username" value="<?php $mailform->echoValueForHTML('username') ?>" /></div></td>
					</tr>
					<tr>
						<th><p>フリガナ（カタカナ）<strong>※</strong></p></th>
						<td><div><input type="text" name="kana" value="<?php $mailform->echoValueForHTML('kana') ?>" /></div></td>
					</tr>
					<tr>
						<th><p>メールアドレス（半角英数字）<strong>※</strong></p></th>
						<td><div><input type="text" name="mail" value="<?php $mailform->echoValueForHTML('mail') ?>" /></div></td>
					</tr>
					<tr>
						<th><p>メールアドレス（確認）<strong>※</strong><br />（半角英数字）</p></th>
						<td><div><input type="text" name="mailconf" value="<?php $mailform->echoValueForHTML('mailconf') ?>" /></div></td>
					</tr>
					<tr>
						<th><p>電話番号（半角数字）</p></th>
						<td><input type="text" name="tel" value="<?php $mailform->echoValueForHTML('tel') ?>" /> <span class="caption">例）0312341234</span></td>
					</tr>
					<tr>
						<th><p>URL</p></th>
						<td><input type="text" name="url" value="<?php $mailform->echoValueForHTML('url') ?>" /></td>
					</tr>
					<tr>
						<th><p>お問い合わせ<strong>※</strong></p></th>
						<td>
							<textarea name="opinion" rows="5" cols="40"><?php $mailform->echoValueForHTML('opinion') ?></textarea>
						</td>
					</tr>
					<tr>
						<th><p>ご希望の時間帯</p></th>
						<td>
							<input type="radio" name="time" value="1" <?php if ($mailform->isSelected('time', '1', true)) echo "checked" ?> /><span><label for="timeno"><?php echo $mailform->getSelectValue('time', '1') ?></label></span><br />
							<input type="radio" name="time" value="2" <?php if ($mailform->isSelected('time', '2')) echo "checked" ?> /><span><label for="time1"><?php echo $mailform->getSelectValue('time', '2') ?></label></span><br />
							<input type="radio" name="time" value="3" <?php if ($mailform->isSelected('time', '3')) echo "checked" ?> /><span><label for="time2"><?php echo $mailform->getSelectValue('time', '3') ?></label></span><br />
							<input type="radio" name="time" value="4" <?php if ($mailform->isSelected('time', '4')) echo "checked" ?> /><span><label for="time3"><?php echo $mailform->getSelectValue('time', '4') ?></label></span><br />
					</td>
					</tr>
				</table>
			</div>
			<div class="infobox">
			<div class="confirmbox">
				<p class="txt">※個人情報に関する確認事項を確認してください。</p>
				<p class="btn"><input type="checkbox" id="conf" name="conf" value="確認済" /><span><label for="conf">確認しました</label></span></p>
			</div>
			<ul class="btnbox">
				<input type="reset" class="btnreset" value="リセット">
				<input type="submit" class="btnconf" name="mailform-confirm-submit"  value="入力内容を確認する">
			</ul>
		</div>

		</form>
	</div><!--/formarea-->
</div><!-- /content -->

</body>
</html>