# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Laravel 13 support
- `PushMessage::withHighPriority()`
- String external user IDs in `toUser()` / `toExternalUserIds()`
- OneSignal notification channel for Laravel
- Fluent message builder API
- Event system for notification lifecycle
- Custom exception handling
- PSR-3 logging integration
- Comprehensive configuration options
- Dependency injection architecture
- Full test coverage
- PHP 8.3 type safety

### Changed
- External user IDs are sent with `include_aliases` (`external_id`) and `target_channel: push` instead of the deprecated `include_external_user_ids` OneSignal parameter
- External user IDs cannot be combined with segments or filters in the same message (OneSignal rejects it); an `InvalidMessageException` is thrown
- External user IDs are always sent as strings
- `NotificationSending` listeners reading `$data['include_external_user_ids']` must read `$data['include_aliases']['external_id']`

### Deprecated
- `PushMessage::withHightPriority()`: use `withHighPriority()`. Triggers an `E_USER_DEPRECATED` notice, removed in 3.0

### Fixed
- Messages without TTL, priority or Android channel ID were rejected by the OneSignal SDK
- `withNotificationId()` was always overwritten with `-1`
- `build()` no longer mutates the message
- `http.timeout`, `http.retry` and `logging.level` settings are now applied
- `NotificationFailed` event is dispatched when `throw_exceptions` is disabled
- Missing `logging` config key no longer breaks client resolution
- New OneSignal API keys (`os_v2_...`) now reach the right endpoint (requires `norkunas/onesignal-php-api` ^2.16)

### Removed
- Laravel 11 support: every 11.x release has unpatched security advisories

### Security
- Logs no longer include notification contents, external user IDs or filters
- CI runs `composer audit` and workflows use least-privilege `GITHUB_TOKEN` permissions
