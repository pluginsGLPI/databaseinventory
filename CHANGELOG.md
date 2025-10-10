# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [UNRELEASED]

### Fixed

- Do not create sessions on CRON context

## [1.1.0] - 2025-09-29

### Added

- GLPI 11 compatibility

### Fixed

- Fix `Undefined array key "id"` when computer not already linkedo agent

## [1.0.4] - 2025-09-04

### Fixed

- Fix foreign key constraint in SQL query.
- Fix `Undefined array key "glpiname"` during database inventory task.
- Fix SQL error when add computer to static group .

## [1.0.3] - 2025-07-10

### Fixed

- Do not disclose `password` from `form` input.
- Improved access control checks when requesting database inventory.

## [1.0.2] - 2024-12-13

### Fixed

- Fix `displayTabContentForItem` for `PluginDatabaseinventoryContactLog`
- Fix foreign key constraint in `where` clause.

## [1.0.1] - 2024-12-11

### Fixed

- Fix foreign key constraint in SQL query.
