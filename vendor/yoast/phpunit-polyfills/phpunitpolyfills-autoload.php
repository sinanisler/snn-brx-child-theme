<?php

namespace Yoast\PHPUnitPolyfills;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version as PHPUnit_Version;
use PHPUnit_Runner_Version;

if ( \class_exists( 'Yoast\PHPUnitPolyfills\Autoload', false ) === false ) {

	/**
	 * Custom autoloader.
	 *
	 * @since 0.1.0
	 */
	final class Autoload {

		/**
		 * Version number.
		 *
		 * @since 1.0.1
		 *
		 * @var string
		 */
		const VERSION = '2.0.5';

		/**
		 * Loads a class.
		 *
		 * @param string $className The name of the class to load.
		 *
		 * @return bool
		 */
		public static function load( $className ) {
			/*
			 * Polyfill two PHP 7.0 classes.
			 * The autoloader will only be called for these if these classes don't already
			 * exist in PHP natively.
			 */
			if ( $className === 'Error' || $className === 'TypeError' ) {
				$file = \realpath( __DIR__ . '/src/Exceptions/' . $className . '.php' );

				if ( \is_string( $file ) && \file_exists( $file ) === true ) {
					require_once $file;
					return true;
				}

				return false;
			}

			// Only load classes belonging to this library.
			if ( \stripos( $className, 'Yoast\PHPUnitPolyfills' ) !== 0 ) {
				return false;
			}

			switch ( $className ) {
				case 'Yoast\PHPUnitPolyfills\Polyfills\ExpectExceptionObject':
					self::loadExpectExceptionObject();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertIsType':
					self::loadAssertIsType();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertStringContains':
					self::loadAssertStringContains();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertEqualsSpecializations':
					self::loadAssertEqualsSpecializations();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\ExpectExceptionMessageMatches':
					self::loadExpectExceptionMessageMatches();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertFileEqualsSpecializations':
					self::loadAssertFileEqualsSpecializations();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\EqualToSpecializations':
					self::loadEqualToSpecializations();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertionRenames':
					self::loadAssertionRenames();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertClosedResource':
					self::loadAssertClosedResource();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertObjectEquals':
					self::loadAssertObjectEquals();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertIsList':
					self::loadAssertIsList();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertIgnoringLineEndings':
					self::loadAssertIgnoringLineEndings();
					return true;

				case 'Yoast\PHPUnitPolyfills\Polyfills\AssertObjectProperty':
					self::loadAssertObjectProperty();
					return true;

				case 'Yoast\PHPUnitPolyfills\TestCases\TestCase':
					self::loadTestCase();
					return true;

				case 'Yoast\PHPUnitPolyfills\TestListeners\TestListenerDefaultImplementation':
					self::loadTestListenerDefaultImplementation();
					return true;

				/*
				 * Handles:
				 * - Yoast\PHPUnitPolyfills\Exceptions\InvalidComparisonMethodException
				 * - Yoast\PHPUnitPolyfills\Helpers\AssertAttributeHelper
				 * - Yoast\PHPUnitPolyfills\Helpers\ResourceHelper
				 * - Yoast\PHPUnitPolyfills\TestCases\XTestCase
				 * - Yoast\PHPUnitPolyfills\TestListeners\TestListenerSnakeCaseMethods
				 */
				default:
					$file = \realpath( __DIR__ . '/src/' . \strtr( \substr( $className, 23 ), '\\', '/' ) . '.php' );

					if ( \is_string( $file ) && \file_exists( $file ) === true ) {
						require_once $file;
						return true;
					}
			}

			return false;
		}

		/**
		 * Load the ExpectExceptionObject polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadExpectExceptionObject() {
			if ( \method_exists( TestCase::class, 'expectExceptionObject' ) === false ) {
				// PHPUnit < 6.4.0.
				require_once __DIR__ . '/src/Polyfills/ExpectExceptionObject.php';
				return;
			}

			// PHPUnit >= 6.4.0.
			require_once __DIR__ . '/src/Polyfills/ExpectExceptionObject_Empty.php';
		}

		/**
		 * Load the AssertIsType polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertIsType() {
			if ( \method_exists( Assert::class, 'assertIsArray' ) === false ) {
				// PHPUnit < 7.5.0.
				require_once __DIR__ . '/src/Polyfills/AssertIsType.php';
				return;
			}

			// PHPUnit >= 7.5.0.
			require_once __DIR__ . '/src/Polyfills/AssertIsType_Empty.php';
		}

		/**
		 * Load the AssertStringContains polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertStringContains() {
			if ( \method_exists( Assert::class, 'assertStringContainsString' ) === false ) {
				// PHPUnit < 7.5.0.
				require_once __DIR__ . '/src/Polyfills/AssertStringContains.php';
				return;
			}

			// PHPUnit >= 7.5.0.
			require_once __DIR__ . '/src/Polyfills/AssertStringContains_Empty.php';
		}

		/**
		 * Load the AssertEqualsSpecializations polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertEqualsSpecializations() {
			if ( \method_exists( Assert::class, 'assertEqualsWithDelta' ) === false ) {
				// PHPUnit < 7.5.0.
				require_once __DIR__ . '/src/Polyfills/AssertEqualsSpecializations.php';
				return;
			}

			// PHPUnit >= 7.5.0.
			require_once __DIR__ . '/src/Polyfills/AssertEqualsSpecializations_Empty.php';
		}

		/**
		 * Load the ExpectExceptionMessageMatches polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadExpectExceptionMessageMatches() {
			if ( \method_exists( TestCase::class, 'expectExceptionMessageMatches' ) === false ) {
				// PHPUnit < 8.4.0.
				require_once __DIR__ . '/src/Polyfills/ExpectExceptionMessageMatches.php';
				return;
			}

			// PHPUnit >= 8.4.0.
			require_once __DIR__ . '/src/Polyfills/ExpectExceptionMessageMatches_Empty.php';
		}

		/**
		 * Load the AssertFileEqualsSpecializations polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertFileEqualsSpecializations() {
			if ( \method_exists( Assert::class, 'assertFileEqualsIgnoringCase' ) === false ) {
				// PHPUnit < 8.5.0.
				require_once __DIR__ . '/src/Polyfills/AssertFileEqualsSpecializations.php';
				return;
			}

			// PHPUnit >= 8.5.0.
			require_once __DIR__ . '/src/Polyfills/AssertFileEqualsSpecializations_Empty.php';
		}

		/**
		 * Load the EqualToSpecializations polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadEqualToSpecializations() {
			if ( \method_exists( Assert::class, 'equalToWithDelta' ) === false ) {
				// PHPUnit < 9.0.0.
				require_once __DIR__ . '/src/Polyfills/EqualToSpecializations.php';
				return;
			}

			// PHPUnit >= 9.0.0.
			require_once __DIR__ . '/src/Polyfills/EqualToSpecializations_Empty.php';
		}

		/**
		 * Load the AssertionRenames polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertionRenames() {
			if ( \method_exists( Assert::class, 'assertMatchesRegularExpression' ) === false ) {
				// PHPUnit < 9.1.0.
				require_once __DIR__ . '/src/Polyfills/AssertionRenames.php';
				return;
			}

			// PHPUnit >= 9.1.0.
			require_once __DIR__ . '/src/Polyfills/AssertionRenames_Empty.php';
		}

		/**
		 * Load the AssertClosedResource polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertClosedResource() {
			if ( \method_exists( Assert::class, 'assertIsClosedResource' ) === false ) {
				// PHPUnit < 9.3.0.
				require_once __DIR__ . '/src/Polyfills/AssertClosedResource.php';
				return;
			}

			// PHPUnit >= 9.3.0.
			require_once __DIR__ . '/src/Polyfills/AssertClosedResource_Empty.php';
		}

		/**
		 * Load the AssertObjectEquals polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertObjectEquals() {
			if ( \method_exists( Assert::class, 'assertObjectEquals' ) === false ) {
				// PHPUnit < 9.4.0.
				require_once __DIR__ . '/src/Polyfills/AssertObjectEquals.php';
				return;
			}

			// PHPUnit >= 9.4.0.
			require_once __DIR__ . '/src/Polyfills/AssertObjectEquals_Empty.php';
		}

		/**
		 * Load the AssertIsList polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertIsList() {
			if ( \method_exists( Assert::class, 'assertIsList' ) === false ) {
				// PHPUnit < 10.0.0.
				require_once __DIR__ . '/src/Polyfills/AssertIsList.php';
				return;
			}

			// PHPUnit >= 10.0.0.
			require_once __DIR__ . '/src/Polyfills/AssertIsList_Empty.php';
		}

		/**
		 * Load the AssertIgnoringLineEndings polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertIgnoringLineEndings() {
			if ( \method_exists( Assert::class, 'assertStringEqualsStringIgnoringLineEndings' ) === false ) {
				// PHPUnit < 10.0.0.
				require_once __DIR__ . '/src/Polyfills/AssertIgnoringLineEndings.php';
				return;
			}

			// PHPUnit >= 10.0.0.
			require_once __DIR__ . '/src/Polyfills/AssertIgnoringLineEndings_Empty.php';
		}

		/**
		 * Load the AssertObjectProperty polyfill or an empty trait with the same name
		 * if a PHPUnit version is used which already contains this functionality.
		 *
		 * @return void
		 */
		public static function loadAssertObjectProperty() {
			if ( \method_exists( Assert::class, 'assertObjectHasProperty' ) === false ) {
				// PHPUnit < 10.1.0.
				require_once __DIR__ . '/src/Polyfills/AssertObjectProperty.php';
				return;
			}

			// PHPUnit >= 10.1.0.
			require_once __DIR__ . '/src/Polyfills/AssertObjectProperty_Empty.php';
		}

		/**
		 * Load the appropriate TestCase class based on the PHPUnit version being used.
		 *
		 * @return void
		 */
		public static function loadTestCase() {
			if ( \version_compare( self::getPHPUnitVersion(), '8.0.0', '<' ) ) {
				// PHPUnit < 8.0.0.
				require_once __DIR__ . '/src/TestCases/TestCasePHPUnitLte7.php';
				return;
			}

			// PHPUnit >= 8.0.0.
			require_once __DIR__ . '/src/TestCases/TestCasePHPUnitGte8.php';
		}

		/**
		 * Load the appropriate TestListenerDefaultImplementation trait based on the PHPUnit version being used.
		 *
		 * @return void
		 */
		public static function loadTestListenerDefaultImplementation() {
			if ( \version_compare( self::getPHPUnitVersion(), '6.0.0', '<' ) ) {
				/*
				 * Alias one particular PHPUnit 5.x class to its PHPUnit >= 6 name.
				 *
				 * All other classes needed are part of the forward-compatibility layer.
				 *
				 * {@internal The `class_exists` wrappers are needed to play nice with
				 * PHPUnit bootstrap files of test suites implementing this library
				 * which may be creating cross-version compatibility in a similar manner.}}
				 */
				if ( \class_exists( 'PHPUnit_Framework_Warning' ) === true
					&& \class_exists( 'PHPUnit\Framework\Warning' ) === false
				) {
					\class_alias( 'PHPUnit_Framework_Warning', 'PHPUnit\Framework\Warning' );
				}

				// PHPUnit < 6.0.0.
				require_once __DIR__ . '/src/TestListeners/TestListenerDefaultImplementationPHPUnitLte5.php';
				return;
			}

			if ( \version_compare( PHPUnit_Version::id(), '7.0.0', '<' ) ) {
				// PHPUnit 6.0.0 < 7.0.0.
				require_once __DIR__ . '/src/TestListeners/TestListenerDefaultImplementationPHPUnit6.php';
				return;
			}

			// PHPUnit >= 7.0.0.
			require_once __DIR__ . '/src/TestListeners/TestListenerDefaultImplementationPHPUnitGte7.php';
		}

		/**
		 * Retrieve the PHPUnit version id.
		 *
		 * As both the pre-PHPUnit 6 class, as well as the PHPUnit 6+ class contain the `id()` function,
		 * this should work independently of whether or not another library may have aliased the class.
		 *
		 * @return string Version number as a string.
		 */
		public static function getPHPUnitVersion() {
			if ( \class_exists( '\PHPUnit\Runner\Version' ) ) {
				return PHPUnit_Version::id();
			}

			if ( \class_exists( '\PHPUnit_Runner_Version' ) ) {
				return PHPUnit_Runner_Version::id();
			}

			return '0';
		}
	}

	\spl_autoload_register( __NAMESPACE__ . '\Autoload::load' );
}
