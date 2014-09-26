# SimpleMailForm

## メールフォームライブラリについて

SimpleMailForm は、入力 -> 確認 -> 完了のメールフォームを作るためのシンプルなPHPライブラリです。

## 特徴
* フォーム項目が簡単にできます
* 自動返信に対応しています
* セッションを使用していません
* CSRF対策を別途する必要があります

## 依存ライブラリ
* [PHPMailer]( https://github.com/PHPMailer/PHPMailer) externalディレクトリにcloneしてください
* [YAML](http://www.yaml.org/)

## 簡単な使い方
メインスクリプトとテンプレートファイルの２種類のファイルを作成します。

### 作成するファイル
* form.php メールフォームのメインスクリプト
* input.php 入力画面のテンプレート
* confirm.php 確認画面のテンプレート
* complete.php 完了画面のテンプレート

※画面のファイル名やパスは自由に変更できます。

## form.php メインスクリプトの書き方
設定用の関数を呼びだして設定していきます。


```php
<?php 
mb_language('japanese');
mb_internal_encoding('UTF-8');

require_once(dirname(__FILE__).'/../src/class.simple_mailform.php');

const MAIN_MAIL_CONFIG_KEY = 1;
const USER_MAIL_CONFIG_KEY = 2;

$mailform = new SimpleMailForm();

// メールフォームのテンプレートファイルのパスを設定
$base_url = dirname(__FILE__).'/';
$mailform->InputURL = $base_url.'input.php';
$mailform->ConfirmURL = $base_url.'confirm.php';
$mailform->CompleteURL = $base_url.'complete.php';
$mailform->ErrorURL = $base_url.'input.php';

// フォーム項目の設定
// 下記以外にもパラメータ型があります
// サンプルを確認ください
$formconfig = <<<EOD
items:  # フォーム項目情報
  username: # フォーム項目名：フォームのinputタグのnameと同じにする必要があります
    title: "名前"    # タイトル：項目のタイトルです
    value: string   # パラメータ型：パラメータの型
    empty: false    # 未入力の許可：未入力を許可するかどうかです。trueは許可、falseは許可しない
  mail:
    title: "ご連絡先メールアドレス"
    value: string
    empty: false
  detail:
    title: "お問い合わせ内容"
    value: string
    empty: false
EOD;
$mailform->setFormConfig($formconfig);

// メール設定
$mailform->setFromByIndex(MAIN_MAIL_CONFIG_KEY, '{{{mail}}}', '');   // 差出人アドレス：{{{mail}}} には、フォームのmailの値が設定されます
$mailform->addToByIndex(MAIN_MAIL_CONFIG_KEY, 'to@example.com', ''); // 宛先アドレス
$mailform->setMailSubjectTemplateByIndex(MAIN_MAIL_CONFIG_KEY, '【お問い合わせ】'); // 件名

// メール本文のテンプレートになります
// {{{フォーム項目名}}}にユーザが入力した値が入ります
$mailFormat = <<<EOD

■名前
{{{username}}}

■メールアドレス
{{{mail}}}

■お問い合わせの内容
{{{detail}}}

EOD;

$mailform->setMailBodyTemplateByIndex(MAIN_MAIL_CONFIG_KEY, $mailFormat);

// 自動返信用のメール設定
$mailform->setFromByIndex(USER_MAIL_CONFIG_KEY, 'from@example.com', '');
$mailform->addToByIndex(USER_MAIL_CONFIG_KEY, '{{{mail}}}', '');
$mailform->setMailSubjectTemplateByIndex(USER_MAIL_CONFIG_KEY, 'お問い合わせありがとうございます（自動送信メール）');
$mailFormat = <<<EOD
  省略
EOD;
$mailform->setMailBodyTemplateByIndex(USER_MAIL_CONFIG_KEY, $mailFormat);

// 実行
$mailform->execute();
?>
```

## input.php 入力画面のテンプレート
HTMLでフォームを作成します。
inputタグの名前は、フォーム項目の設定で設定したフォーム項目名を設定してください。
入力エラーの場合、入力画面テンプレートが表示されます。
入力データを引き継ぐために、inputタグのvalueにスクリプトを設定します。
usernameの場合、下記のようになります。

```php
<input type="text" name="username" value="<?php $mailfrom->echoValueForHTML('username') ?>
```

入力エラー等のエラーを表示することができます。
```php
<?php 
	if($mailform->isError()) { ?>
<div class="errorbox">
	<p>エラー</p>
	<ul>
<?php
	$errors = $mailform->getErrorMessages();
	foreach ($errors as &$message) {
echo "<li>".$message."</li>";
	}
?>	
	</ul>
</div>
```

フォーム項目名の値がエラーかどうか取得することができます。

```php
<?php $mailform->isErrorValue('username'); ?>
```

inputタグのサブミットのnameは"mailform-confirm-submit"に設定する必要があります。

```php
<input type="submit" name="mailform-confirm-submit" value="入力内容を確認する" >
```


## confirm.php 確認画面のテンプレート
確認画面のHTMLを作成します。
ユーザが入力したデータは、echoValueForHTML 関数で表示できます。

```php
<?php $mailfrom->echoValueForHTML('username') ?>
```

次の画面に移動するためには、formが必要です。
入力データを引き継ぐためにechoConfirmHiddenForm 関数をformの中で呼びだしてください。
この内容で送信するボタンのnameには、"mailform-sendmail-submit" を設定してください。

```php
<form  method="POST" action="form.php">
	<input type="reset" class="btnreset" onclick="javascript:history.back();" value="前の画面に戻って編集">
	<input type="submit" class="submit-complete" name="mailform-sendmail-submit" value="送信する" >
</form>
```

## complete.php 完了画面のテンプレート
確認画面のHTMLを作成します。
特にスクリプトを設定する必要はありません。
