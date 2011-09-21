<?php
/**
 * テストをしやすく為のツール
 * @author polidog
 * @version 0.1
 */
class TestRunkit
{
	/**
	 * 退避中のメソッド名
	 * @var array
	 */
	private static $__swapMethod = array();
	
	/**
	 * 退避中の関数名一覧
	 * @var array
	 */
	private static $__swapFunction = array();
	
	
	/**
	 * メソッドを一時的に退避させる
	 * @param string $className
	 * @param string $methodName
	 * @param string $rewriteMethodArgs
	 * @param string $rewriteMethod
	 */
	public static function swapMethod( $className, $methodName, $rewriteMethodArgs = '', $rewriteMethod = "return false;" ) {
		
		if ( !class_exists( $className ) ) {
			return false;
		}
		
		$toMethodName = '____swap_____method_______'.$methodName;
		if ( !runkit_method_copy( $className, $toMethodName, $className, $methodName ) ) {
			return false;
		}
		
		// 退避させたメソッド名を記憶しておく
		self::$__swapMethod[] = array(
			'class' => $className,
			'methodName' => $methodName,
		);
		
		// メソッドの書き換え
		if ( !runkit_method_redefine( $className, $methodName, $rewriteMethodArgs, $rewriteMethod ) ) {
			// 書き換えに失敗した場合
			array_pop( self::$__swapMethod );
			return false;
		}

	}
	
	/**
	 * 退避させたメソッドを復帰させる
	 * @param string $className
	 * @param string $methodName
	 */
	public function clearSwapMethod( $className = null, $methodName = null) {
		
		$methods = array();
		if ( !is_null( $className ) && !is_null( $methodName) ) {
			foreach( self::$__swapMethod as $key => $value ) {
				if ( $value['class'] == $className && $value['methodName'] == $meethodName ) {
					$methods = $value;
				}
			}
		}
		elseif ( is_null( $className ) && is_null( $methodName) ) {
			$methods = array_pop( self::$__swapMethod );
		}
		
		if ( !empty( $methods) ) {
			$removeMethodName = '____swap_____method_______'.$methods['methodName'];
			runkit_method_remove( $methods['class'], $methods['methodName'] );
			runkit_method_copy( $methods['class'], $methods['methodName'], $methods['class'], $removeMethodName );
			runkit_method_remove( $methods['class'], $removeMethodName );
			return true;
		}
		
		return false;
		
	}
	
	/**
	 * メソッドを一時退避させる
	 * @param string $functionName
	 */
	public function swapFunction( $functionName, $rewriteFuncArgs = '', $rewriteFunc = 'return false;' ) {
		if ( !function_exists( $functionName ) ) {
			return false;
		}
		else {
			// 一部許可しない関数は拒否する
			$noallowSwapFunctionNames = array( 'array_search', 'array_pop' );
			if ( array_search( $functionName, $noallowSwapFunctionNames ) ) {
				return false;
			}
		}
		
		$targetFunctionName = '____swap_____function_______'.$functionName;
		
		if ( runkit_function_copy( $functionName, $targetFunctionName ) ) {
			if ( runkit_function_redefine( $functionName, $rewriteFuncArgs, $rewriteFunc ) ) {
				self::$__swapFunction[] = $functionName;
				return true;
			}
			
		}
		return false;
	}
	
	/**
	 * 退避したメソッドを復活させる
	 */
	public function clearSwapFunction( $functionName = null ) {
		if ( is_null( $functionName ) ) {
			$functionName = array_pop( self::$__swapFunction );
		}
		else {
			if ( !array_search( $functionName, self::$__swapFunction ) ) {
				return false;
			}
		}
		
		if ( !function_exists( $functionName ) ) {
			return false;
		}
		
		$targetFunctionName = '____swap_____function_______'.$functionName;
		runkit_function_remove( $functionName );
		if ( runkit_function_copy( $targetFunctionName, $functionName ) ) {
			foreach( self::$__swapFunction as $key => $value ) {
				if ( $value ==  $functionName ) {
					unset( self::$__swapFunction[$key]);
				}
			}
		}
	}
	
}