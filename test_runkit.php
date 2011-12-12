<?php
/**
 * テストをしやすく為のツール
 * 
 * ちなみに関数のswap基本独自定義のみです。組み込み関数をスワップさせる場合は
 * runkit.internal_overrideの設定をphp.iniより変更してください
 * @author polidogs@gmail.com
 * @version 0.2
 */
class TestRunkit
{
	/**
	 * 退避中のメソッド名
	 * @var array
	 */
	private static $__swapMethodList = array();

	/**
	 * 退避中の関数
	 * @var array
	 */
	private static $__swapFunctionList = array();
	
	/**
	 * メソッドを一時的に退避させる
	 * @param string $class
	 * @param string $method
	 * @param string $rewriteMethodArgs
	 * @param string $rewriteMethod
	 * @return boolean
	 */
	public static function swapMethod( $class, $method, $rewriteMethodArgs = '', $rewriteMethod = "return false;" ) {
		
		// メソッドが存在するかチェックする
		if ( !self::checkMethod($class, $method) ) {
			return false;
		}
		
		// すでにスワップ中かチェックする
		if ( self::isSwapMethod( $class, $method ) ) {
			return false;
		}
		
		
		$swapMethodName = self::getSwapMethodNmae($method);
		
		// 今定義しているメソッドを別名にコピーする
		if ( !runkit_method_copy( $class, $swapMethodName, $class, $method ) ) {
			return false;
		}
		
		// メソッドの書き換えを行う
		if ( !runkit_method_redefine( $class, $method, $rewriteMethodArgs, $rewriteMethod ) ) {
			return false;
		}
		
		// スワップ情報を記録する
		self::addSwapMethodList( $class, $method );
		return true;
	}
	
	/**
	 * スワップを解除する
	 * @param string $class
	 * @param string $method
	 * @return booelan
	 */
	public static function clearSwapMethod( $class = null, $method = null ) {
		
		// 指定されてない場合
		if ( is_null( $class ) || is_null( $method ) ) {
			extract( self::getLastSwapMethod() );
		}
		
		// すでにスワップ中かチェックする
		if ( !self::isSwapMethod( $class, $method ) ) {
			return false;
		}
		
		$swapMethodName = self::getSwapMethodNmae($method);
		
		if ( !runkit_method_remove( $class, $method ) ) {
			return false;
		} 
		
		if ( !runkit_method_copy( $class, $method, $class, $swapMethodName ) ) {
			return false;
		}
		
		if ( !runkit_method_remove( $class, $swapMethodName ) ) {
			return false;
		} 
		
		return self::removeSwapMethodList( $class, $method );
		
	}
	
	/**
	 * スワップしているメソッド一覧を取得する
	 * @return array
	 */
	public static function getSwapMethodList() {
		return self::$__swapMethodList;
	}
	
	/**
	 * 関数をスワップさせる
	 * @param string $function
	 * @param string $rewriteArgs
	 * @param string $rewriteCode
	 * @return boolean スワップ成功時true 失敗時false
	 */
	public static function swapFunction( $function, $rewriteArgs = '', $rewriteCode = "return false;") {
		// 関数が存在するかチェックする
		if ( !self::checkFunction( $function ) ) {
			return false;
		}
		
		// 関数が既にスワップされているかチェックする
		if ( self::isSwapFunction($function ) ) {
			return false;
		}
		
		$swapFunctionName = self::getSwapFunctionName( $function );
		
		// 関数をスワップさせる
		if ( !runkit_function_copy( $function, $swapFunctionName ) ) {
			return false;
		}
		
		// 関数を書き換える
		if ( !runkit_function_redefine( $function , $rewriteArgs, $rewriteCode ) ) {
			return false;
		}
		self::addSwapFunctionList( $function );
		return true;
	}
	
	/**
	 * 退避させたスワップを元に戻す
	 * @param string $function 指定がない場合は最後にスワップした関数を元に戻す
	 * @return boolean
	 */
	public static function clearSwapFunction( $function = null ) {
		if ( is_null( $function ) ) {
			extract( self::getLastSwapFunction() );
		}
		var_dump($function);
		
		// スワップ中か確認する
		if ( !self::isSwapFunction($function ) ) {
			return false;
		}
		
		if ( !runkit_function_remove( $function ) ) {
			return false;
		}
		
		$swapFunctionName = self::getSwapFunctionName($function);
		if ( !runkit_function_copy( $swapFunctionName, $function ) ) {
			return false;
		}
		
		if ( !runkit_function_remove( $swapFunctionName ) ) {
			return false;
		}
		
		self::removeSwapFunctionList($function);
		return true;
	}
	
	/**
	 * スワップしているメソッド一覧を取得する
	 * @return array
	 */
	public static function getSwapFunctionList() {
		return self::$__swapFunctionList;
	}
	
