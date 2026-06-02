<?php

namespace Yoast\PHPUnitPolyfills\TestListeners;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use Throwable;

/**
 * Renamed snake_case TestListener method collection used by the TestListenerDefaultImplementation traits.
 *
 * @since 0.2.0
 *
 * @phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- This is intentional as these are template methods.
 */
trait TestListenerSnakeCaseMethods {

	/**
	 * An error occurred.
	 *
	 * @param Test                $test Test object.
	 * @param Exception|Throwable $e    Instance of the error encountered.
	 * @param float               $time Execution time of this test.
	 *
	 * @return void
	 */
	public function add_error( $test, $e, $time ) {}

	/**
	 * A warning occurred.
	 *
	 * This method is only functional in PHPUnit 6.0 and above.
	 *
	 * @param Test    $test Test object.
	 * @param Warning $e    Instance of the warning encountered.
	 * @param float   $time Execution time of this test.
	 *
	 * @return void
	 */
	public function add_warning( $test, $e, $time ) {}

	/**
	 * A failure occurred.
	 *
	 * @param Test                 $test Test object.
	 * @param AssertionFailedError $e    Instance of the assertion failure exception encountered.
	 * @param float                $time Execution time of this test.
	 *
	 * @return void
	 */
	public function add_failure( $test, $e, $time ) {}

	/**
	 * Incomplete test.
	 *
	 * @param Test                $test Test object.
	 * @param Exception|Throwable $e    Instance of the incomplete test exception.
	 * @param float               $time Execution time of this test.
	 *
	 * @return void
	 */
	public function add_incomplete_test( $test, $e, $time ) {}

	/**
	 * Risky test.
	 *
	 * @param Test                $test Test object.
	 * @param Exception|Throwable $e    Instance of the risky test exception.
	 * @param float               $time Execution time of this test.
	 *
	 * @return void
	 */
	public function add_risky_test( $test, $e, $time ) {}

	/**
	 * Skipped test.
	 *
	 * @param Test                $test Test object.
	 * @param Exception|Throwable $e    Instance of the skipped test exception.
	 * @param float               $time Execution time of this test.
	 *
	 * @return void
	 */
	public function add_skipped_test( $test, $e, $time ) {}

	/**
	 * A test suite started.
	 *
	 * @param TestSuite $suite Test suite object.
	 *
	 * @return void
	 */
	public function start_test_suite( $suite ) {}

	/**
	 * A test suite ended.
	 *
	 * @param TestSuite $suite Test suite object.
	 *
	 * @return void
	 */
	public function end_test_suite( $suite ) {}

	/**
	 * A test started.
	 *
	 * @param Test $test Test object.
	 *
	 * @return void
	 */
	public function start_test( $test ) {}

	/**
	 * A test ended.
	 *
	 * @param Test  $test Test object.
	 * @param float $time Execution time of this test.
	 *
	 * @return void
	 */
	public function end_test( $test, $time ) {}
}
