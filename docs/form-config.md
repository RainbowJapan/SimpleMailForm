# フォームの設定

`MailForm` クラス（`class.mailform.php`）におけるフォーム設定（JSON）のパラメータ仕様、バリデーションルール、および記述例を解説するドキュメントです。

---

## 概要

フォームの設定は、JSON形式のテキストを `setFormConfig($jsonString, $templateDir, MailForm::MAILFORM_FILE_TYPE_JSON)` に渡すことで読み込みます。

JSON構造の全体像は以下の通りです。

```json
{
  "mailer": { ... },
  "mailconf": { ... },
  "request-guard": [ ... ],
  "items": { ... }
}

```

* **`mailer`**: メールの送信エンジン（SMTP/php mail）の設定
* **`mailconf`**: メールの送信先・件名・テンプレートなどのメール送信設定
* **`request-guard`**: reCAPTCHA v3 などのリクエスト検証・スパム防御設定
* **`items`**: 各フォーム項目の入力タイプ、バリデーション、自動変換などの設定

---

## 1. メールの送信エンジン設定 (`mailer`)

メール送信時に使用するメーラーのバックエンド（`SimpleMailer`, `SmtpMailer`, または `OAuth2Mailer`）を設定します。省略した場合はデフォルトで `SimpleMailer` (PHP標準の `mail()` 関数を利用) が選択されます。
ローカルの sendmail や PHP 標準の mail() 関数を使用する場合は、mailer セクションの設定は不要（省略可能）です。省略した場合はデフォルトで simple が選択されます。

### 設定例 (SMTP利用時)

```json
{
  "mailer": {
    "type": "smtp",
    "host": "smtp.example.com",
    "port": 587,
    "encryption": "tls",
    "username": "user@example.com",
    "password": "secret_password",
    "auth_type": "auto",
    "helo": "mail.example.com",
    "timeout": 10
  }
}
```

### 設定例 (OAuth2 / XOAUTH2利用時 - 例: Gmail)

Google Workspace / Gmail や Microsoft 365 などの OAuth 2.0 (XOAUTH2) 認証を利用する場合の設定です。リフレッシュトークンを使用してアクセストークンを自動取得・更新しながら送信します。

```json
{
  "mailer": {
    "type": "oauth2",
    "host": "smtp.gmail.com",
    "port": 587,
    "encryption": "tls",
    "username": "your-account@gmail.com",
    "token_endpoint": "https://oauth2.googleapis.com/token",
    "client_id": "YOUR_CLIENT_ID.apps.googleusercontent.com",
    "client_secret": "YOUR_CLIENT_SECRET",
    "refresh_token": "YOUR_REFRESH_TOKEN",
    "timeout": 10
  }
}
```

### パラメータ一覧 (`mailer`)

| キー | 型 | 必須 | デフォルト値 | 説明 |
| --- | --- | --- | --- | --- |
| `type` | string | - | `"simple"` | 送信タイプ。`"simple"`（標準 `mail()`）、`"smtp"`（SMTP直接送信）、または `"oauth2"`（OAuth2認証によるSMTP送信）を指定します。 |
| `host` | string | - | `"localhost"` | SMTPサーバーのホスト名またはIPアドレス（`type: "smtp"` または `"oauth2"` 時）。 |
| `port` | integer | - | `587` | SMTPサーバーのポート番号。通常は STARTTLS の場合 `587`、SMTPS (SSL) の場合 `465`。 |
| `encryption` | string | - | `"tls"` | 暗号化方式（`"tls"`, `"ssl"`, または空文字/`"none"`）。 |
| `username` | string | - | `""` | 送信元メールアドレスまたは認証ユーザー名（`smtp` および `oauth2` 共通）。 |
| `password` | string | - | `""` | SMTP認証のパスワード（`type: "smtp"` 時）。 |
| `auth_type` | string | - | `"auto"` | 認証方式（`"auto"`, `"login"`, `"plain"`, `"cram-md5"` 等。`type: "smtp"` 時）。 |
| `token_endpoint` | string | ○ (`oauth2`) | `""` | OAuth2のトークンエンドポイントURL（`type: "oauth2"` 時必須。例: `https://oauth2.googleapis.com/token`）。※HTTPS必須。 |
| `client_id` | string | ○ (`oauth2`) | `""` | OAuth2クライアントID（`type: "oauth2"` 時必須）。 |
| `client_secret` | string | ○ (`oauth2`) | `""` | OAuth2クライアントシークレット（`type: "oauth2"` 時必須）。 |
| `refresh_token` | string | ○ (`oauth2`) | `""` | OAuth2リフレッシュトークン（`type: "oauth2"` 時必須）。 |
| `helo` | string | - | `""` | EHLO/HELO コマンドで送信するドメイン名。省略時はホスト名等から自動判定されます。 |
| `timeout` | integer | - | `10` | ソケット接続およびトークンリクエストのタイムアウト秒数。 |

