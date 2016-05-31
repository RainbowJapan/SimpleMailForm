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
<div id="contact">
	<h1>お問い合わせサンプル</h1>
	<div id="formarea">
		<form method="post" action="form.php" name="contactform">
			<div class="inputbox">
				<strong>※印は、必須</strong>
				<table>
					<tr>
						<th><p>名前<strong>※</strong></p></th>
						<td><div><?php __e($mailform->getValue('username')) ?></div></td>
					</tr>
					<tr>
						<th><p>メールアドレス（半角英数字）<strong>※</strong></p></th>
						<td><div><p><?php __e($mailform->getValue('mail')) ?></p></div></td>
					</tr>
					<tr>
						<th><p>お問い合わせ<strong>※</strong></p></th>
						<td>
							<?php __ebr($mailform->getValue('opinion')) ?>
						</td>
					</tr>
					<tr>
						<th><p>資料<strong>※</strong></p></th>
						<td>
							<?php __e($mailform->getValue('docment-file')) ?>
						</td>
					</tr>
				</table>
			</div>
			<ul class="btnbox cfx">
				<input type="reset" class="btnreset" onclick="javascript:history.back();" value="前の画面に戻って編集">
				<input type="submit" class="btnconf" name="mailform-complete-submit"  value="送信する">
			</ul>
		</form>
	</div><!--/formarea-->
</div><!-- /contact -->

</body>
</html>