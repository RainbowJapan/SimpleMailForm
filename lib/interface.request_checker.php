<?php
/**
 * RequestCheckerInterface
 *
 * セキュリティ的なリクエストチェックのインタフェース。
 */
interface RequestCheckerInterface
{
	/**
	 * リクエストの事前チェックを実行する。
	 *
	 * @param array $request $_POSTリクエスト配列
	 * @return bool 成功ならtrue、検証失敗ならfalse
	 */
	public function check($request);
	
	/**
	 * エラーIDを返す。
	 *
	 * @return int|null エラーID（未発生時はnull）
	 */
	public function getErrorId();
	
	/**
	 * フロントエンドに渡したい公開データだけを配列で返す。
	 *
	 * @return array 公開データ配列
	 */
	public function getClientConfig();
}
