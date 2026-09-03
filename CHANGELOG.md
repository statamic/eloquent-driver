# Release Notes

## 5.11.0 (2026-08-11)

### What's fixed
- Preserve the order of bard and replicator sets [#604](https://github.com/statamic/eloquent-driver/issues/604) by @lazerg
- Bump shivammathur/setup-php from 2.37.1 to 2.37.2 in the github-actions group [#600](https://github.com/statamic/eloquent-driver/issues/600) by @dependabot
- Bump actions/checkout from 6.0.3 to 7.0.0 in the github-actions group [#601](https://github.com/statamic/eloquent-driver/issues/601) by @dependabot
- Bump actions/checkout from 7.0.0 to 7.0.1 in the github-actions group [#602](https://github.com/statamic/eloquent-driver/issues/602) by @dependabot



## 5.10.0 (2026-06-17)

### What's fixed
- Use closest origin that has field localized [#598](https://github.com/statamic/eloquent-driver/issues/598) by @ryanmitchell
- Fix tests [#597](https://github.com/statamic/eloquent-driver/issues/597) by @duncanmcclean
- Harden workflows [#596](https://github.com/statamic/eloquent-driver/issues/596) by @duncanmcclean
- Bump actions/checkout from 6.0.2 to 6.0.3 in the github-actions group [#599](https://github.com/statamic/eloquent-driver/issues/599) by @dependabot



## 5.9.0 (2026-05-18)

### What's new
- Allow export selection in `eloquent:export-blueprints` [#594](https://github.com/statamic/eloquent-driver/issues/594) by @ryanmitchell

### What's fixed
- Fix export navs command [#595](https://github.com/statamic/eloquent-driver/issues/595) by @ryanmitchell
- Pin GitHub Actions to commit SHAs and add Dependabot config [#590](https://github.com/statamic/eloquent-driver/issues/590) by @duncanmcclean
- Bump the github-actions group with 2 updates [#591](https://github.com/statamic/eloquent-driver/issues/591) by @dependabot



## 5.8.0 (2026-05-14)

### What's new
- Preserve fallback blueprint [#589](https://github.com/statamic/eloquent-driver/issues/589) by @ryanmitchell

### What's fixed
- Change revisions message column to be text on new installs [#583](https://github.com/statamic/eloquent-driver/issues/583) by @ryanmitchell
- Fix stale folder issues in sync assets command [#581](https://github.com/statamic/eloquent-driver/issues/581) by @ryanmitchell
- Forget the all-list Blink cache when saving containers, taxonomies and globals [#586](https://github.com/statamic/eloquent-driver/issues/586) by @BartWaardenburg



## 5.7.0 (2026-05-01)

### What's fixed
- Fix `Submission::date()` to fall back to id-derived timestamp [#577](https://github.com/statamic/eloquent-driver/issues/577) by @SUXUMI
- Use `saveQuietly()` in `ExportEntries` to prevent infinite loop [#569](https://github.com/statamic/eloquent-driver/issues/569) by @lunitrixx
- Asset containers should only store `search_index` name [#573](https://github.com/statamic/eloquent-driver/issues/573) by @ryanmitchell
- Ensure template is only stored when it's not localized [#571](https://github.com/statamic/eloquent-driver/issues/571) by @ryanmitchell



## 5.6.3 (2026-04-17)

### What's fixed
- Add missing serializable classes [#568](https://github.com/statamic/eloquent-driver/issues/568) by @duncanmcclean



## 5.6.2 (2026-04-07)

### What's fixed
- Use configured classes in serializable classes config [#566](https://github.com/statamic/eloquent-driver/issues/566) by @duncanmcclean



## 5.6.1 (2026-04-07)

### What's fixed
- Add serializable classes to allowlist [#565](https://github.com/statamic/eloquent-driver/issues/565) by @duncanmcclean



## 5.6.0 (2026-04-02)

### What's fixed
- Ensure forms fire the same events as core [#563](https://github.com/statamic/eloquent-driver/issues/563) by @ryanmitchell



## 5.5.0 (2026-03-27)

### What's new
- Add blink caching to collection and forms [#557](https://github.com/statamic/eloquent-driver/issues/557) by @ryanmitchell



## 5.4.2 (2026-03-18)

### What's fixed
- Postgres should use timestamp not datetime in casts [#559](https://github.com/statamic/eloquent-driver/issues/559) by @ryanmitchell



## 5.4.1 (2026-03-12)

### What's fixed
- Fix filtering by date [#556](https://github.com/statamic/eloquent-driver/issues/556) by @ryanmitchell



## 5.4.0 (2026-03-12)

### What's fixed
- Make taxonomy queries more performant [#552](https://github.com/statamic/eloquent-driver/issues/552) by @ryanmitchell
- Make entries count more performant when using eloquent for terms and entries [#553](https://github.com/statamic/eloquent-driver/issues/553) by @ryanmitchell
- Fixes from code analysis [#550](https://github.com/statamic/eloquent-driver/issues/550) by @ryanmitchell
- Require `spatie/laravel-ray` in dev [#554](https://github.com/statamic/eloquent-driver/issues/554) by @duncanmcclean



## 5.3.1 (2026-03-09)

### What's new
- Supports Laravel 13 [#546](https://github.com/statamic/eloquent-driver/issues/546) by @duncanmcclean

### What's fixed
- Use `RunsUpdateScripts` trait from core [#549](https://github.com/statamic/eloquent-driver/issues/549) by @duncanmcclean



## 5.3.0 (2026-02-23)

### What's fixed
- Fix and improve asset and collection export commands [#548](https://github.com/statamic/eloquent-driver/issues/548) by @sjongejan
- Run GitHub Actions workflows only once [#547](https://github.com/statamic/eloquent-driver/issues/547) by @duncanmcclean



## 5.2.0 (2026-02-17)

### What's new
- Support `entry_class` option on collections [#545](https://github.com/statamic/eloquent-driver/issues/545) by @duncanmcclean



## 5.1.3 (2026-02-16)

### What's fixed
- Only publish global variables origin migration when table exists [#544](https://github.com/statamic/eloquent-driver/issues/544) by @duncanmcclean



## 5.1.2 (2026-02-12)

### What's fixed
- Fix working copy not deleted on entry publish [#542](https://github.com/statamic/eloquent-driver/issues/542) by @morhi



## 5.1.1 (2026-02-04)

### What's fixed
- Ensure we don't only select taxonomy when we actually want all term columns [#536](https://github.com/statamic/eloquent-driver/issues/536) by @ryanmitchell



## 5.1.0 (2026-02-04)

### What's fixed
- Fix taxonomy null caching and ensure taxonomy column in term queries [#534](https://github.com/statamic/eloquent-driver/issues/534) by @ryanmitchell



## 5.0.0 (2026-01-28)

### What's new
- Statamic 6 support
- Addon Settings [#469](https://github.com/statamic/eloquent-driver/issues/469) by @duncanmcclean
- Separate globals config and content [#411](https://github.com/statamic/eloquent-driver/issues/411) by @duncanmcclean
- Allow globals and variables to be exported separately [#431](https://github.com/statamic/eloquent-driver/issues/431) by @ryanmitchell
- Revisions query builder [#514](https://github.com/statamic/eloquent-driver/issues/514) by @ryanmitchell

### What's fixed
- Dropped support for Laravel 11 & PHP 8.2
- Convert `$casts` property to method [#400](https://github.com/statamic/eloquent-driver/issues/400) by @duncanmcclean
- Drop support for old asset container options [#505](https://github.com/statamic/eloquent-driver/issues/505) by @duncanmcclean



## 5.0.0-beta.1 (2026-01-13)

### What's new
- Statamic 6 support
- Addon Settings [#469](https://github.com/statamic/eloquent-driver/issues/469) by @duncanmcclean
- Separate globals config and content [#411](https://github.com/statamic/eloquent-driver/issues/411) by @duncanmcclean
- Allow globals and variables to be exported separately [#431](https://github.com/statamic/eloquent-driver/issues/431) by @ryanmitchell
- Revisions query builder [#514](https://github.com/statamic/eloquent-driver/issues/514) by @ryanmitchell
- Always bind models [#520](https://github.com/statamic/eloquent-driver/issues/520) by @ryanmitchell

### What's fixed
- Drop Laravel 10 support [#399](https://github.com/statamic/eloquent-driver/issues/399) by @duncanmcclean
- Drop support for Laravel 11 & PHP 8.2 [#515](https://github.com/statamic/eloquent-driver/issues/515) by @duncanmcclean
- Convert `$casts` property to method [#400](https://github.com/statamic/eloquent-driver/issues/400) by @duncanmcclean
- Drop support for old asset container options [#505](https://github.com/statamic/eloquent-driver/issues/505) by @duncanmcclean
- Fix `GlobalVariablesTest` [#507](https://github.com/statamic/eloquent-driver/issues/507) by @duncanmcclean
