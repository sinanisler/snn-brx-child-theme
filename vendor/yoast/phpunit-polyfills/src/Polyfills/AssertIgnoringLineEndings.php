<?php

namespace Yoast\PHPUnitPolyfills\Polyfills;

use PHPUnit\SebastianBergmann\Exporter\Exporter as Exporter_In_Phar_Old;
use PHPUnitPHAR\SebastianBergmann\Exporter\Exporter as Exporter_In_Phar;
use SebastianBergmann\Exporter\Exporter;
use TypeError;

/**
 * Polyfill the Assert::assertStringEqualsStringIgnoringLineEndings() and the
 * Assert::assertStringContainsStringIgnoringLineEndings() methods.
 *
 * Introduced in PHPUnit 10.0.0.
 *
 * @link https://github.com/sebastianbergmann/phpunit/issues/4641
 * @link https://github.com/sebastianbergmann/phpunit/pull/4670
 * @link https://github.com/sebastianbergmann/phpunit/issues/4935
 * @link https://github.com/sebastianbergmann/phpunit/pull/5279
 *
 * @since 2.0.0
 */
trait AssertIgnoringLineEndings {

	/**
	 * Asserts that two strings are equal except for line endings.
	 *
	 * @param string $expected Expected value.
	 * @param string $actual   The value to test.
	 * @param string $message  Optional failure message to display.
	 *
	 * @return void
	 *
	 * @throws TypeError When any of the passed arguments do not meet the required type.
	 */
	final public static function assertStringEqualsStringIgnoringLineEndings( $expected, $actual, $message = '' ) {
		/*
		 * Parameter input validation.
		 * In PHPUnit this is done via PHP native type declarations. Emulating this for the polyfill.
		 * Note: using `is_scalar()` instead of `is_string()` as test files may not be using strict_types.
		 */
		if ( \is_scalar( $expected ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 1 passed to assertStringEqualsStringIgnoringLineEndings() must be of type string, %s given',
					\gettype( $expected )
				)
			);
		}
		if ( \is_scalar( $actual ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 2 passed to assertStringEqualsStringIgnoringLineEndings() must be of type string, %s given',
					\gettype( $actual )
				)
			);
		}

		$expected = self::normalizeLineEndingsForIgnoringLineEndingsAssertions( (string) $expected );
		$exporter = self::getPHPUnitExporterObjectForIgnoringLineEndings();
		$msg      = \sprintf(
			'Failed asserting that %s is equal to "%s" ignoring line endings.',
			$exporter->export( $actual ),
			$expected
		);

		if ( $message !== '' ) {
			$msg = $message . \PHP_EOL . $msg;
		}

		$actual = self::normalizeLineEndingsForIgnoringLineEndingsAssertions( (string) $actual );

		static::assertSame( $expected, $actual, $msg );
	}

	/**
	 * Asserts that two variables are equal (ignoring case).
	 *
	 * @param string $needle   The string to search for.
	 * @param string $haystack The string to treat as the haystack.
	 * @param string $message  Optional failure message to display.
	 *
	 * @return void
	 *
	 * @throws TypeError When any of the passed arguments do not meet the required type.
	 */
	final public static function assertStringContainsStringIgnoringLineEndings( $needle, $haystack, $message = '' ) {
		/*
		 * Parameter input validation.
		 * In PHPUnit this is done via PHP native type declarations. Emulating this for the polyfill.
		 * Note: using `is_scalar()` instead of `is_string()` as test files may not be using strict_types.
		 */
		if ( \is_scalar( $needle ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 1 passed to assertStringContainsStringIgnoringLineEndings() must be of type string, %s given',
					\gettype( $needle )
				)
			);
		}
		if ( \is_scalar( $haystack ) === false ) {
			throw new TypeError(
				\sprintf(
					'Argument 2 passed to assertStringContainsStringIgnoringLineEndings() must be of type string, %s given',
					\gettype( $haystack )
				)
			);
		}

		$needle   = self::normalizeLineEndingsForIgnoringLineEndingsAssertions( (string) $needle );
		$haystack = self::normalizeLineEndingsForIgnoringLineEndingsAssertions( (string) $haystack );

		static::assertStringContainsString( $needle, $haystack, $message );
	}

	/**
	 * Normalize line endings.
	 *
	 * @param string $value The text to normalize.
	 *
	 * @return string
	 */
	private static function normalizeLineEndingsForIgnoringLineEndingsAssertions( $value ) {
		return \strtr(
			$value,
			[
				"\r\n" => "\n",
				"\r"   => "\n",
			]
		);
	}

	/**
	 * Helper function to obtain an instance of the Exporter class.
	 *
	 * @return Exporter|Exporter_In_Phar|Exporter_In_Phar_Old
	 */
	private static function getPHPUnitExporterObjectForIgnoringLineEndings() {
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