---

## 2. メール送信設定 (`mailconf`)

送信メッセージごとの設定を定義します。インデックス（通常は `"0"`、自動返信等がある場合は `"1"` など）ごとに設定を作成します。

```json
{
  "mailconf": {
    "0": {
      "from": "管理者 <admin@example.com>",
      "to": "info@example.com",
      "subject": "【お問い合わせ】{{{title}}}",
      "reply-to": "{{{email}}}",
      "return-path": "admin@example.com",
      "body-template-path": "mail_body.txt",
      "allow-temp-files": true
    }
  }
}

```

### パラメータ一覧 (`mailconf`)

| キー | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `from` | string | ○ | 差出人メールアドレス。`"名前 <email>"` 形式または `email` のみ。`{{{tag}}}` 置換利用可能。 |
| `to` | string / array | ○ | 送信先アドレス。配列で複数指定可能。`{{{tag}}}` 置換利用可能。 |
| `subject` | string | ○ | メールの件名。`{{{tag}}}` 置換利用可能。 |
| `reply-to` | string | - | Reply-Toアドレス。`{{{tag}}}` 置換利用可能。 |
| `return-path` | string | - | Return-Pathアドレス。 |
| `body-template-path` | string | ○ | メール本文テンプレートファイルの相対パス。 |
| `allow-temp-files` | boolean | - | `true` の場合、フォームで添付されたファイルをメールに自動添付します。 |

---

## 3. スパム・リクエスト検証設定 (`request-guard`)

bot 等によるスパム投稿を防ぐための検証ガード（reCAPTCHA v3 等）を設定します。配列形式で複数のガードを指定できます。

```json
{
  "request-guard": [
    {
      "type": "recaptcha",
      "secret_key": "YOUR_SECRET_KEY",
      "site_key": "YOUR_SITE_KEY",
      "score_threshold": 0.5
    }
  ]
}
```

### パラメータ一覧 (`request-guard`)

| キー | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `type` | string | ○ | 検証タイプ。reCAPTCHA v3 の場合は `"recaptcha"` を指定します。 |
| `secret_key` | string | ○ | Google reCAPTCHA v3 のシークレットキー。 |
| `site_key` | string | ○ | Google reCAPTCHA v3 のサイトキー。 |
| `score_threshold` | float | - | reCAPTCHA v3 の判定スコア閾値（`0.0` 〜 `1.0`）。省略時や `0` 指定時はデフォルト（`0.5`）が適用されます。指定した閾値未満のアクセスを bot と判定してエラーにします。 |

---

## 4. フォーム項目設定 (`items`)

フォームの各入力フィールドの挙動・検証ルールを定義します。キー名（下記例の `name`, `email` 等）が HTML の `name` 属性およびメールテンプレートの `{{{key}}}` 置換タグと連動します。

```json
{
  "items": {
    "name": {
      "title": "お名前",
      "value": "string",
      "empty": false,
      "minlength": 2,
      "maxlength": 50
    }
  }
}

```

### パラメータ一覧 (`items`)

