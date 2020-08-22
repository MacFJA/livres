# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add more files in the cleanup (`make clean`) process

### Changed

- Downgrade ESLint to avoid unwanted `no-unused-vars` false-positive error

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

[Unreleased]: https://github.com/MacFJA/livres/compare/1.0.0...HEAD
[1.0.0]: https://github.com/MacFJA/livres/releases/tag/1.0.0
[0.0.1]: https://github.com/MacFJA/livres/releases/tag/0.0.1