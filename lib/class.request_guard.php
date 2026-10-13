<?php
require_once __DIR__ . '/interface.request_checker.php';

/**
 * RequestGuard
 *
 * セキュリティ的なリクエストチェックを保持し、順番にチェックを実行するクラス。
 */
class RequestGuard
{
	/**
	 * @var RequestCheckerInterface[]
	 */
	private $checkers = [];
	
	private $clientConfig = [];
	
	/**
	 * チェッカー（Hook）を追加する。
	 *
	 * @param string $type タイプ名
	 * @param RequestCheckerInterface $checker チェッカーオブジェクト
	 * @return void
	 */
	public function addChecker($type, RequestCheckerInterface $checker)
	{
		$this->checkers[] = $checker;
		$config = $checker->getClientConfig();
		$this->clientConfig[$type] = $config;
	}
	
	/**
	 * 登録された全チェッカーを順番に実行する。
	 * エラーを検知した時点で即座に処理を打ち切ります
	 *
	 * @param array $request $_POSTをセット
	 * @return array エラーコード（int）の配列（空なら検証成功）
	 */
	public function validate($request)
	{
		$errors = [];
		foreach ($this->checkers as $checker) {
			if (!$checker->check($request)) {
				$errors[] = $checker->getErrorId();
				return $errors;
			}
		}
		return $errors;
	}
	
	/**
	 * フロントエンドに渡したい公開データだけを配列で返す。
	 *
	 * @param string $type タイプ
	 * @return array|null フロントエンド用設定の配列（存在しない場合はnull）
	 */
	public function getClientConfig($type)
	{
		if (!isset($this->clientConfig[$type])) {
			return null;
		}
		return $this->clientConfig[$type];
	}
	
	/**
	 * 登録されているリクエストチェッカーの配列を取得する。
	 *
	 * @return RequestCheckerInterface[]
	 */
	public function getRequestCheckers()
	{
		return $this->checkers;
	}
}
