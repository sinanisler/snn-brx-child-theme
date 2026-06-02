<?php

namespace Yoast\PHPUnitPolyfills\Polyfills;

use PHPUnit\Framework\Assert;
use ReflectionObject;
use TypeError;
use Yoast\PHPUnitPolyfills\Autoload;

/**
 * Polyfill the Assert::assertObjectHasProperty() and Assert::assertObjectNotHasProperty() methods,
 * which replace the Assert::assertObjectHasAttribute() and Assert::assertObjectNotHasAttribute() methods.
 *
 * Introduced in PHPUnit 10.1.0 and PHPUnit 9.6.11.
 *
 * The Assert::assertObjectHasAttribute() and Assert::assertObjectNotHasAttribute() methods
 * were deprecated in PHPUnit 9.6.1 and removed in PHPUnit 10.0.0.
 *
 * @link https://github.com/sebastianbergmann/phpunit/pull/5231
 * @link https://github.com/sebastianbergmann/phpunit/issues/5478
 *
 * @since 2.0.0
 */
trait AssertObjectProperty {

	/**
	 * Asserts that an object has a specified property.
	 *
	 * @param string $propertyName The name of the property.
	 * @param object $object       The object on which to check whether the property exists.
	 * @param string $message      Optional failure message to display.
	 *
	 * @return void
	 *
	 * @throws TypeError When any of the passed arguments do not meet the required type.
	 */
	final public static function assertObjectHasProperty( $propertyName, $object, $message = '' ) {
		/*
		 * Parameter input validation.
		 * In PHPUnit this is done via PHP native type declarations. Emulating this for the polyfill,
		 * including for those PHPUnit versions where we hand off to a native PHPUnit alternative, as
		 * otherwise the method referenced in the error message would get very confusing and inconsistent.
		 */
		if ( \is_string( $propertyName ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 1 passed to assertObjectHasProperty() must be of type string, %s given',
					\gettype( $propertyName )
				)
			);
		}
		if ( \is_object( $object ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 2 passed to assertObjectHasProperty() must be of type object, %s given',
					\gettype( $object )
				)
			);
		}

		if ( \method_exists( Assert::class, 'assertObjectHasAttribute' )
			&& \version_compare( Autoload::getPHPUnitVersion(), '9.6.0', '<=' )
		) {
			// PHPUnit <= 9.6.0.
			static::assertObjectHasAttribute( $propertyName, $object, $message );
			return;
		}

		/*
		 * PHPUnit 9.6.1 < 9.6.11 and PHPUnit 10.0.x.
		 * Note: letting this polyfill code kick in for PHPUnit 9.6.1 < 9.6.11 as well
		 * to prevent the PHPUnit deprecation notice showing.
		 */
		$msg  = self::assertObjectHasPropertyFailureDescription( $object );
		$msg .= \sprintf( ' has property "%s".', $propertyName );
		if ( $message !== '' ) {
			$msg = $message . \PHP_EOL . $msg;
		}

		$hasProperty = ( new ReflectionObject( $object ) )->hasProperty( $propertyName );
		static::assertTrue( $hasProperty, $msg );
	}

	/**
	 * Asserts that an object does not have a specified property.
	 *
	 * @param string $propertyName The name of the property.
	 * @param object $object       The object on which to check whether the property exists.
	 * @param string $message      Optional failure message to display.
	 *
	 * @return void
	 *
	 * @throws TypeError When any of the passed arguments do not meet the required type.
	 */
	final public static function assertObjectNotHasProperty( $propertyName, $object, $message = '' ) {
		/*
		 * Parameter input validation.
		 * In PHPUnit this is done via PHP native type declarations. Emulating this for the polyfill,
		 * including for those PHPUnit versions where we hand off to a native PHPUnit alternative, as
		 * otherwise the method referenced in the error message would get very confusing and inconsistent.
		 */
		if ( \is_string( $propertyName ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 1 passed to assertObjectNotHasProperty() must be of type string, %s given',
					\gettype( $propertyName )
				)
			);
		}
		if ( \is_object( $object ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 2 passed to assertObjectNotHasProperty() must be of type object, %s given',
					\gettype( $object )
				)
			);
		}

		if ( \method_exists( Assert::class, 'assertObjectNotHasAttribute' )
			&& \version_compare( Autoload::getPHPUnitVersion(), '9.6.0', '<=' )
		) {
			// PHPUnit <= 9.6.0.
			static::assertObjectNotHasAttribute( $propertyName, $object, $message );
			return;
		}

		/*
		 * PHPUnit 9.6.1 < 9.6.11 and PHPUnit 10.0.x.
		 * Note: letting this polyfill code kick in for PHPUnit 9.6.1 < 9.6.11 as well
		 * to prevent the PHPUnit deprecation notice showing.
		 */
		$msg  = self::assertObjectHasPropertyFailureDescription( $object );
		$msg .= \sprintf( ' does not have property "%s".', $propertyName );
		if ( $message !== '' ) {
			$msg = $message . \PHP_EOL . $msg;
		}

		$hasProperty = ( new ReflectionObject( $object ) )->hasProperty( $propertyName );
		static::assertFalse( $hasProperty, $msg );
	}

	/**
	 * Returns the description of the failure.
	 *
	 * @param object $object The object under test.
	 *
	 * @return string
	 */
	private static function assertObjectHasPropertyFailureDescription( $object ) {
		return \sprintf(
			'Failed asserting that object of class "%s"',
			\get_class( $object )
		);
	}
}
