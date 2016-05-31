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
	<h1>お問い合わせ添付ファイルのサンプル</h1>
	<div id="formarea">
		<form method="post" action="form.php" name="contactform" enctype="multipart/form-data">
<?php
	if($mailform->isError()) { ?>
			<div class="errorbox">
				<p>エラー</p>
				<ul>
<?php
	$errors = $mailform->getErrorMessages();
	foreach ($errors as $message) {
		__p( "					<li>".$message."</li>");
	}
?>
				</ul>
			</div>
<?php } ?>
</select>
			<div class="inputbox">
				<strong>※印は、必須</strong>
				<table>
					<tr>
						<th><p>名前<strong>※</strong></p></th>
						<td><div><input type="text" name="username" value="<?php __e($mailform->getValue('username')) ?>" /></div></td>
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
						<th><p>資料概要<strong>※</strong></p></th>
						<td>
							<textarea name="opinion" rows="5" cols="40"><?php __e($mailform->getValue('opinion')) ?></textarea>
						</td>
					</tr>
					<tr>
						<th><p>資料<strong>※</strong></p></th>
						<td><input type="file" name="docment-file" id="docment" class="btn-file"><br/><span class="caption">投稿可能なファイルタイプ）pdf / .doc（.docx）/ .xls（.xlsx）/ .ppt（.pptx）/ .zip</span></td>
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