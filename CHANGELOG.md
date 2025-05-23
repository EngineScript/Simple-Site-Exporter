# Changelog for EngineScript: Simple Site Exporter

## 1.5.9 - May 3, 2025
### Security Enhancements
- Reduced export file auto-deletion time from 1 hour to 5 minutes for improved security
- Removed dependency on external systems for file security management

### Improvements
- Simplified user interface by removing environment-specific messaging
- Enhanced self-containment of the plugin's security features

## 1.5.8 - May 1, 2025
### Code Quality Improvements
- Refactored validation functions to eliminate code duplication
- Created shared `sse_validate_export_file()` function for both download and deletion operations
- Improved code maintainability while preserving security controls

### Security Enhancements
- Updated license to GPL v3
- Enhanced file path validation
- Strengthened regex pattern for export file validation
- Added proper documentation for security-related functions

## 1.5.7 - April 25, 2025
### Security Enhancements
- Implemented comprehensive file path validation function to prevent directory traversal attacks
- Added referrer checks for download and delete operations
- Enhanced file pattern validation with stronger regex patterns
- Improved path display in admin interface using [wp-root] placeholder for better security
- Added security headers to file download operations
- Implemented strict comparison operators throughout the plugin
- Consistently applied sanitization to nonce values before verification

### Code Improvements
- Standardized input sanitization and validation across all user inputs
- Enhanced error logging for security-related events
- Applied path normalization for consistent security checks
- Improved documentation with security considerations

## 1.5.6 - April 15, 2025
### Features
- Added more detailed logging for export operations
- Improved error handling during file operations

### Bug Fixes
- Fixed potential memory issues during export of large sites
- Resolved a race condition in the scheduled deletion process

## 1.5.5 - March 2, 2025
### Features
- Added automatic deletion of export files after 1 hour
- Implemented secure download mechanism through WordPress admin
- Added ability to manually delete export files

### Improvements
- Enhanced file export process with better error handling
- Improved progress feedback during export operations

## 1.5.4 - February 10, 2025
### Features
- Added deletion request validation and confirmation
- Implemented redirect after deletion with status notification

### Bug Fixes
- Fixed database export issues on some hosting environments

## 1.5.3 - January 5, 2025
### Features
- Added manual export file deletion
- Enhanced security for file operations

### Improvements
- Better error handling for WP-CLI operations
- Improved user interface with clearer notifications

## 1.5.2 - December 12, 2024
### Features
- Added WP-CLI integration for database exports
- Implemented fallback methods for database exports

### Bug Fixes
- Fixed ZIP creation issues on certain hosting environments

## 1.5.1 - November 15, 2024
### Improvements
- Enhanced ZIP file creation process
- Improved handling of large files
- Added exclusion for cache and temporary directories

## 1.5.0 - October 20, 2024
### Initial Release
- Basic site export functionality
- Database and file export
- Simple admin interface