# SimpleMailForm
入力 -> 確認 -> 完了のメールフォームを作るためのPHPライブラリ

## 特徴
* フォーム項目の簡単設定
* 送信メールのフォーマット設定
* 自動返信に対応
* CSRF対策
* セッションの実装のオーバーライド
* ファイルをアップロード対応  
アップロードしたファイルは、添付ファイルとして送信されます。
* 関数のオーバーライドで、送信先の振り分けなどに対応

## 依存ライブラリ
* [YAML](http://www.yaml.org/)
* php-yaml 
* ファイルのアップロード機能を使う時は、PHPMailerが必要
  *  [PHPMailer]( https://github.com/PHPMailer/PHPMailer)  
  externalディレクトリにcloneしてください

### 簡単な使い方
メインスクリプトとテンプレートファイルの２種類のファイルを作成します。
各ファイルは、/samples/baseを参考にしてください。

#### 作成するファイル
* form.php メールフォームのメインスクリプト
* input.php 入力画面のテンプレート
* confirm.php 確認画面のテンプレート
* complete.php 完了画面のテンプレート
* mailform_config.yml メールフォームの項目の設定
* to-admin-mail-body-template.txt, to-user-mail-body-template.txt メールのテンプレートファイル

ブラウザからアクセスするページは、form.phpになります。  
各ページのURLを変えたい場合は、/examples/separate_url を参考にしてください。

※画面のファイル名やパスは自由に変更できます。
