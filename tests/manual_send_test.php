<?php
/**
 * SimpleMailer 送信テスト・文字化け確認用スクリプト
 *
 * 【目的】
 * メール送信における文字化け・表示崩れ・エンコード処理（MIME変換）の確認を行う。
 * ※エラー系のテスト（存在しないアドレス、不正ヘッダー等）は除外しています。
 *
 * 【チェック項目】
 * 1. 各パラメータの組み合わせ（CharSet / Encoding / FromEncoding）での送信
 * 2. 日本語（ひらがな・カタカナ・漢字・英数字）の正常表示
 * 3. 機種依存文字・旧字体・サロゲートペア（①, ㈱, 髙, 𠮷野家など）の表示
 * 4. 絵文字（🍎, 🚀 など）の表示（※ISO-2022-JP変換時の挙動確認含む）
 * 5. 長文・長い件名におけるMIMEヘッダー/本文の折り返し位置での文字切れ・文字化け
 * 6. 差出人名（From）および宛先名（To）の日本語表示
 *
 * 【実行方法】
 * CLI: php examples/send_test.php
 * Web: ブラウザで examples/send_test.php にアクセス
 */
require_once __DIR__ . '/../lib/class.simplemailer.php';

// =========================================================================
// 設定
// =========================================================================
$toEmail   = 'your-address@example.com'; // 【必須】受信確認できるご自身のメールアドレス
$fromEmail = 'sender@example.com';       // 【必須】差出人メールアドレス

// 工場インスタンスの作成
$factory = new SimplemailerFactory();

// -------------------------------------------------------------------------
// ヘルパー関数: テスト送信の実行とログ表示
// -------------------------------------------------------------------------
function executeTest($testNo, $title, SimpleMailer $mailer)
{
	echo "==================================================\n";
	echo "【No.{$testNo}】{$title}\n";
	echo "==================================================\n";

	$result = $mailer->send();

	if ($result) {
		echo " STATUS : 送信成功 (OK)\n";
	} else {
		echo " STATUS : 送信失敗 (FAILED)\n";
		echo " ERROR  : " . $mailer->getErrorInfo() . "\n";
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
// パターン 1: 【標準】UTF-8 × UTF-8 × base64
// =========================================================================
$m1 = $factory->getInstance();
$m1->setFromEncoding('UTF-8');
$m1->setCharSet('UTF-8');
$m1->setEncode('base64');
$m1->setFrom($fromEmail, 'テスト送信者(UTF8)');
$m1->addAddress($toEmail, 'テスト受信者(UTF8)');
$m1->setSubject('【No.1】UTF-8 / Base64 標準・特殊文字テスト');
$m1->setBody($commonData['body_standard'] . "\n\n" . $commonData['body_special']);

executeTest(1, '標準ケース (UTF-8 / base64)', $m1);

// =========================================================================
// パターン 2: 【生送信】UTF-8 × UTF-8 × 8bit
// =========================================================================
$m2 = $factory->getInstance();
$m2->setFromEncoding('UTF-8');
$m2->setCharSet('UTF-8');
$m2->setEncode('8bit');
$m2->setFrom($fromEmail, '送信者(8bit)');
$m2->addAddress($toEmail, '受信者(8bit)');
$m2->setSubject('【No.2】UTF-8 / 8bit 生送信テスト');
$m2->setBody("Content-Transfer-Encoding: 8bit のテストです。\n\n" . $commonData['body_long']);

executeTest(2, 'エンコードなし生送信 (UTF-8 / 8bit)', $m2);

// =========================================================================
// パターン 3: 【伝統的JIS】UTF-8 × ISO-2022-JP × 7bit
// =========================================================================
$m3 = $factory->getInstance();
$m3->setFromEncoding('UTF-8');
$m3->setCharSet('ISO-2022-JP');
$m3->setEncode('7bit');
$m3->setFrom($fromEmail, 'JIS送信者(7bit)');
$m3->addAddress($toEmail, 'JIS受信者(7bit)');
$m3->setSubject('【No.3】ISO-2022-JP / 7bit テスト');
$m3->setBody($commonData['body_standard'] . "\n\n※JIS変換のため絵文字や一部の特殊文字は ? 等に置換されるのが正常な挙動です。");

executeTest(3, '伝統的JIS送信 (ISO-2022-JP / 7bit)', $m3);

// =========================================================================
// パターン 4: 【JIS＋Base64】UTF-8 × ISO-2022-JP × base64
// =========================================================================
$m4 = $factory->getInstance();
$m4->setFromEncoding('UTF-8');
$m4->setCharSet('ISO-2022-JP');
$m4->setEncode('base64');
$m4->setFrom($fromEmail, 'JIS送信者(Base64)');
$m4->addAddress($toEmail, 'JIS受信者(Base64)');
$m4->setSubject('【No.4】ISO-2022-JP / Base64 テスト');
$m4->setBody($commonData['body_standard']);

executeTest(4, 'JIS Base64送信 (ISO-2022-JP / base64)', $m4);

// =========================================================================
// パターン 5: 【SJISソース】SJIS × UTF-8 × base64
// =========================================================================
// 入力ソースデータをShift-JISに変換してセットする模擬処理
$sjisSubject = mb_convert_encoding('【No.5】SJISソースからのUTF-8変換送信テスト', 'SJIS-win', 'UTF-8');
$sjisBody    = mb_convert_encoding("Shift-JISで作成されたテキスト本文です。\n" . $commonData['body_special'], 'SJIS-win', 'UTF-8');
$sjisName    = mb_convert_encoding('SJIS差出人名', 'SJIS-win', 'UTF-8');

$m5 = $factory->getInstance();
$m5->setFromEncoding('SJIS-win');
$m5->setCharSet('UTF-8');
$m5->setEncode('base64');
$m5->setFrom($fromEmail, $sjisName);
$m5->addAddress($toEmail, '受信者');
$m5->setSubject($sjisSubject);
$m5->setBody($sjisBody);

executeTest(5, 'SJISソースデータ変換 (SJIS-win -> UTF-8 / base64)', $m5);

// =========================================================================
// パターン 6: 【長文・ヘッダー折り返し】UTF-8 × UTF-8 × base64
// =========================================================================
$m6 = $factory->getInstance();
$m6->setFromEncoding('UTF-8');
$m6->setCharSet('UTF-8');
$m6->setEncode('base64');
$m6->setFrom($fromEmail, '長文テスト送信者');
$m6->addAddress($toEmail, '長文テスト受信者');
$m6->setSubject($commonData['subject_long']);
$m6->setBody($commonData['body_long']);

executeTest(6, '長文件名・本文折り返し境界線テスト (UTF-8 / base64)', $m6);