	/**
	 * メソッドが存在するかチェックする
	 * @param string $class
	 * @param string $method
	 * @return boolean メソッドが存在する場合はtrueを返す
	 */
	private static function checkMethod( $class, $method ) {
		// 指定されたクラスが存在するかチェックする
		if ( !class_exists( $class ) ) {
			return false;
		}

		// 指定されたメソッドが存在するかチェックする
		if ( !method_exists( $class, $method ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * スワップ中のメソッドがあるかチェックする
	 * @param string $class
	 * @param string $method
	 */
	private static function isSwapMethod( $class, $method ) {
		$key = self::getMethodListKey($class, $method);
		if ( isset( self::$__swapMethodList[$key] ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * スワップリストに記録する
	 * @param string $class
	 * @param string $method
	 * @return void
	 */
	private static function addSwapMethodList( $class, $method ) {
		$key = self::getMethodListKey($class, $method);
		self::$__swapMethodList[$key] = array(
			'class' => $class,
			'method' => $method,
			'datetime'	=> date('Y-m-d H:i:s') 
		);
	}
	
	/**
	 * スワップリストから削除する
	 * @param string $class
	 * @param string $method
	 * @return boolean
	 */
	private static function removeSwapMethodList( $class, $method ) {
		$key = self::getMethodListKey($class, $method);
		if ( isset( self::$__swapMethodList[$key] ) ) {
			unset( self::$__swapMethodList[$key] ) ;
			return true;
		}
		return false;
	}
	
	
	/**
	 * スワップ後のメソッド名を取得する
	 * @param string $method
	 */
	private static function getSwapMethodNmae( $method ) {
		return "_______swap_____method_____".$method;
	}
	
	/**
	 * swap配列のキーを取得する
	 * @param stirng $class
	 * @param string $method
	 */
	private static function getMethodListKey( $class, $method ) {
		return $class."::".$method;
	}
	
	/**
	 * 最後にスワップした情報を取得する
	 * @return array
	 */
	private static function getLastSwapMethod() {
		end( self::$__swapMethodList );
		$key = key( self::$__swapMethodList );
		reset( self::$__swapMethodList );
		return self::$__swapMethodList[$key];
	}
	
	/**
	 * 最初にスワップした情報を取得する
	 */
	private static function getFirstSwapMethod() {
		reset( self::$__swapMethodList );
		$key = key( self::$__swapMethodList );
		return self::$__swapMethodList[$key];
	}
	
	/**
	 * 関数があるか調べる
	 * @param string $function
	 * @return boolean
	 */
	private static function checkFunction( $function ) {
		return function_exists( $function );
	}
	
	/**
	 * 関数がスワップされいているか確認する
	 * @param string $function
	 * @return boolean
	 */
	private static function isSwapFunction( $function ) {
		$key = self::getSwapFunctionKey($function);
		return isset( self::$__swapFunctionList[$key] );
	}
	
	/**
	 * スワップ後の関数名
	 * @param string $function
	 * @return string
	 */
	private static function getSwapFunctionName( $function ) {
		return "_______swap_____function_____".$function;
	}
	
	/**
	 * swap配列のキーを取得する
	 * @param string $function
	 * @return string
	 */
	private static function getSwapFunctionKey( $function ) {
		return "function::".$function;
	}
	
	/**
	 * スワップリストに登録する
	 * @param string $function
	 * @return void
	 */
	private static function addSwapFunctionList( $function ) {
		$key = self::getSwapFunctionKey( $function );
		self::$__swapFunctionList[$key] = array(
			'function' => $function,
			'datetime' => date('Y-m-d H:i:s'),
		);
	}
	
	/**
	 * スワップリストから削除する
	 * @param string $function
	 * @return boolean
	 */
	private static function removeSwapFunctionList( $function ) {
		$key = self::getSwapFunctionKey( $function );
		if ( isset( self::$__swapFunctionList[$key] ) ) {
			unset( self::$__swapFunctionList[$key] );
			return true;
		}
		return false;
	}
	
	/**
	 * 最後にスワップした関数の情報を取得する
	 * @return array
	 */
	private static function getLastSwapFunction() {
		end( self::$__swapFunctionList );
		$key = key( self::$__swapFunctionList );
		reset( self::$__swapFunctionList );
		return self::$__swapFunctionList[$key];
	}
	
	/**
	 * 最初にスワップした関数の情報を取得する
	 */
	private static function getFirstSwapFunction() {
		reset( self::$__swapFunctionList );
		$key = key( self::$__swapFunctionList );
		return self::$__swapFunctionList[$key];
	}	

	/**
	 * ハッシュ値を生成する
	 * @return string ハッシュ値
	 */
	private static function _hash() {
		return md5( mt_rand(1,2000000000).date('YmdHis').mt_rand(1,20000) );
	}
}