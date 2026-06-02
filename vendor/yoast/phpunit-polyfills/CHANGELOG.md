# Change Log for Yoast PHPUnit Polyfills

All notable changes to this project will be documented in this file.

This projects adheres to [Keep a CHANGELOG](http://keepachangelog.com/) and uses [Semantic Versioning](https://semver.org/).


## [Unreleased]

_Nothing yet._

## [2.0.5] - 2025-08-10

### Fixed
* PHP 8.5 deprecation notice when using the `AssertAttributeHelper::getProperty()` and `AssertAttributeHelper::getPropertyValue()` methods. PR [#261]

### Changed
* General housekeeping.

<!-- Link to #261 is defined on the 1.1.5 release. -->


## [2.0.4] - 2025-02-09

This is a maintenance release.

### Changed
* README: updated sub-section about PHPUnit version support with information about the PHPUnit Polyfills 4.x branch. PR [#249]
* General housekeeping.

<!-- Link to #249 is defined on the 1.1.4 release. -->


## [2.0.3] - 2025-01-08

This is a maintenance release.

### Changed
* README: fixed a few broken badges.
* General housekeeping.


## [2.0.2] - 2024-09-07

This is a maintenance release.

### Changed
* README: updated sub-section about PHPUnit version support with information about the PHPUnit Polyfills 3.x branch. PR [#188]
* README: FAQ updated with info about ability to polyfill the removed `expectDeprecation*()` methods et al. PR [#187], props [Tonya Mork].
* README: links to the PHPUnit manual now point explicitly to the PHPUnit 10.x documentation. PR [#190]
* General housekeeping.

[#187]: https://github.com/Yoast/PHPUnit-Polyfills/pull/187
<!-- Link to #188 is defined on the 1.1.2 release. -->
[#190]: https://github.com/Yoast/PHPUnit-Polyfills/pull/190


## [2.0.1] - 2024-04-05

### Added
* Compatibility fixes for running tests using a PHPUnit PHAR file for PHPUnit 8.5.38+, 9.6.19+ and PHPUnit 10.5.17+. PRs [#161], [#164].

### Changed
* General housekeeping.

[#164]: https://github.com/Yoast/PHPUnit-Polyfills/pull/164


## [2.0.0] - 2023-06-06

### PHPUnit 10 support

This release updates the PHPUnit Polyfills to allow for _"writing your tests for PHPUnit 10 and running them all the way back to PHPUnit 5"_.

Please keep in mind that the PHPUnit Polyfills provide _forward_-compatibility. This means that features which PHPUnit no longer supports in PHPUnit 10.x, like expecting PHP deprecation notices or warnings, are also no longer supported in the 2.0 release of the PHPUnit Polyfills.

Please refer to the [PHPUnit 10 release notification] and [PHPUnit 10 changelog] to inform your decision on whether or not to upgrade (yet).

Projects which don't use any of the new or removed functionality in their test suite, can, of course, use the PHPUnit Polyfills 1.x and 2.x series side-by-side, like so `composer require --dev yoast/phpunit-polyfills:"^1.0 || ^2.0"`.

[PHPUnit 10 release notification]: https://phpunit.de/announcements/phpunit-10.html
[PHPUnit 10 changelog]:            https://github.com/sebastianbergmann/phpunit/blob/10.0.19/ChangeLog-10.0.md

> :warning: **Important: about the TestListener polyfill** :warning:
>
> The TestListener polyfill in PHPUnit Polyfills 2.0 is **[not (yet) compatible with PHPUnit 10.0.0][polyfill-ticket]**.
>
> If you need the TestListener polyfill, it is recommended to stay on the PHPUnit Polyfills 1.x series for the time being and to watch the [related ticket][polyfill-ticket].

[polyfill-ticket]: https://github.com/Yoast/PHPUnit-Polyfills/issues/128


### Changelog

#### Added
* `Yoast\PHPUnitPolyfills\Polyfills\AssertIgnoringLineEndings` trait to polyfill the `Assert::assertStringEqualsStringIgnoringLineEndings()` and the `Assert::assertStringContainsStringIgnoringLineEndings()` methods as introduced in PHPUnit 10.0.0. PR [#109].
* `Yoast\PHPUnitPolyfills\Polyfills\AssertIsList` trait to polyfill the `Assert::assertIsList()` method as introduced in PHPUnit 10.0.0. PR [#110].
* `Yoast\PHPUnitPolyfills\Polyfills\AssertObjectProperty` trait to polyfill the `Assert::assertObjectHasProperty()` and the `Assert::assertObjectNotHasProperty()` methods as introduced in PHPUnit 10.1.0. PR [#116].

#### Changed
* Composer: allow for installation of PHPUnit 10.x. PR [#130]
* Nearly all assertion methods are now `final`. This alignes them with the same change made upstream in PHPUnit 10.0.0. PR [#104].
* General housekeeping.

#### Removed
* Support for PHP < 5.6. PR [#102].
* Support for PHPUnit < 5.7.21. PR [#102].
* Support for expecting PHP deprecations, notices, warnings and error via the `Yoast\PHPUnitPolyfills\Polyfills\ExpectPHPException` trait. PR [#108].
    The trait has been removed completely as PHPUnit 10 no longer supports this functionality.
* The `Yoast\PHPUnitPolyfills\Polyfills\AssertNumericType` trait which is no longer needed now support for PHPUnit < 5.7 has been dropped. PR [#102].
* The `Yoast\PHPUnitPolyfills\Polyfills\ExpectException` trait which is no longer needed now support for PHPUnit < 5.7 has been dropped. PR [#102].
* The `Yoast\PHPUnitPolyfills\Polyfills\AssertFileDirectory` trait which is no longer needed now support for PHPUnit < 5.7 has been dropped. PR [#102].

[#102]: https://github.com/Yoast/PHPUnit-Polyfills/pull/102
[#104]: https://github.com/Yoast/PHPUnit-Polyfills/pull/104
[#108]: https://github.com/Yoast/PHPUnit-Polyfills/pull/108
[#109]: https://github.com/Yoast/PHPUnit-Polyfills/pull/109
[#110]: https://github.com/Yoast/PHPUnit-Polyfills/pull/110
[#116]: https://github.com/Yoast/PHPUnit-Polyfills/pull/116
[#130]: https://github.com/Yoast/PHPUnit-Polyfills/pull/130


## [1.1.5] - 2025-08-10

### Fixed
* PHP 8.5 deprecation notice when using the `AssertAttributeHelper::getProperty()` and `AssertAttributeHelper::getPropertyValue()` methods. PR [#261]

### Changed
* General housekeeping.

[#261]: https://github.com/Yoast/PHPUnit-Polyfills/pull/261


## [1.1.4] - 2025-02-09

This is a maintenance release.

### Changed
* README: updated sub-section about PHPUnit version support with information about the PHPUnit Polyfills 4.x branch. PR [#249]
* General housekeeping.

[#249]: https://github.com/Yoast/PHPUnit-Polyfills/pull/249


## [1.1.3] - 2025-01-08

This is a maintenance release.

### Changed
* README: fixed a few broken badges.
* General housekeeping.


## [1.1.2] - 2024-09-07

This is a maintenance release.

### Changed
* README: updated sub-section about PHPUnit version support with information about the PHPUnit Polyfills 3.x branch. PR [#188]
* General housekeeping.

[#188]: https://github.com/Yoast/PHPUnit-Polyfills/pull/188


## [1.1.1] - 2024-04-05

### Added
* Compatibility fix for running tests using a PHPUnit PHAR file for PHPUnit 8.5.38+ and PHPUnit 9.6.19+. PR [#161].

### Changed
* General housekeeping.

[#161]: https://github.com/Yoast/PHPUnit-Polyfills/pull/161


## [1.1.0] - 2023-08-19

### Added
* `Yoast\PHPUnitPolyfills\Polyfills\AssertObjectProperty` trait to polyfill the `Assert::assertObjectHasProperty()` and `Assert::assertObjectNotHasProperty()` methods as backported from PHPUnit 10.1.0 to PHPUnit 9.6.11. PR [#135].

### Changed
* General housekeeping.

[#135]: https://github.com/Yoast/PHPUnit-Polyfills/pull/135


## [1.0.5] - 2023-03-31

### Fixed
* A custom `$message` parameter passed to an assertion, will no longer overrule an emulated "assertion failed" message, but will be prefixed to it instead. PR [#97].
    This applies to the following polyfills:
    - `assertIsClosedResource()`
    - `assertIsNotClosedResource()`
    - `assertIsReadable()`
    - `assertNotIsReadable()`
    - `assertIsWritable()`
    - `assertNotIsWritable()`
    - `assertDirectoryExists()`
    - `assertDirectoryNotExists()`
    - `assertStringNotContainsString()`
    - `assertStringNotContainsStringIgnoringCase()`

### Changed
* The `develop` branch has been removed. Development will now take place in the `1.x` and `2.x` branches.
* README: links to the PHPUnit manual now point explicitly to the PHPUnit 9.x documentation. PR [#94].
* README: new sub-section about PHPUnit version support. PR [#99].
* README: various minor improvements. PRs [#92], [#93].
* General housekeeping.

[#92]: https://github.com/Yoast/PHPUnit-Polyfills/pull/92
[#93]: https://github.com/Yoast/PHPUnit-Polyfills/pull/93
[#94]: https://github.com/Yoast/PHPUnit-Polyfills/pull/94
[#97]: https://github.com/Yoast/PHPUnit-Polyfills/pull/97
[#99]: https://github.com/Yoast/PHPUnit-Polyfills/pull/99


## [1.0.4] - 2022-11-16

This is a maintenance release.

### Changed
* The `Yoast\PHPUnitPolyfills\Autoload` class is now `final`. PR [#77].
* README: clear up minor language confusion. Props [Phil E. Taylor] and [fredericgboutin-yapla] for pointing it out.
* README: fix links which were broken due to an upstream branch rename. PR [#80].
* Verified PHP 8.2 compatibility.
* General housekeeping.

[#77]: https://github.com/Yoast/PHPUnit-Polyfills/pull/77
[#80]: https://github.com/Yoast/PHPUnit-Polyfills/pull/80


## [1.0.3] - 2021-11-23

### Changed
* General housekeeping.

### Fixed
* The failure message thrown for the `assertIsClosedResource()` and `assertIsNotClosedResource()` assertions will now be more informative, most notably, when the value under test _is_ a closed resource. PR [#65], props [Alain Schlesser] for reporting.

[#65]: https://github.com/Yoast/PHPUnit-Polyfills/pull/65


## [1.0.2] - 2021-10-03

As of version 2.15.0 of the `shivammathur/setup-php` action for GitHub Actions, the PHPUnit Polyfills can be installed directly from this action using the `tools` key.

### Added
* README: FAQ section about installing and using the library via the `shivammathur/setup-php` action. PR [#52].

### Changed
* README: minor textual clarifications and improvements. PRs [#52], [#54], props [Pierre Gordon].
* General housekeeping.

### Fixed
* Autoloader: improved compatibility with packages which create a `class_alias` for the `PHPUnit_Runner_Version` or `PHPUnit\Runner\Version` class. PR [#59].

[#52]: https://github.com/Yoast/PHPUnit-Polyfills/pull/52
[#54]: https://github.com/Yoast/PHPUnit-Polyfills/pull/54
[#59]: https://github.com/Yoast/PHPUnit-Polyfills/pull/59


## [1.0.1] - 2021-08-09

### Added
* The `Yoast\PHPUnitPolyfills\Autoload` class now contains a `VERSION` constant. Issue [#46], PR [#47], props [Pascal Birchler] for the suggestion.
    This version constant can be used by (complex) test setups to verify that the PHPUnit Polyfills which will be loaded, comply with the version requirements for the test suite.

### Changed
* Minor documentation updates. [#43]

[#43]: https://github.com/Yoast/PHPUnit-Polyfills/pull/43
[#46]: https://github.com/Yoast/PHPUnit-Polyfills/issues/46
[#47]: https://github.com/Yoast/PHPUnit-Polyfills/pull/47


## [1.0.0] - 2021-06-21

### Added
* `Yoast\PHPUnitPolyfills\Polyfills\AssertClosedResource` trait to polyfill the `Assert::assertIsClosedResource()` and `Assert::assertIsNotClosedResource()` methods as introduced in PHPUnit 9.3.0. PR [#27].
* `Yoast\PHPUnitPolyfills\Polyfills\AssertObjectEquals` trait to polyfill the `Assert::assertObjectEquals()` method as introduced in PHPUnit 9.4.0. PR [#38].
    The behaviour of the polyfill closely matches the PHPUnit native implementation, but is not 100% the same.
    Most notably, the polyfill will check the type of the returned value from the comparator method instead of enforcing a return type declaration for the comparator method.
* `Yoast\PHPUnitPolyfills\Polyfills\EqualToSpecializations` trait to polyfill the `Assert::equalToCanonicalizing()`, `Assert::equalToIgnoringCase()` and `Assert::equalToWithDelta()` methods as introduced in PHPUnit 9.0.0. PR [#28], props [Marc Siegrist].
* Polyfills for the PHP native `Error` and `TypeError` classes as introduced in PHP 7.0. PR [#36].
* README: FAQ section covering functionality removed from PHPUnit and usage with a Phar.

### Changed
* The minimum supported PHP version has been lowered to PHP 5.4 (was 5.5). PR [#19].
* `XTestCase`: the visibility of the `setUpFixtures()` and the `tearDownFixtures()` methods has been changed to `protected` (was `public`). Issue [#10], PR [#20], props [Mark Baker] for reporting.
* README: re-ordered the sections and various other improvements.
* Initial preparation for PHPUnit 10.0 compatibility.
* General housekeeping.

### Fixed
* Issue [#17] via PR [#18] - `AssertStringContainString`: PHPUnit < 6.4.2 would throw a _"mb_strpos(): empty delimiter"_ PHP warning when the `$needle` passed was an empty string. Props [Gary Jones].

[#10]: https://github.com/Yoast/PHPUnit-Polyfills/issues/10
[#17]: https://github.com/Yoast/PHPUnit-Polyfills/issues/17
[#18]: https://github.com/Yoast/PHPUnit-Polyfills/pull/18
[#19]: https://github.com/Yoast/PHPUnit-Polyfills/pull/19
[#20]: https://github.com/Yoast/PHPUnit-Polyfills/pull/20
[#27]: https://github.com/Yoast/PHPUnit-Polyfills/pull/27
[#28]: https://github.com/Yoast/PHPUnit-Polyfills/pull/28
[#36]: https://github.com/Yoast/PHPUnit-Polyfills/pull/36
[#38]: https://github.com/Yoast/PHPUnit-Polyfills/pull/38


## [0.2.0] - 2020-11-25

### Added
* `Yoast\PHPUnitPolyfills\TestListeners\TestListenerDefaultImplementation`: a cross-version compatible base implementation for `TestListener`s using snake_case method names to replace the PHPUnit native method names.
* `Yoast\PHPUnitPolyfills\Helpers\AssertAttributeHelper` trait containing a `getProperty()` and a `getPropertyValue()` method.
    This is a stop-gap solution for the removal of the PHPUnit `assertAttribute*()` methods in PHPUnit 9.
    It is strongly recommended to refactor your tests/classes in a way that protected and private properties no longer be tested directly as they should be considered an implementation detail.
    However, if for some reason the value of protected or private properties still needs to be tested, this helper can be used to get access to their value.
* `Yoast\PHPUnitPolyfills\Polyfills\AssertNumericType` trait to polyfill the `Assert::assertFinite()`, `Assert::assertInfinite()` and `Assert::assertNan()` methods as introduced in PHPUnit 5.0.0.
* `Yoast\PHPUnitPolyfills\Polyfills\ExpectException` trait to polyfill the `TestCase::expectException()`, `TestCase::expectExceptionMessage()`, `TestCase::expectExceptionCode()` and `TestCase::expectExceptionMessageRegExp()` methods, as introduced in PHPUnit 5.2.0 to replace the `Testcase::setExpectedException()` and the `Testcase::setExpectedExceptionRegExp()` method.
* `Yoast\PHPUnitPolyfills\Polyfills\AssertFileDirectory` trait to polyfill the `Assert::assertIsReadable()`, `Assert::assertIsWritable()` methods and their file/directory based variations, as introduced in PHPUnit 5.6.0.
* `Yoast\PHPUnitPolyfills\TestCases\TestCase`: support for the `assertPreConditions()` and `assertPostConditions()` methods.

### Changed
* The minimum supported PHP version has been lowered to PHP 5.5 (was 5.6).
* The minimum supported PHPUnit version has been lowered to PHP 4.8.36 (was 5.7).
    Note: for PHPUnit 4, only version 4.8.36 is supported, for PHPUnit 5, only PHPUnit >= 5.7.21 is supported.
* Readme: documentation improvements.


## [0.1.0] - 2020-10-26

Initial release.


[Unreleased]: https://github.com/Yoast/PHPUnit-Polyfills/compare/2.0.5...2.x
[2.0.5]: https://github.com/Yoast/PHPUnit-Polyfills/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/Yoast/PHPUnit-Polyfills/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/Yoast/PHPUnit-Polyfills/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/Yoast/PHPUnit-Polyfills/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/Yoast/PHPUnit-Polyfills/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.1.5...2.0.0
[1.1.5]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.0.5...1.1.0
[1.0.5]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/Yoast/PHPUnit-Polyfills/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/Yoast/PHPUnit-Polyfills/compare/0.2.0...1.0.0
[0.2.0]: https://github.com/Yoast/PHPUnit-Polyfills/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/Yoast/PHPUnit-Polyfills/compare/e8f8b7a73737aa9a5974bd9c73d2bd8d09f69873...0.1.0

[Alain Schlesser]: https://github.com/schlessera
[fredericgboutin-yapla]: https://github.com/fredericgboutin-yapla
[Gary Jones]: https://github.com/GaryJones
[Marc Siegrist]: https://github.com/mergeMarc
[Mark Baker]: https://github.com/MarkBaker
[Pascal Birchler]: https://github.com/swissspidy
[Phil E. Taylor]: https://github.com/PhilETaylor
[Pierre Gordon]: https://github.com/pierlon
[Tonya Mork]: https://github.com/hellofromtonya
