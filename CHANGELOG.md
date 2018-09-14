# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added|Changed|Deprecated|Removed|Fixed|Security
Nothing so far

## 4.0.0 - 2018-06-22
### Added
- Support for Symfony 3.x and Twig 2.x
### Removed
- Support for Symfony 2.x and Twig 1.x

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


