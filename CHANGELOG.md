# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added|Changed|Deprecated|Removed|Fixed|Security
Nothing so far

## 5.3.0 - 2022-10-03
### Fixed
- Fixed admin code to be forward compatible with Sonata Admin v4.

## 5.2.2 - 2022-09-30
### Changed
- Start using recommended `SymfonyStyle` for output in `LoadCommand`

## 5.2.1 - 2022-05-02
### Fixed
- Fixed commands
- Pass root name to TreeBuilder constructor and call `getRootNode` instead of deprecated `->root()`

## 5.2.0 - 2021-12-07
### Added
- Support for PHP 8
### Removed
- Support for PHP 7.1

## 5.1.0 - 2021-11-24
### Added
- State-filter on `MessageAdmin` to query the state of `MessageTranslation`

## 5.0.5 - 2020-11-19
### Fixed
- Missing `controller.service_arguments` for `RcController` so it was not callable.

## 5.0.4 - 2020-10-20
### Removed
- Unneeded dependency declaration for `doctrine/doctrine-bundle`.

## 5.0.3 - 2020-08-28
### Fixed
- Added a Compiler Pass to place the .db files last to have the database entries processed last (to override existing messages)

## 5.0.2 - 2020-07-13
### Fixed
- Forward merge from 4.1.3

## 5.0.1 - 2020-05-25
### Fixed
- Forward merge from 4.1.2

## 5.0.0 - 2020-05-19
### Added
- Support for Symfony 4.x
### Removed
- Support for Symfony 3.x
### Changed
- Removed Zicht(Test)/Bundle/MessagesBundle/ directory depth: moved all code up directly into src/ and test/

## 4.1.3 - 2020-07-10
### Fixed
- Translation cleanup
- Fix message state (i.e. imported, user, unknown)

## 4.1.2 - 2020-05-22
### Fixed
- Made the `zicht:messages:load` command significantly less verbose

## 4.1.1 - 2020-05-15
### Changed
- Switched from PSR-0 to PSR-4 autoloading

## 4.1.0 - 2020-04-30
### Added
- Integration for API-based translation of `.yaml` and `.xliff` files. See README for more info.

## 4.0.2 - 2019-03-08
### Changed
- Updated admin message state choices (Symfony forced flipping the
  keys and values in choice form field options)

## 4.0.1 - 2018-09-14
### Added
- There is now a RC (Remove Control) route available that clears the
  translation cache. This is in-line with how Redis and Varnish cache
  can be manually cleared in the CMS.
- Added english translations

## 4.0.0 - 2018-06-22
### Added
- Support for Symfony 3.x and Twig 2.x
### Removed
- Support for Symfony 2.x and Twig 1.x

## 3.1.1 - 2018-09-14
### Added
- Added english translations

## 3.1.0 - 2018-06-25
### Added
- There is now a RC (Remove Control) route available that clears the
  translation cache.  This is in-line with how Redis and Varnish cache
  can be manually cleared in the CMS.

## 3.0.0 - 2018-01-16
### Changed
From this version on the minimal PHP requirement is `7.0`

## 2.5.0
### Added
* Adds a `--sync` flag to the zicht:messages:load command which updates the
  state flag of all translations in the database to the correct value.