| キー | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `title` | string | ○ | 項目の表示名。エラーメッセージの表示等に使用されます。 |
| `value` | string | ○ | 項目の入力タイプおよび適用するバリデーションルール（後述参照）。 |
| `empty` | boolean | ○ | 空送信の許可。`false` で**必須入力（選択）**、`true` で任意入力になります。 |
| `conv` | string | - | バリデーション実行前に入力値を自動変換します（後述参照）。 |
| `minlength` | integer | - | 最小文字数。指定文字数未満の場合はエラーになります。 |
| `maxlength` | integer | - | 最大文字数。指定文字数を超えるとエラーになります。 |
| `same` | string | - | 値が一致すべき別項目のキー名を指定します（確認用入力チェック）。 |
| `selectvalues` | object | - | `value` が `select` または `multi-select` の場合に選択肢を連想配列で定義します。 |
| `equal` | string | - | `value` が `agreement` の場合に、同意として認める値を指定します。 |
| `filetypes` | array | - | `value` が `file` の場合に、許可する MIME タイプを配列で指定します。 |

---

## 5. 入力値の自動変換 (`conv`)

`conv` パラメータを指定すると、**バリデーション処理が実行される前に**入力値が自動的に変換（サニタイズ・整形）されます。

| 指定値 | 変換内容 | 使用する内部関数・処理 |
| --- | --- | --- |
| `"two-byte"` | 全角変換 | `mb_convert_kana($val, 'KVRNA', 'UTF-8')` |
| `"one-byte"` | 半角変換 | `mb_convert_kana($val, 'as', 'UTF-8')` （長音符 `ー` は `-` に変換） |
| `"plus"` | 半角数字のみ抽出 | 英数変換後、数字以外の文字を除去 (`mb_ereg_replace('[^0-9]', '')`) |

---

## 6. `value` によるバリデーション種別

`value` に指定する値によって、適用される判定ロジックが決まります。

| `value` の値 | 判定内容 | 関連パラメータ | エラー条件 |
| --- | --- | --- | --- |
| **`string`** (または任意の文字列) | 基本テキスト |  |  |
| **`kana`** | 全角カタカナ |  | 全角カタカナ・全角スペース以外の文字が含まれる場合 |
| **`yomi`** | 全角ひらがな |  | 全角ひらがな・全角スペース以外の文字が含まれる場合 |
| **`plus`** | 半角数字（正数） |  | 0〜9の数字以外の文字が含まれる場合 |
| **`tel`** | 電話番号 |  | 半角数字・ハイフン(`-`)・プラス(`+`)以外の文字が含まれる場合 |
| **`email`** | メールアドレス |  | `filter_var(..., FILTER_VALIDATE_EMAIL)` に失敗した場合 |
| **`select`** | 単一選択 | `selectvalues` | 送信された値が `selectvalues` のキーに存在しない場合 |
| **`multi-select`** | 複数選択 | `selectvalues` | 配列でない、または配列要素が `selectvalues` のキーに存在しない場合 |
| **`agreement`** | 規約同意等 | `equal` | 入力値が `equal` で指定した値と一致しない場合 |
| **`file`** | 添付ファイル | `filetypes` | アップロードファイルが存在しない、または指定 MIME タイプに含まれない場合 |

---

## 7. 設定用 JSON サンプル

一般的なお問い合わせフォームで利用される設定のフルサンプルです。

