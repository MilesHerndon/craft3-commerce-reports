# Commerce Reports Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.0.0 - 2019-01-29
### Added
- Initial release

## 1.0.1 - 2019-02-18
### Fixed
- Fix issue where looping through variant products throws error if the item is disabled.

## 1.0.2 - 2019-02-22
### Fixed
- Fix bug with wholesale price not retrieved for variants

## 1.0.3 - 2019-03-06
### Changed
- Update time of day when batch reports are started and ended

## 1.0.4 - 2019-03-06
### Changed
- Update README with accurate information

## 1.1.0 - 2019-03-29
### Added
- Add helpers for common functionality
- Add table to record when product variant quantities are changed
- Add report to query and return quantity changes
- Add widget to run inventory quantity adjustments
- Add service specific to inventory functionality

### Changed
- Refactor CommerceReportsService into separate helper and service files

### Removed
- Remove CommerceReportsService
