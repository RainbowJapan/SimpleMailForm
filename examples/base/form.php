<?php 
mb_language('japanese');
mb_internal_encoding('UTF-8');

require_once(dirname(__FILE__).'/../../lib/class.simple_mail_form.php');

const MAIN_MAIL_CONFIG_KEY = 1;
const USER_MAIL_CONFIG_KEY = 2;

$mailform = new SimpleMailForm();

// メールフォームのテンプレートファイルのパスを設定します
$base_url = dirname(__FILE__).'/';
$mailform->InputURL = $base_url.'input.php';
$mailform->ConfirmURL = $base_url.'confirm.php';
$mailform->CompleteURL = $base_url.'complete.php';
$mailform->ErrorURL = $base_url.'input.php';

// フォーム項目の設定です
$formconfig = <<<EOD
items:
  kind:
    title: 'お問い合わせの種類' 
    value: select
    empty: false
    selectvalues:
      1: お問い合わせ１
      2: お問い合わせ２
      3: お問い合わせ３
  username:
    title: '名前'
    value: string
    empty: false
  kana:
    title: 'フリガナ'
    value: kana
    empty: false
  mail:
    title: 'メールアドレス'
    value: email
    empty: false
  mailconf:
    title: 'メールアドレス（確認）'
    value: email
    empty: false
    same: mail
  tel:
    title: '電話番号'
    value: plus
    empty: true
  url:
    title: 'サイト'
    value: regular
    empty: true
    pattern: /^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/
  opinion:
    title: 'お問い合わせ内容'
    value: string
    empty: false
  time:
    title: 'ご希望の時間帯'
    value: radio
    empty: false
    selectvalues:
      1: 希望なし
      2: 9:00～12:00
      3: 12:00～17:00
      4: 17:00～20:00
  conf:
    title: '個人情報についての許可'
    value: agreement
    empty: false
EOD;
$mailform->setFormConfig($formconfig);

// メールフォームの内容送信用の設定
$mailform->setFromByIndex(MAIN_MAIL_CONFIG_KEY, '{{{mail}}}', '');
$mailform->addToByIndex(MAIN_MAIL_CONFIG_KEY, 'to@example.com', '');
$mailform->setMailSubjectTemplateByIndex(MAIN_MAIL_CONFIG_KEY, '【お問い合わせ】{{{kind}}}');
$mailFormat = <<<EOD

■お問い合わせ内容

【お問い合わせ】{{{kind}}}

＜送信日時＞
{{{datetime}}}

＜名前＞
{{{username}}}

＜フリガナ＞
{{{kana}}}

＜メールアドレス＞
{{{mail}}}

＜電話番号＞
{{{url}}}

＜URL＞
{{{opinion}}}

＜お問い合わせ内容＞
{{{opinion}}}

＜ご希望の時間帯＞
{{{time}}}

EOD;

$mailform->setMailBodyTemplateByIndex(MAIN_MAIL_CONFIG_KEY, $mailFormat);

// ユーザ返信用の設定
$mailform->setFromByIndex(USER_MAIL_CONFIG_KEY, 'from@example.com', '');
$mailform->addToByIndex(USER_MAIL_CONFIG_KEY, '{{{mail}}}', '');
$mailform->setMailSubjectTemplateByIndex(USER_MAIL_CONFIG_KEY, 'お問い合わせありがとうございます（自動送信メール）');
$mailFormat = <<<EOD


{{{username}}}様

お問い合わせありがとうございます。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━

＜送信日時＞
{{{datetime}}}

＜お問い合わせの種類＞
{{{kind}}}

＜名前＞
{{{username}}}

＜フリガナ＞
{{{kana}}}

＜メールアドレス＞
{{{mail}}}

＜電話番号＞
{{{url}}}

＜URL＞
{{{opinion}}}

＜お問い合わせ内容＞
{{{opinion}}}

＜ご希望の時間帯＞
{{{time}}}

EOD;
$mailform->setMailBodyTemplateByIndex(USER_MAIL_CONFIG_KEY, $mailFormat);

// 実行
$mailform->execute();
?>
