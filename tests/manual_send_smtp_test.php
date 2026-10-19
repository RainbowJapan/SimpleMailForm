<?php
/**
 * SMTPメール送信テストスクリプト
 *
 * SmtpMailerクラスを使用して、ポート番号や暗号化方式（TLS / SSL）の
 * 違いによるSMTP接続およびメール送信動作を確認するためのテストスクリプトです。
 *
 * 【機能概要】
 * - ポート587（TLS）およびポート465（SSL）での送信テスト
 * - 送信結果（成功/失敗）およびエラーログの標準出力
 * - 文字コードの送信パターンのテスト
 *
 * 【実行方法】
 * CLI: php examples/manual_send_test.php
 * Web: ブラウザで examples/manual_send_test.php にアクセス
 */
require_once __DIR__ . '/../lib/class.smtpmailer.php';
// =========================================================================
// 設定
// =========================================================================
$toEmail   = 'your-address@example.com'; // 【必須】受信確認できるご自身のメールアドレス
$fromEmail = 'sender@example.com';       // 【必須】差出人メールアドレス

// -------------------------------------------------------------------------
// ヘルパー関数: メーラー作成
// -------------------------------------------------------------------------
function createMailer($port, $secure)
{
	$mailer = new SmtpMailer();
	$mailer->setSSLVerifyPeer(false);
	$mailer->setSSLVerifyHost(false);
	
	$mailer->setHost('localhost');
	$mailer->setPort($port);
	$mailer->setSMTPSecure($secure);
	$mailer->setUsername('testusername');
	$mailer->setPassword('testpassword');
	
	return $mailer;
}

// -------------------------------------------------------------------------
// ヘルパー関数: テスト送信の実行とログ表示
// -------------------------------------------------------------------------
function executeTest($testNo, $title, $mailer)
{
	echo "==================================================\n";
	echo "【No.{$testNo}】 {$title}\n";
	echo "==================================================\n";
	
	$result = $mailer->send();
	
	if ($result) {
		echo "送信成功！メールボックスを確認してください。\n";
	} else {
		echo "送信失敗: [" . $mailer->getErrorCode() . "] " . $mailer->getErrorInfo() . "\n";
	}
	echo "\n";
}

// -------------------------------------------------------------------------
// テストデータ定義（文字化け・境界値チェック用）
// -------------------------------------------------------------------------
$commonData = [
'subject_long' => '【長文件名テスト】' . str_repeat('送信テスト件名が非常に長い場合のヘッダー折り返し確認用テキストです。', 3),
'body_standard' => "これは標準的な日本語テキストの送信テストです。\n改行コードや文字化けが発生していないか確認してください。",
'body_special'  => "【特殊文字・環境依存文字・絵文字テスト】\n"
. "・丸数字・記号 : ① ② ③ ㈱ ㈲ ㍑\n"
. "・旧字体・異体字: 髙橋 (はしご高), 𠮷野家 (サロゲートペア/土吉)\n"
. "・絵文字 : 🍎 🚀 👨‍💻 🎉\n"
. "・波ダッシュ : 〜 (U+301C) / ～ (U+FF5E)",
'body_long'     => "【長文折り返しテスト】\n"
. "以下は1行が非常に長いテキストです（76バイト境界での折り返し文字化けチェック）：\n"
. str_repeat('あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをん', 5) . "\n\n"
. "以下は複数行の文章です：\n"
. str_repeat("1234567890 日本語テキスト送信確認行です。\n", 10),
];

// =========================================================================
// パターン 1: tlsでの送信
// =========================================================================
$m1 = createMailer(587, 'tls');

$m1->addCc('cc@example.com', 'CCテスト');
$m1->addBcc('bcc@example.com', 'BCCテスト');
$m1->setReplyTo('reply@example.com', '返信先名');
$m1->setReturnPath('bounce@example.com', 'エラー送信先名');

$m1->setFromEncoding('UTF-8');
$m1->setCharSet('UTF-8');
$m1->setEncode('base64');
$m1->setFrom($fromEmail, 'テスト送信者(UTF8)');
$m1->addAddress($toEmail, 'テスト受信者(UTF8)');
$m1->setSubject('【No.1】tls UTF-8 / Base64 標準・特殊文字テスト、cc、bcc、replay-to、return-path');
$m1->setBody($commonData['body_standard'] . "\n\n" . $commonData['body_special']);

executeTest(1, 'tls：標準ケース (UTF-8 / base64)', $m1);

// =========================================================================
// パターン 2: sslでの送信
// =========================================================================
$m2 = createMailer(465, 'ssl');

$m2->addCc('cc@example.com', 'CCテスト');
$m2->addBcc('bcc@example.com', 'BCCテスト');
$m2->setReplyTo('reply@example.com', '返信先名');
$m2->setReturnPath('bounce@example.com', 'エラー送信先名');

$m2->setFromEncoding('UTF-8');
$m2->setCharSet('UTF-8');
$m2->setEncode('base64');
$m2->setFrom($fromEmail, 'テスト送信者(UTF8)');
$m2->addAddress($toEmail, 'テスト受信者(UTF8)');
$m2->setSubject('【No.2】ssl UTF-8 / Base64 標準・特殊文字テスト、cc、bcc、replay-to、return-path');
$m2->setBody($commonData['body_standard'] . "\n\n" . $commonData['body_special']);

