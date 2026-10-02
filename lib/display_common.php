<?php

/**
 * htmlで表示しても大丈夫なようにエスケープした文字列を返します。
 *
 * @param string $value 変換する文字列
 * @return string 変換後の文字列
 */
function __htmlescape($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * 指定された値をHTML用にエスケープして表示します。
 *
 * @param string $value 表示する値
 */
function __e($value)
{
	echo __htmlescape($value);
}

/**
 * 指定された値をHTML用にエスケープして改行コードを<br />に変換して表示します。
 *
 * @param string $value 表示する値
 */
function __ebr($value)
{
	echo str_replace("\n", "<br />", __htmlescape($value));
}

/**
 * 指定された値を表示します（エスケープなし）。
 *
 * @param string $value 表示する値
 */
function __p($value)
{
	echo $value;
}

/**
 * チェックボックスのcheckedを出力します。
 *
 * @param bool $isChecked
 */
function __pchecked($isChecked)
{
	if ($isChecked) {
		echo 'checked';
	}
}
