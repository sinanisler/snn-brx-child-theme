<?php

namespace Yoast\PHPUnitPolyfills\Polyfills;

use PHPUnit\Framework\Assert;

/**
 * Polyfill the Assert::assertIsList() method.
 *
 * Introduced in PHPUnit 10.0.0.
 *
 * @link https://github.com/sebastianbergmann/phpunit/pull/4818
 *
 * @since 2.0.0
 */
trait AssertIsList {

	/**
	 * Asserts that an array is list.
	 *
	 * @param mixed  $array   The value to test.
	 * @param string $message Optional failure message to display.
	 *
	 * @return void
	 */
	final public static function assertIsList( $array, $message = '' ) {
		$msg = self::assertIsListFailureDescription( $array );
		if ( $message !== '' ) {
			$msg = $message . \PHP_EOL . $msg;
		}

		if ( \is_array( $array ) === false ) {
			if ( \method_exists( Assert::class, 'assertIsArray' ) ) {
				static::assertIsArray( $array, $msg );
				return;
			}

			static::assertInternalType( 'array', $array, $msg );
			return;
		}

		if ( $array === [] ) {
			static::assertSame( $array, $array, $msg );
			return;
		}

		if ( \function_exists( 'array_is_list' ) ) {
			// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_is_listFound -- PHP 8.1+.
			static::assertTrue( \array_is_list( $array ), $msg );
			return;
		}

		$expected = \range( 0, ( \count( $array ) - 1 ) );

		static::assertSame( $expected, \array_keys( $array ), $msg );
	}

	/**
	 * Returns the description of the failure.
	 *
	 * @param mixed $other The value under test.
	 *
	 * @return string
	 */
	private static function assertIsListFailureDescription( $other ) {
		$type = \strtolower( \gettype( $other ) );

		switch ( $type ) {
			case 'double':
				$description = 'a float';
				break;

			case 'resource (closed)':
				$description = 'a closed resource';
				break;

			case 'array':
			case 'integer':
			case 'object':
				$description = 'an ' . $type;
				break;

			case 'boolean':
			case 'closed resource':
			case 'float':
			case 'resource':
			case 'string':
				$description = 'a ' . $type;
				break;

			case 'null':
				$description = 'null';
				break;

			default:
				$description = 'a value of ' . $type;
				break;
		}

		return \sprintf( 'Failed asserting that %s is a list.', $description );
	}
}