executeTest(2, 'ssl：標準ケース (UTF-8 / base64)', $m2);

// =========================================================================
// パターン 3: 【標準】ssl UTF-8 × UTF-8 × base64
// =========================================================================
$m3 = createMailer(465, 'ssl');
$m3->setFromEncoding('UTF-8');
$m3->setCharSet('UTF-8');
$m3->setEncode('base64');
$m3->setFrom($fromEmail, 'テスト送信者(UTF8)');
$m3->addAddress($toEmail, 'テスト受信者(UTF8)');
$m3->setSubject('【No.3】ssl UTF-8 / Base64 標準・特殊文字テスト');
$m3->setBody($commonData['body_standard'] . "\n\n" . $commonData['body_special']);

executeTest(3, 'ssl：標準ケース (UTF-8 / base64)', $m3);

// =========================================================================
// パターン 4: 【生送信】ssl UTF-8 × UTF-8 × 8bit
// =========================================================================
$m4 = createMailer(465, 'ssl');
$m4->setFromEncoding('UTF-8');
$m4->setCharSet('UTF-8');
$m4->setEncode('8bit');
$m4->setFrom($fromEmail, '送信者(8bit)');
$m4->addAddress($toEmail, '受信者(8bit)');
$m4->setSubject('【No.2】ssl UTF-8 / 8bit 生送信テスト');
$m4->setBody("Content-Transfer-Encoding: 8bit のテストです。\n\n" . $commonData['body_long']);

executeTest(4, 'ssl：エンコードなし生送信 (UTF-8 / 8bit)', $m4);

// =========================================================================
// パターン 5: 【伝統的JIS】ssl UTF-8 × ISO-2022-JP × 7bit
// =========================================================================
$m5 = createMailer(465, 'ssl');
$m5->setFromEncoding('UTF-8');
$m5->setCharSet('ISO-2022-JP');
$m5->setEncode('7bit');
$m5->setFrom($fromEmail, 'JIS送信者(7bit)');
$m5->addAddress($toEmail, 'JIS受信者(7bit)');
$m5->setSubject('【No.3】ssl ISO-2022-JP / 7bit テスト');
$m5->setBody($commonData['body_standard'] . "\n\n※JIS変換のため絵文字や一部の特殊文字は ? 等に置換されるのが正常な挙動です。");

executeTest(5, 'ssl：伝統的JIS送信 (ISO-2022-JP / 7bit)', $m5);

// =========================================================================
// パターン 6: 【JIS＋Base64】ssl UTF-8 × ISO-2022-JP × base64
// =========================================================================
$m6 = createMailer(465, 'ssl');
$m6->setFromEncoding('UTF-8');
$m6->setCharSet('ISO-2022-JP');
$m6->setEncode('base64');
$m6->setFrom($fromEmail, 'JIS送信者(Base64)');
$m6->addAddress($toEmail, 'JIS受信者(Base64)');
$m6->setSubject('【No.4】ssl ISO-2022-JP / Base64 テスト');
$m6->setBody($commonData['body_standard']);

executeTest(6, 'ssl：JIS Base64送信 (ISO-2022-JP / base64)', $m6);

// =========================================================================
// パターン 7: 【SJISソース】ssl SJIS × UTF-8 × base64
// =========================================================================
// 入力ソースデータをShift-JISに変換してセットする模擬処理
$sjisSubject = mb_convert_encoding('【No.5】ssl SJISソースからのUTF-8変換送信テスト', 'SJIS-win', 'UTF-8');
$sjisBody    = mb_convert_encoding("Shift-JISで作成されたテキスト本文です。\n" . $commonData['body_special'], 'SJIS-win', 'UTF-8');
$sjisName    = mb_convert_encoding('SJIS差出人名', 'SJIS-win', 'UTF-8');

$m7 = createMailer(465, 'ssl');
$m7->setFromEncoding('SJIS-win');
$m7->setCharSet('UTF-8');
$m7->setEncode('base64');
$m7->setFrom($fromEmail, $sjisName);
$m7->addAddress($toEmail, '受信者');
$m7->setSubject($sjisSubject);
$m7->setBody($sjisBody);

executeTest(7, 'ssl：SJISソースデータ変換 (SJIS-win -> UTF-8 / base64)', $m7);

// =========================================================================
// パターン 8: 【長文・ヘッダー折り返し】ssl UTF-8 × UTF-8 × base64
// =========================================================================
$m8 = createMailer(465, 'ssl');
$m8->setFromEncoding('UTF-8');
$m8->setCharSet('UTF-8');
$m8->setEncode('base64');
$m8->setFrom($fromEmail, '長文テスト送信者');
$m8->addAddress($toEmail, '長文テスト受信者');
$m8->setSubject($commonData['subject_long']);
$m8->setBody($commonData['body_long']);

executeTest(8, 'ssl：長文件名・本文折り返し境界線テスト (UTF-8 / base64)', $m8);
