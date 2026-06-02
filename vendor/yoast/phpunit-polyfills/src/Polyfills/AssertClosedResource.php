<?php

namespace Yoast\PHPUnitPolyfills\Polyfills;

use PHPUnit\SebastianBergmann\Exporter\Exporter as Exporter_In_Phar_Old;
use PHPUnitPHAR\SebastianBergmann\Exporter\Exporter as Exporter_In_Phar;
use SebastianBergmann\Exporter\Exporter;
use Yoast\PHPUnitPolyfills\Helpers\ResourceHelper;

/**
 * Polyfill the Assert::assertIsClosedResource() and Assert::assertIsNotClosedResource() methods.
 *
 * Introduced in PHPUnit 9.3.0.
 *
 * @link https://github.com/sebastianbergmann/phpunit/issues/4276
 * @link https://github.com/sebastianbergmann/phpunit/pull/4365
 *
 * @since 1.0.0
 */
trait AssertClosedResource {

	/**
	 * Asserts that a variable is of type resource and is closed.
	 *
	 * @param mixed  $actual  The variable to test.
	 * @param string $message Optional failure message to display.
	 *
	 * @return void
	 */
	public static function assertIsClosedResource( $actual, $message = '' ) {
		$exporter = self::getPHPUnitExporterObject();
		$msg      = \sprintf( 'Failed asserting that %s is of type "resource (closed)"', $exporter->export( $actual ) );

		if ( $message !== '' ) {
			$msg = $message . \PHP_EOL . $msg;
		}

		static::assertTrue( ResourceHelper::isClosedResource( $actual ), $msg );
	}

	/**
	 * Asserts that a variable is not of type resource or is an open resource.
	 *
	 * @param mixed  $actual  The variable to test.
	 * @param string $message Optional failure message to display.
	 *
	 * @return void
	 */
	public static function assertIsNotClosedResource( $actual, $message = '' ) {
		$exporter = self::getPHPUnitExporterObject();
		$type     = $exporter->export( $actual );
		if ( $type === 'NULL' ) {
			$type = 'resource (closed)';
		}
		$msg = \sprintf( 'Failed asserting that %s is not of type "resource (closed)"', $type );

		if ( $message !== '' ) {
			$msg = $message . \PHP_EOL . $msg;
		}

		static::assertFalse( ResourceHelper::isClosedResource( $actual ), $msg );
	}

	/**
	 * Helper function to determine whether an assertion regarding a resource's state should be skipped.
	 *
	 * Due to some bugs in PHP itself, the "is closed resource" determination
	 * cannot always be done reliably.
	 *
	 * This method can determine whether or not the current value in combination with
	 * the current PHP version on which the test is being run is affected by this.
	 *
	 * Use this function to skip running a test using `assertIs[Not]ClosedResource()` or
	 * to skip running just that particular assertion.
	 *
	 * @param mixed $actual The variable to test.
	 *
	 * @return bool
	 */
	public static function shouldClosedResourceAssertionBeSkipped( $actual ) {
		return ( ResourceHelper::isResourceStateReliable( $actual ) === false );
	}

	/**
	 * Helper function to obtain an instance of the Exporter class.
	 *
	 * @return Exporter|Exporter_In_Phar|Exporter_In_Phar_Old
	 */
	private static function getPHPUnitExporterObject() {
		if ( \class_exists( Exporter::class ) ) {
			// Composer install or really old PHAR files.
			return new Exporter();
		}
		elseif ( \class_exists( Exporter_In_Phar::class ) ) {
			// PHPUnit PHAR file for 8.5.38+, 9.6.19+, 10.5.17+ and 11.0.10+.
			return new Exporter_In_Phar();
		}

		// PHPUnit PHAR file for < 8.5.38, < 9.6.19, < 10.5.17 and < 11.0.10.
		return new Exporter_In_Phar_Old();
	}
}
