# Release Notes

## 4.36.3 (2026-10-05)

### What's changed
- [4.x] Improve ordering [#626](https://github.com/statamic/eloquent-driver/pull/626) by @jasonvarga



## 4.36.2 (2026-06-03)

### What's changed
- [4.x] Use closest origin that has field localized to determine if a field can be forgotten in `makeModelFromContract` method of `Entry` [#587](https://github.com/statamic/eloquent-driver/pull/587) by @Boefjim



## 4.36.1 (2026-03-27)

### What's changed
- Fix taxonomy null caching and ensure taxonomy column in term queries [#533](https://github.com/statamic/eloquent-driver/pull/533) by @BartWaardenburg
- Patch postgres orderby fix (c316473) from 5.x into 4.x [#560](https://github.com/statamic/eloquent-driver/pull/560) by @nadinengland



## 4.36.0 (2026-01-22)

### What's changed
- PHP 8.5 Compatibility [#525](https://github.com/statamic/eloquent-driver/pull/525) by @duncanmcclean
- Bring back asset::move [#530](https://github.com/statamic/eloquent-driver/pull/530) by @ryanmitchell



## 4.35.2 (2025-12-12)

### What's changed
- Fix exporting when using UUIDs [#524](https://github.com/statamic/eloquent-driver/pull/524) by @ryanmitchell



## 4.35.1 (2025-11-24)

### What's changed
- Fix exporting of entries [#516](https://github.com/statamic/eloquent-driver/pull/516) by @ryanmitchell



## 4.35.0 (2025-10-30)

### What's changed
- Support whereHas etc on Entries and Terms [#512](https://github.com/statamic/eloquent-driver/pull/512) by @ryanmitchell



## 4.34.0 (2025-10-22)

### What's changed
- Remove stache stores for repositories we control [#433](https://github.com/statamic/eloquent-driver/pull/433) by @ryanmitchell
- Fix sorting by numbers when using collection `in` condition [#513](https://github.com/statamic/eloquent-driver/pull/513) by @jacksleight



## 4.33.0 (2025-10-14)

### What's changed
- Implement `EntryRespository::whereInId()` [#417](https://github.com/statamic/eloquent-driver/pull/417) by @nadinengland
- Rework form exports command to not require eloquent repo to be enabled [#500](https://github.com/statamic/eloquent-driver/pull/500) by @ryanmitchell
- Deleted directory items on S3 container still in Statamic Assets DB after running sync-assets [#511](https://github.com/statamic/eloquent-driver/pull/511) by @nicbovee



## 4.32.0 (2025-09-26)

### What's changed
- Require spatie/laravel-ray in dev [#506](https://github.com/statamic/eloquent-driver/pull/506) by @duncanmcclean
- Updates form_submission migration stubs to match the package migrations  [#508](https://github.com/statamic/eloquent-driver/pull/508) by @adampatterson
- Support inject/cascade on taxonomies [#504](https://github.com/statamic/eloquent-driver/pull/504) by @ryanmitchell



## 4.31.0 (2025-09-11)

### What's changed
- Load model from config [#494](https://github.com/statamic/eloquent-driver/pull/494) by @kevinmeijer97
- Remove asset clearCaches function [#492](https://github.com/statamic/eloquent-driver/pull/492) by @ryanmitchell
- Fix speed regression on model status query by going through eloquent model [#497](https://github.com/statamic/eloquent-driver/pull/497) by @ryanmitchell



## 4.30.2 (2025-08-22)

### What's changed
- Fix pluck() regression [#491](https://github.com/statamic/eloquent-driver/pull/491) by @ryanmitchell



## 4.30.1 (2025-08-22)

### What's changed
- Fix entries date index update script and migration [#485](https://github.com/statamic/eloquent-driver/pull/485) by @ryanmitchell
- Tidy up entries import [#480](https://github.com/statamic/eloquent-driver/pull/480) by @ryanmitchell
- Fix ordering assets by integers, floats, and date fields [#487](https://github.com/statamic/eloquent-driver/pull/487) by @duncanmcclean
- Use `QueriesJsonColumns` trait in entry query builder [#488](https://github.com/statamic/eloquent-driver/pull/488) by @duncanmcclean
- Update code style workflows [#489](https://github.com/statamic/eloquent-driver/pull/489) by @duncanmcclean



## 4.30.0 (2025-07-24)

### What's changed
- Add fieldsets and submissions to about command [#478](https://github.com/statamic/eloquent-driver/pull/478) by @ryanmitchell
- Always load model for assets [#479](https://github.com/statamic/eloquent-driver/pull/479) by @godismyjudge95



## 4.29.1 (2025-07-21)

### What's changed
- FormSubmission transform should return a DataCollection [#477](https://github.com/statamic/eloquent-driver/pull/477) by @ryanmitchell



## 4.29.0 (2025-07-15)

### What's changed
- Support pluck() on term query builder [#476](https://github.com/statamic/eloquent-driver/pull/476) by @ryanmitchell



## 4.28.1 (2025-07-13)

### What's changed
- [4.x] Fix empty localized values not being set properly [#474](https://github.com/statamic/eloquent-driver/pull/474) by @Jade-GG



## 4.28.0 (2025-07-10)

### What's changed
- Respect configured namespaces for blueprint import [#470](https://github.com/statamic/eloquent-driver/pull/470) by @macaws
- Prevent global variables merging origin data [#468](https://github.com/statamic/eloquent-driver/pull/468) by @ryanmitchell
- Conditionally query against order column in Sites.php [#473](https://github.com/statamic/eloquent-driver/pull/473) by @hailwood



## 4.27.0 (2025-07-04)

### What's changed
- Add order to sites column [#466](https://github.com/statamic/eloquent-driver/pull/466) by @ryanmitchell



## 4.26.0 (2025-07-03)

### What's changed
- enable order by date range column [#461](https://github.com/statamic/eloquent-driver/pull/461) by @faltjo
- Use asset container contents cache store [#464](https://github.com/statamic/eloquent-driver/pull/464) by @ryanmitchell



## 4.25.2 (2025-06-17)

### What's changed
- Use firstOrNew() on asset save when asset has no model [#457](https://github.com/statamic/eloquent-driver/pull/457) by @ryanmitchell



## 4.25.1 (2025-06-13)

### What's changed
- Ensure empty folders are included in asset folder listing [#454](https://github.com/statamic/eloquent-driver/pull/454) by @ryanmitchell



## 4.25.0 (2025-06-11)

### What's changed
- Store model reference on assets [#452](https://github.com/statamic/eloquent-driver/pull/452) by @ryanmitchell



## 4.24.1 (2025-06-11)

### What's changed
- Asset blink should cache the asset not the model [#449](https://github.com/statamic/eloquent-driver/pull/449) by @ryanmitchell
- Don't blink asset-meta-exists if the file doesn't exist [#451](https://github.com/statamic/eloquent-driver/pull/451) by @ryanmitchell



## 4.24.0 (2025-06-09)

### What's changed
- Prevent n+1 queries on asset meta exists [#446](https://github.com/statamic/eloquent-driver/pull/446) by @ryanmitchell



## 4.23.4 (2025-06-07)

### What's changed
- Get AssetContainer::folders() from eloquent table [#444](https://github.com/statamic/eloquent-driver/pull/444) by @ryanmitchell



## 4.23.3 (2025-06-05)

### What's changed
- Ensure global variables save through repository [#442](https://github.com/statamic/eloquent-driver/pull/442) by @ryanmitchell



## 4.23.2 (2025-06-05)

### What's changed
- Fix bug in term queries [#438](https://github.com/statamic/eloquent-driver/pull/438) by @ryanmitchell
- Fix submission is newly created on save [#440](https://github.com/statamic/eloquent-driver/pull/440) by @Lampone



## 4.23.1 (2025-06-04)

### What's changed
- Fix looping issues in imports [#436](https://github.com/statamic/eloquent-driver/pull/436) by @ryanmitchell



## 4.23.0 (2025-05-30)

### What's changed
- Remove ensure associations [#434](https://github.com/statamic/eloquent-driver/pull/434) by @afonic
- Taxonomy wheres should use entry query builder, not stache [#432](https://github.com/statamic/eloquent-driver/pull/432) by @ryanmitchell



## 4.22.0 (2025-05-27)

### What's changed
- Add index to the date column [#429](https://github.com/statamic/eloquent-driver/pull/429) by @afonic



## 4.21.2 (2025-05-21)

### What's changed
- Ensure subfolder starts with / for comparison when syncing assets [#427](https://github.com/statamic/eloquent-driver/pull/427) by @ryanmitchell
- `EntryQueryBuilder::where()`: Forward all arguments to parent, instead of passing them manually [#425](https://github.com/statamic/eloquent-driver/pull/425) by @duncanmcclean



## 4.21.1 (2025-04-15)

### What's changed
- Fix template not being set correctly [#415](https://github.com/statamic/eloquent-driver/pull/415) by @ryanmitchell



## 4.21.0 (2025-04-08)

### What's changed
- Store updated_at in terms as timestamp [#409](https://github.com/statamic/eloquent-driver/pull/409) by @ryanmitchell
- Prevent null values from being saved to data [#412](https://github.com/statamic/eloquent-driver/pull/412) by @ryanmitchell
- Fix rolling back `modify_form_submissions` migration [#413](https://github.com/statamic/eloquent-driver/pull/413) by @Boefjim
- Bring FormSubmission save/delete inline with core [#419](https://github.com/statamic/eloquent-driver/pull/419) by @ryanmitchell



## 4.20.2 (2025-03-12)

### What's changed
- Ensure template is assigned when it's present in data [#405](https://github.com/statamic/eloquent-driver/pull/405) by @ryanmitchell
- Ensure namespaced fieldsets are returned [#407](https://github.com/statamic/eloquent-driver/pull/407) by @ryanmitchell



## 4.20.1 (2025-03-11)

### What's changed
- Ensure template is stored when importing entries [#404](https://github.com/statamic/eloquent-driver/pull/404) by @ryanmitchell



## 4.20.0 (2025-02-26)

### What's changed
- Supports Laravel 12 [#401](https://github.com/statamic/eloquent-driver/pull/401) by @duncanmcclean
- Adds Taxonomy export arguments, matching import [#393](https://github.com/statamic/eloquent-driver/pull/393) by @adampatterson
- Adjusted the length of the form handle column in `form_submissions` table [#395](https://github.com/statamic/eloquent-driver/pull/395) by @Lenitr
- Revisions are now returned in chronological order [#396](https://github.com/statamic/eloquent-driver/pull/396) by @faltjo
- Updated dedicated column docs [#397](https://github.com/statamic/eloquent-driver/pull/397) by @duncanmcclean



## 4.19.1 (2024-12-12)

### What's changed
- Ensure we store the latest Entry uri [#392](https://github.com/statamic/eloquent-driver/pull/392) by @ryanmitchell
- Fix issues with importing and exporting forms [#390](https://github.com/statamic/eloquent-driver/pull/390) by @ryanmitchell



## 4.19.0 (2024-11-29)

### What's changed
- Laravel Pint [#386](https://github.com/statamic/eloquent-driver/pull/386) by @duncanmcclean
- Add collection tests [#385](https://github.com/statamic/eloquent-driver/pull/385) by @duncanmcclean
- PHP 8.4 Support [#387](https://github.com/statamic/eloquent-driver/pull/387) by @duncanmcclean



## 4.18.0 (2024-11-20)

### What's changed
- Apply and restore sort direction on asset containers [#382](https://github.com/statamic/eloquent-driver/pull/382) by @daun
- Prevent creation of duplicate terms on slug change [#384](https://github.com/statamic/eloquent-driver/pull/384) by @daun



## 4.17.1 (2024-11-19)

### What's changed
- Allow non-CP collection config fields to persist [#381](https://github.com/statamic/eloquent-driver/pull/381) by @ryanmitchell



## 4.17.0 (2024-11-13)

### What's changed
- Optimize taxonomy <> collection queries [#376](https://github.com/statamic/eloquent-driver/pull/376) by @TheBnl



## 4.16.2 (2024-11-08)

### What's changed
- Add site database table exists [#375](https://github.com/statamic/eloquent-driver/pull/375) by @jhhazelaar



## 4.16.1 (2024-11-01)

### What's changed
- Fallback to locale or 'en' when site lang is not present [#372](https://github.com/statamic/eloquent-driver/pull/372) by @ryanmitchell



## 4.16.0 (2024-10-28)

### What's changed
- Empty folders readme [#366](https://github.com/statamic/eloquent-driver/pull/366) by @ryanmitchell
- Use variables model defined in config [#370](https://github.com/statamic/eloquent-driver/pull/370) by @ryanmitchell



## 4.15.2 (2024-10-05)

### What's changed
- Fix entries export to use correct model, blueprint column, and set ID when exporting UUIDs [#362](https://github.com/statamic/eloquent-driver/pull/362) by @ryanmitchell



## 4.15.1 (2024-10-04)

### What's changed
- Prevent freezing of ImportAssets for asset meta data when importing a large number of files [#360](https://github.com/statamic/eloquent-driver/pull/360) by @faltjo



## 4.15.0 (2024-09-26)

### What's changed
- Ensure asset container contents folder check finishes with / [#356](https://github.com/statamic/eloquent-driver/pull/356) by @ryanmitchell
- Refactor globals export [#358](https://github.com/statamic/eloquent-driver/pull/358) by @ryanmitchell



## 4.14.3 (2024-09-12)

### What's changed
- The root folder is / and not empty otherwise it's skipped [#352](https://github.com/statamic/eloquent-driver/pull/352) by @danielsmink



## 4.14.2 (2024-09-11)

### What's changed
- Corrects issue with importing/exporting blueprints on Windows [#351](https://github.com/statamic/eloquent-driver/pull/351) by @JohnathonKoster



## 4.14.1 (2024-09-06)

### What's changed
- Prevent `lang` and `attributes` being exported with empty values in the `eloquent:export-sites` command [#349](https://github.com/statamic/eloquent-driver/pull/349) by @duncanmcclean



## 4.14.0 (2024-08-28)

### What's changed
- Make term <> collection query more performant [#345](https://github.com/statamic/eloquent-driver/pull/345) by @ryanmitchell



## 4.13.0 (2024-08-22)

### What's changed
- Store form data [#342](https://github.com/statamic/eloquent-driver/pull/342) by @ryanmitchell
- Improve data column mapping docs [#340](https://github.com/statamic/eloquent-driver/pull/340) by @duncanmcclean
- Empty namespace should default to file when namespaces is not `all` [#336](https://github.com/statamic/eloquent-driver/pull/336) by @ryanmitchell
- Fix for vendor prefixed fieldset handles [#339](https://github.com/statamic/eloquent-driver/pull/339) by @ryanmitchell



## 4.12.3 (2024-08-07)

### What's changed
- Only run RelateFormSubmissionsByHandle when we have form submissions [#334](https://github.com/statamic/eloquent-driver/pull/334) by @ryanmitchell



## 4.12.2 (2024-08-07)

### What's changed
- Fall back to boolean instead of null [#332](https://github.com/statamic/eloquent-driver/pull/332) by @dnwjn



## 4.12.1 (2024-08-07)




## 4.12.0 (2024-07-31)

### What's changed
- Only retrieve forms once instead of for every item [#330](https://github.com/statamic/eloquent-driver/pull/330) by @dnwjn
- Apply changes from new version of pint [#329](https://github.com/statamic/eloquent-driver/pull/329) by @ryanmitchell



## 4.11.1 (2024-07-24)

### What's changed
- Drop indexes before dropping the column on asset and form submission migrations [#328](https://github.com/statamic/eloquent-driver/pull/328) by @georgecoca



## 4.11.0 (2024-07-22)

### What's changed
- Support canSelectAcrossSites on navs [#327](https://github.com/statamic/eloquent-driver/pull/327) by @ryanmitchell



## 4.10.0 (2024-07-18)

### What's changed
- Store sites in eloquent [#322](https://github.com/statamic/eloquent-driver/pull/322) by @ryanmitchell
- Remove `initial_path` from import and export data [#318](https://github.com/statamic/eloquent-driver/pull/318) by @ryanmitchell
- Fix migration errors on form_submissions [#324](https://github.com/statamic/eloquent-driver/pull/324) by @ryanmitchell



## 4.9.0 (2024-07-10)

### What's changed
- Fix URI and related issues [#317](https://github.com/statamic/eloquent-driver/pull/317) by @jasonvarga



## 4.8.1 (2024-07-02)

### What's changed
- use created_at column for submission sorting [#316](https://github.com/statamic/eloquent-driver/pull/316) by @SylvesterDamgaard



## 4.8.0 (2024-07-01)

### What's changed
- Support mapping of entry data to database columns [#273](https://github.com/statamic/eloquent-driver/pull/273) by @ryanmitchell
- Handle vendor namespaces and handle for blueprints and fieldsets [#310](https://github.com/statamic/eloquent-driver/pull/310) by @ryanmitchell



## 4.7.0 (2024-06-29)

### What's changed
- Make `Asset::clearCaches` protected [#308](https://github.com/statamic/eloquent-driver/pull/308) by @ryanmitchell



## 4.6.0 (2024-06-27)

### What's changed
- Use database to build asset folder list [#311](https://github.com/statamic/eloquent-driver/pull/311) by @ryanmitchell
- Change form submissions migration to use decimal [#313](https://github.com/statamic/eloquent-driver/pull/313) by @ryanmitchell



## 4.5.0 (2024-06-24)

### What's changed
- Move to PHPUnit attributes [#309](https://github.com/statamic/eloquent-driver/pull/309) by @ryanmitchell
- Use model `created_at` for form submission date() [#306](https://github.com/statamic/eloquent-driver/pull/306) by @ryanmitchell



## 4.4.0 (2024-06-20)

### What's changed
- Allow blueprints and field sets to be split repository [#302](https://github.com/statamic/eloquent-driver/pull/302) by @ryanmitchell
- Use double instead of float for MySQL compatibility [#305](https://github.com/statamic/eloquent-driver/pull/305) by @ryanmitchell



## 4.3.1 (2024-06-18)

### What's changed
- Fix bug in updateEntryUris and updateEntryOrder [#304](https://github.com/statamic/eloquent-driver/pull/304) by @ryanmitchell



## 4.3.0 (2024-06-16)

### What's changed
- Refactor makeModelFromContract on assets and containers [#297](https://github.com/statamic/eloquent-driver/pull/297) by @ryanmitchell
- make use of $ids in updateEntryOrder method [#301](https://github.com/statamic/eloquent-driver/pull/301) by @mkwia
- Support mixed repository blueprints [#300](https://github.com/statamic/eloquent-driver/pull/300) by @ryanmitchell
- Only create asset during sync if it doesnt already exist [#299](https://github.com/statamic/eloquent-driver/pull/299) by @ryanmitchell



## 4.2.0 (2024-06-05)

### What's changed
- Add validation_rules support [#296](https://github.com/statamic/eloquent-driver/pull/296) by @ryanmitchell



## 4.1.0 (2024-06-04)

### What's changed
- Drop foreign key on form_id [#285](https://github.com/statamic/eloquent-driver/pull/285) by @ryanmitchell
- GitHub Issue Templates [#290](https://github.com/statamic/eloquent-driver/pull/290) by @duncanmcclean
- Add "Tokens" driver to `artisan about` command output [#293](https://github.com/statamic/eloquent-driver/pull/293) by @duncanmcclean
- Change ID type on form_submissions to be a float [#287](https://github.com/statamic/eloquent-driver/pull/287) by @ryanmitchell
- Date(time) sorting does not take time into account [#295](https://github.com/statamic/eloquent-driver/pull/295) by @SylvesterDamgaard



## 4.0.0 (2024-05-09)

### What's changed
- Statamic 5 Support [#276](https://github.com/statamic/eloquent-driver/pull/276) by @duncanmcclean