```json
{
  "mailconf": {
    "0": {
      "from": "管理者 <admin@example.com>",
      "to": "info@example.com",
      "subject": "【Webフォーム】お問い合わせがありました",
      "reply-to": "{{{email}}}",
      "body-template-path": "templates_mail_admin.txt"
    },
    "1": {
      "from": "サポートデスク <admin@example.com>",
      "to": "{{{email}}}",
      "subject": "【自動返信】お問い合わせを受け付けました",
      "body-template-path": "templates_mail_user.txt"
    }
  },
  "request-guard": [
    {
      "type": "recaptcha",
      "secret_key": "YOUR_SECRET_KEY",
      "site_key": "YOUR_SITE_KEY",
      "score_threshold": 0.5
    }
  ],
  "items": {
    "name": {
      "title": "お名前",
      "value": "string",
      "empty": false,
      "maxlength": 50
    },
    "kana": {
      "title": "フリガナ",
      "value": "kana",
      "empty": false,
      "conv": "two-byte"
    },
    "tel": {
      "title": "電話番号",
      "value": "tel",
      "empty": true,
      "minlength": 6
    },
    "email": {
      "title": "メールアドレス",
      "value": "email",
      "empty": false,
      "conv": "one-byte"
    },
    "email_confirm": {
      "title": "メールアドレス（確認）",
      "value": "email",
      "empty": false,
      "conv": "one-byte",
      "same": "email"
    },
    "category": {
      "title": "お問い合わせ種別",
      "value": "select",
      "empty": false,
      "selectvalues": {
        "1": "サービスに関するお問い合わせ",
        "2": "採用について",
        "3": "その他"
      }
    },
    "message": {
      "title": "お問い合わせ内容",
      "value": "string",
      "empty": false,
      "maxlength": 1000
    },
    "attachment": {
      "title": "添付ファイル",
      "value": "file",
      "empty": true,
      "filetypes": [
        "image/jpeg",
        "image/png",
        "application/pdf"
      ]
    },
    "agree": {
      "title": "個人情報保護方針への同意",
      "value": "agreement",
      "empty": false,
      "equal": "1"
    }
  }
}
```

---

## 8. メソッドによるメール設定の直接指定（PHPコードによる設定）

JSONファイル (`mailconf`) を使わずに、PHPのメソッドを直接呼び出してメール送信設定を行うことや、JSONの設定を動的に上書きすることが可能です。

### 基本的な設定メソッド

```php
$mailform = new MailForm();

// フォーム項目の設定JSON読み込み
$mailform->loadFormConfig('config.json', __DIR__ . '/templates', MailForm::MAILFORM_FILE_TYPE_JSON);

// 宛先・差出人の直接指定
$mailform->addTo('info@example.com', '管理者');
$mailform->setFrom('no-reply@example.com', '送信専用');

// 件名・本文テンプレートの直接指定
$mailform->setMailSubjectTemplate('【Webお問い合わせ】{{{title}}}');
$mailform->setMailBodyTemplate("名前: {{{name}}}\nメール: {{{email}}}");

// 添付ファイルの許可
$mailform->setAllowTempFiles(true);

```

### 複数メール設定（インデックス指定）

自動返信メールなど、複数のメール設定を扱う場合は `ByIndex` がつくメソッドを使用します（`0` が管理者向け、`1` がユーザー自動返信向けなど）。

```php
// インデックス "1"（ユーザー宛て自動返信）の設定
$mailform->setFromByIndex(1, 'support@example.com', 'サポートデスク');
$mailform->addToByIndex(1, '{{{email}}}'); // 入力されたメールアドレス宛て
$mailform->setMailSubjectTemplateByIndex(1, '【自動返信】お問い合わせありがとうございます');
$mailform->setMailBodyTemplateByIndex(1, "{{{name}}} 様\n\nお問い合わせを受け付けました。");

```

### 設定用メソッド一覧

| メソッド名 | 説明 |
| --- | --- |
| `addTo($address, $name = '')` | 宛先を追加します（標準インデックス `0`） |
| `setFrom($address, $name = '')` | 差出人を設定します |
| `setReplyTo($address, $name = '')` | Reply-Toを設定します |
| `setReturnPath($address, $name = '')` | Return-Pathを設定します |
| `setMailSubjectTemplate($text)` | メール件名テンプレートを設定します |
| `setMailBodyTemplate($text)` | メール本文テンプレートを設定します |
| `setAllowTempFiles($flag)` | 添付ファイルの送信許可フラグ（`true`/`false`）を設定します |
| `getRequestGuard()` | 設定されている RequestGuard オブジェクトを取得します |
| `setRequestGuard($obj)` | RequestGuard オブジェクトを直接設定・上書きします |

※メール関連の各種メソッドに対して、第1引数にインデックス（`$index`）を指定できる `〇〇ByIndex()` メソッドが用意されています。
