# Changelog

## [1.4.0] - 2026-09-30

### Fixed

  - All tiles were not deleted when deleting a 360 image
  - Bugs affecting the self-hosting of an exported tour
  - Update dependencies with npm

### Added

  - Admin tool to delete a user and all their data (CLI): src/admin/deleteUser.php
  - Admin tool to delete images tiles that should have been deleted: src/admin/fixDeleteTiles.php

## [1.3.0] - 2025-09-25

### Fixed

  - Replacing HTML2Canvas library with snapDOM to fix a bug with VR headset browsers

## [1.2.0] - 2024-12-04

### Added

 - **BREAKING** New parameter in /src/config.php to set memory limit for the export feature
 - **BREAKING** PHP dependencies added to the PHP image; TLS certs created automatically ; You should restart the containers and force the rebuild of the image, eg.:  
  ```docker compose --profile prod --build --force-recreate -d```
 - The text in POIs can now be written using markdown syntax and accept the same range of characters as the browser (eg.: emojis)

## [1.1.0] - 2024-11-29

### Added

 - **BREAKING** Allowing the copy of a tour between user acounts. You'll need to update the database schema.
 - Creation of a tour with one unique 360° photo
 - Dev only feature to create and connect a user without OIDC

## [1.0.0] - 2024-10-10

### Added

 - Initial release
 - Creation, modification, sharing, export, deletion of a tour
 - Addition of point of interest with text and image
 - OIDC connection
