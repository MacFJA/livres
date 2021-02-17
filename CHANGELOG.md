# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- New lines are preserved in teh Book details view
- Superfluous space when displaying a Book with a series
- Cover cache not update on book edition

### Added

- Add Opcache extension in dev
- New RediSearch client lib
- New SecurityChecker
- Display a button to launch a search when there are more than 5 results in search preview
- API endpoint for all data manipulation

### Changed

- Upgrade Symfony version from `5.1` to `5.2`
- Update Flex recipes
- Upgrade to Composer v2 (remove unsupported composer plugin)
- Minimum PHP changed from `7.2.5` to `7.3`
- TLD in dev environment changed from `.docker` to `.localhost`
- Upgrade version of RediSearch server to `2.0.x`
- Upgrade EasyAdmin from `^2.3` to `^3.0`
- Update front to use API

### Removed

- Remove composer plugin not compatible with Composer v2
- Remove deprecated SensioLabs SecurityChecker
- Remove abandoned RediSearch PHP client
- Unused controllers

## [1.1.1] - 2020-09-12

### Fixed

- Unprotected class usage
- Missing translations in the build process

## [1.1.0] - 2020-09-12

### Added

- Add more files in the cleanup (`make clean`) process
- Add a demo database (`make demo`)
- Translate the application (EN + FR)

### Changed

- Downgrade ESLint to avoid unwanted `no-unused-vars` false-positive error
- Change application page title from "Welcome!" to "Livres"
- Upgrade Symfony dependencies

### Fixed

- CircleCI configuration
- Cleanup (`make clean`) not working if files aren't generated
- Fix release date of version 1.0.0 being two days early
- Allow application building (`make build`) to work on a fresh install
- Warning of missing `docker-compose-traefik.yml` when building application (`make build`)
- Fix a small typo in the README file
- Missing dev dependencies (tools) on fresh install

## [1.0.0] - 2020-08-18

Full rewrite of the application.

### Changed

- Use external library for book data provider
- Change frontend to Svelte
- Update to Symfony 5.1

## [0.0.1]

First version

[Unreleased]: https://github.com/MacFJA/livres/compare/1.1.1...HEAD
[1.1.1]: https://github.com/MacFJA/livres/releases/tag/1.1.1
[1.1.0]: https://github.com/MacFJA/livres/releases/tag/1.1.0
[1.0.0]: https://github.com/MacFJA/livres/releases/tag/1.0.0
[0.0.1]: https://github.com/MacFJA/livres/releases/tag/0.0.1