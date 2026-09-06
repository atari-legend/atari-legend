<?php

use Database\Support\IndexRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Every secondary index and foreign-key constraint that does not carry the
 * name Laravel would derive today closes in one migration.
 *
 * Every previous campaign in this series left this deliberately alone --
 * "Index and constraint names are not part of any gate"
 * (docs/plans/2026-08-26-schema-consistency-sweep.md) -- because nothing at
 * runtime reads either. That is still true; this migration is pure metadata
 * and changes no column, no type, no rule, and no row. What it buys is a
 * schema `dropIndex(['col'])` and `dropForeign(['col'])` can trust without
 * reading information_schema first, which is the trap recorded at length in
 * docs/plans/2026-08-23-foreign-key-rename.md and repeated in every campaign
 * since: the derived name is assumed, does not match, and the ALTER fails
 * with SQLSTATE 42000 / 1091.
 *
 * Three shapes of mismatch, not one:
 *
 * - **Never named for anything Laravel would produce.** The large majority: a
 *   bare column name (`game_id`, `crew_id`, ...) on a table this campaign
 *   never touched -- this is `docs/plans/2026-08-26-schema-consistency-sweep.md`'s
 *   "Out of scope" list, now closed. A bare index name and a stale one look
 *   identical in the census below; the column list is what tells them apart,
 *   which is why "The renames" table in the plan carries it.
 * - **Still carrying a table's pre-rename stem.** Three auto-generated
 *   `*_ibfk_*` constraints, and six constraints and their indexes on
 *   `magazine_indices`/`magazine_issues`/`magazines` still saying
 *   `magazine_game`/`magazine_issue`/`magazine` -- the pluralisation renamed
 *   the table but, same as every table rename in this series, never reached a
 *   name that did not already begin with the table's old name.
 * - **Named for a column this campaign itself renamed.** `pub_dev_id` ->
 *   `company_id` (three tables), `game_submitinfo_id` -> `game_submission_id`,
 *   `website_id`/`website_category_id` -> `link_id`/`category_id`,
 *   `screenshot_article_id` -> `article_screenshot_id` (and the interview and
 *   review siblings), `game_similar_cross` -> `similar_game_id`, and the
 *   `game_vs` index still spelling `lemonamiga_id` as `amiga_id` -- each
 *   flagged as a future landmine in the plan that made the rename, and
 *   deferred here rather than fixed in twelve separate migrations.
 *
 * `IndexRenamer::renameIndexes()` runs first, changing only the index; it
 * finds what to rename by column list, not by the name recorded below, for
 * the reason its own doc block gives: this schema's index names disagree
 * between a fresh install and every environment that reached today by
 * running each migration once, in order, since 2020 -- production among
 * them. `IndexRenamer::renameForeignKeys()` runs second: it drops each
 * constraint and re-adds it under its new name, against the exact column,
 * reference and `ON UPDATE`/`ON DELETE` rule read from the live schema, so by
 * the time it runs, the index it reuses already carries the matching name and
 * no duplicate index is created. `down()` reverses both, in the opposite
 * order.
 *
 * SQLite is skipped entirely, in both directions -- it neither names indexes
 * after their table nor supports `RENAME KEY`, the same guard TableRenamer
 * applies to its own raw statements.
 *
 * See docs/plans/2026-09-06-index-name-consistency.md for the full census,
 * the query it was measured with, and why each of the 109 renames below is
 * safe.
 */
return new class extends Migration
{
    /**
     * table => list of [target index name, columns, name measured on the
     * long-lived dev database that mirrors production -- kept only so
     * down() has something to restore; up() never reads it].
     */
    private const INDEXES = [
        'article_screenshot_comments' => [
            ['article_screenshot_comments_article_screenshot_id_foreign', ['article_screenshot_id'], 'article_screenshot_comments_screenshot_article_id_foreign'],
        ],
        'comments' => [
            ['comments_user_id_index', ['user_id'], 'user_id'],
        ],
        'dumps' => [
            ['dumps_media_id_index', ['media_id'], 'media_id'],
            ['dumps_user_id_index', ['user_id'], 'user_id'],
        ],
        'game_akas' => [
            ['game_akas_game_id_index', ['game_id'], 'game_id'],
            ['game_akas_language_id_index', ['language_id'], 'language_id'],
        ],
        'game_control' => [
            ['game_control_control_id_index', ['control_id'], 'control_id'],
            ['game_control_game_id_index', ['game_id'], 'game_id'],
        ],
        'game_developer' => [
            ['game_developer_developer_role_id_index', ['developer_role_id'], 'developer_role_id'],
            ['game_developer_game_id_index', ['game_id'], 'game_id'],
            ['game_developer_company_id_index', ['company_id'], 'pub_dev_id'],
        ],
        'game_engine' => [
            ['game_engine_engine_id_index', ['engine_id'], 'engine_id'],
            ['game_engine_game_id_index', ['game_id'], 'game_id'],
        ],
        'game_genre' => [
            ['game_genre_genre_id_index', ['genre_id'], 'game_cat_id'],
            ['game_genre_game_id_index', ['game_id'], 'game_id'],
        ],
        'game_individual' => [
            ['game_individual_game_id_index', ['game_id'], 'game_id'],
            ['game_individual_individual_id_index', ['individual_id'], 'individual_id'],
            ['game_individual_individual_role_id_index', ['individual_role_id'], 'individual_role_id'],
        ],
        'game_programming_language' => [
            ['game_programming_language_game_id_index', ['game_id'], 'game_id'],
            ['game_programming_language_programming_language_id_index', ['programming_language_id'], 'programming_language_id'],
        ],
        'game_release_akas' => [
            ['game_release_akas_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_akas_language_id_index', ['language_id'], 'language_id'],
        ],
        'game_release_copy_protection' => [
            ['game_release_copy_protection_copy_protection_id_index', ['copy_protection_id'], 'copy_protection_id'],
            ['game_release_copy_protection_game_release_id_index', ['game_release_id'], 'game_release_id'],
        ],
        'game_release_crew' => [
            ['game_release_crew_crew_id_index', ['crew_id'], 'crew_id'],
            ['game_release_crew_game_release_id_index', ['game_release_id'], 'game_release_id'],
        ],
        'game_release_disk_protection' => [
            ['game_release_disk_protection_disk_protection_id_index', ['disk_protection_id'], 'disk_protection_id'],
            ['game_release_disk_protection_game_release_id_index', ['game_release_id'], 'game_release_id'],
        ],
        'game_release_distributor' => [
            ['game_release_distributor_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_distributor_company_id_index', ['company_id'], 'pub_dev_id'],
        ],
        'game_release_emulator_incompatible' => [
            ['game_release_emulator_incompatible_emulator_id_index', ['emulator_id'], 'emulator_id'],
            ['game_release_emulator_incompatible_game_release_id_index', ['game_release_id'], 'game_release_id'],
        ],
        'game_release_language' => [
            ['game_release_language_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_language_language_id_index', ['language_id'], 'language_id'],
        ],
        'game_release_location' => [
            ['game_release_location_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_location_location_id_index', ['location_id'], 'location_id'],
        ],
        'game_release_memory_enhanced' => [
            ['game_release_memory_enhanced_enhancement_id_index', ['enhancement_id'], 'enhancement_id'],
            ['game_release_memory_enhanced_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_memory_enhanced_memory_id_index', ['memory_id'], 'memory_id'],
        ],
        'game_release_memory_incompatible' => [
            ['game_release_memory_incompatible_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_memory_incompatible_memory_id_index', ['memory_id'], 'memory_id'],
        ],
        'game_release_memory_minimum' => [
            ['game_release_memory_minimum_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_memory_minimum_memory_id_index', ['memory_id'], 'memory_id'],
        ],
        'game_release_resolution' => [
            ['game_release_resolution_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_resolution_resolution_id_index', ['resolution_id'], 'resolution_id'],
        ],
        'game_release_scans' => [
            ['game_release_scans_game_release_id_index', ['game_release_id'], 'game_release_id'],
        ],
        'game_release_system_enhanced' => [
            ['game_release_system_enhanced_enhancement_id_index', ['enhancement_id'], 'enhancement_id'],
            ['game_release_system_enhanced_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_system_enhanced_system_id_index', ['system_id'], 'system_id'],
        ],
        'game_release_system_incompatible' => [
            ['game_release_system_incompatible_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_system_incompatible_system_id_index', ['system_id'], 'system_id'],
        ],
        'game_release_tos_incompatibilities' => [
            ['game_release_tos_incompatibilities_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_tos_incompatibilities_language_id_index', ['language_id'], 'language_id'],
            ['game_release_tos_incompatibilities_tos_id_index', ['tos_id'], 'tos_id'],
        ],
        'game_release_trainer_option' => [
            ['game_release_trainer_option_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['game_release_trainer_option_trainer_option_id_index', ['trainer_option_id'], 'trainer_option_id'],
        ],
        'game_releases' => [
            ['game_releases_game_id_index', ['game_id'], 'game_id'],
            ['game_releases_company_id_index', ['company_id'], 'pub_dev_id'],
        ],
        'game_screenshot' => [
            ['game_screenshot_game_id_index', ['game_id'], 'game_id'],
        ],
        'game_similar' => [
            ['game_similar_similar_game_id_foreign', ['similar_game_id'], 'game_similar_game_similar_cross_foreign'],
        ],
        'game_sound_hardware' => [
            ['game_sound_hardware_game_id_index', ['game_id'], 'game_id'],
            ['game_sound_hardware_sound_hardware_id_index', ['sound_hardware_id'], 'sound_hardware_id'],
        ],
        'game_submission_screenshot' => [
            ['game_submission_screenshot_game_submission_id_foreign', ['game_submission_id'], 'game_submission_screenshot_game_submitinfo_id_foreign'],
        ],
        'game_submissions' => [
            ['game_submissions_user_id_index', ['user_id'], 'user_id'],
        ],
        'game_vs' => [
            ['game_vs_atari_id_lemon64_slug_lemonamiga_id_index', ['atari_id', 'lemon64_slug', 'lemonamiga_id'], 'game_vs_atari_id_lemon64_slug_amiga_id_index'],
        ],
        'games' => [
            ['games_game_progress_system_id_index', ['game_progress_system_id'], 'game_progress_system_id'],
            ['games_game_series_id_index', ['game_series_id'], 'game_series_id'],
            ['games_port_id_index', ['port_id'], 'port_id'],
        ],
        'interview_screenshot_comments' => [
            ['interview_screenshot_comments_interview_screenshot_id_foreign', ['interview_screenshot_id'], 'interview_screenshot_comments_screenshot_interview_id_foreign'],
        ],
        'interviews' => [
            ['interviews_user_id_index', ['user_id'], 'user_id'],
        ],
        'link_category' => [
            ['link_category_category_id_foreign', ['category_id'], 'link_category_website_category_id_foreign'],
            ['link_category_link_id_category_id_unique', ['link_id', 'category_id'], 'link_category_website_id_website_category_id_unique'],
        ],
        'links' => [
            ['links_user_id_index', ['user_id'], 'user_id'],
        ],
        'locations' => [
            ['locations_continent_code_iso2_unique', ['continent_code', 'iso2'], 'continent_code'],
        ],
        'magazine_indices' => [
            ['magazine_indices_game_id_foreign', ['game_id'], 'magazine_game_game_id_foreign'],
            ['magazine_indices_magazine_index_type_id_foreign', ['magazine_index_type_id'], 'magazine_game_magazine_index_type_id_foreign'],
            ['magazine_indices_magazine_issue_id_foreign', ['magazine_issue_id'], 'magazine_game_magazine_issue_id_foreign'],
            ['magazine_indices_menu_software_id_foreign', ['menu_software_id'], 'magazine_game_menu_software_id_foreign'],
        ],
        'magazine_issues' => [
            ['magazine_issues_magazine_id_foreign', ['magazine_id'], 'magazine_issue_magazine_id_foreign'],
        ],
        'magazines' => [
            ['magazines_location_id_foreign', ['location_id'], 'magazine_location_id_foreign'],
        ],
        'media' => [
            ['media_game_release_id_index', ['game_release_id'], 'game_release_id'],
            ['media_media_type_id_index', ['media_type_id'], 'media_type_id'],
        ],
        'media_scans' => [
            ['media_scans_media_id_index', ['media_id'], 'media_id'],
            ['media_scans_media_scan_type_id_index', ['media_scan_type_id'], 'media_scan_type_id'],
        ],
        'news' => [
            ['news_user_id_index', ['user_id'], 'user_id'],
        ],
        'review_screenshot_comments' => [
            ['review_screenshot_comments_review_screenshot_id_foreign', ['review_screenshot_id'], 'review_screenshot_comments_screenshot_review_id_foreign'],
        ],
        'reviews' => [
            ['reviews_user_id_index', ['user_id'], 'user_id'],
        ],
        'sndhs' => [
            ['sndhs_title_fulltext', ['title'], 'title_search'],
        ],
        'users' => [
            ['users_userid_index', ['userid'], 'userid'],
        ],
    ];

    private const FOREIGN_KEYS = [
        ['article_screenshot_comments', 'article_screenshot_comments_screenshot_article_id_foreign', 'article_screenshot_comments_article_screenshot_id_foreign', 'article_screenshot_id', 'article_screenshot', 'id', 'RESTRICT', 'CASCADE'],
        ['dumps', 'dumps_ibfk_2', 'dumps_user_id_foreign', 'user_id', 'users', 'id', 'RESTRICT', 'RESTRICT'],
        ['game_akas', 'game_akas_ibfk_1', 'game_akas_language_id_foreign', 'language_id', 'languages', 'id', 'RESTRICT', 'RESTRICT'],
        ['game_developer', 'game_developer_pub_dev_id_foreign', 'game_developer_company_id_foreign', 'company_id', 'companies', 'id', 'RESTRICT', 'CASCADE'],
        ['game_genre', 'game_genre_game_genre_id_foreign', 'game_genre_genre_id_foreign', 'genre_id', 'genres', 'id', 'RESTRICT', 'CASCADE'],
        ['game_release_distributor', 'game_release_distributor_pub_dev_id_foreign', 'game_release_distributor_company_id_foreign', 'company_id', 'companies', 'id', 'RESTRICT', 'CASCADE'],
        ['game_releases', 'game_releases_ibfk_3', 'game_releases_company_id_foreign', 'company_id', 'companies', 'id', 'RESTRICT', 'RESTRICT'],
        ['game_similar', 'game_similar_game_similar_cross_foreign', 'game_similar_similar_game_id_foreign', 'similar_game_id', 'games', 'id', 'RESTRICT', 'CASCADE'],
        ['game_submission_screenshot', 'game_submission_screenshot_game_submitinfo_id_foreign', 'game_submission_screenshot_game_submission_id_foreign', 'game_submission_id', 'game_submissions', 'id', 'RESTRICT', 'CASCADE'],
        ['games', 'games_ibfk_1', 'games_game_series_id_foreign', 'game_series_id', 'game_series', 'id', 'RESTRICT', 'RESTRICT'],
        ['interview_screenshot_comments', 'interview_screenshot_comments_screenshot_interview_id_foreign', 'interview_screenshot_comments_interview_screenshot_id_foreign', 'interview_screenshot_id', 'interview_screenshot', 'id', 'RESTRICT', 'CASCADE'],
        ['link_category', 'link_category_website_category_id_foreign', 'link_category_category_id_foreign', 'category_id', 'categories', 'id', 'RESTRICT', 'CASCADE'],
        ['link_category', 'link_category_website_id_foreign', 'link_category_link_id_foreign', 'link_id', 'links', 'id', 'RESTRICT', 'CASCADE'],
        ['magazine_indices', 'magazine_game_game_id_foreign', 'magazine_indices_game_id_foreign', 'game_id', 'games', 'id', 'CASCADE', 'CASCADE'],
        ['magazine_indices', 'magazine_game_magazine_index_type_id_foreign', 'magazine_indices_magazine_index_type_id_foreign', 'magazine_index_type_id', 'magazine_index_types', 'id', 'RESTRICT', 'SET NULL'],
        ['magazine_indices', 'magazine_game_magazine_issue_id_foreign', 'magazine_indices_magazine_issue_id_foreign', 'magazine_issue_id', 'magazine_issues', 'id', 'CASCADE', 'CASCADE'],
        ['magazine_indices', 'magazine_game_menu_software_id_foreign', 'magazine_indices_menu_software_id_foreign', 'menu_software_id', 'menu_software', 'id', 'CASCADE', 'CASCADE'],
        ['magazine_issues', 'magazine_issue_magazine_id_foreign', 'magazine_issues_magazine_id_foreign', 'magazine_id', 'magazines', 'id', 'CASCADE', 'CASCADE'],
        ['magazines', 'magazine_location_id_foreign', 'magazines_location_id_foreign', 'location_id', 'locations', 'id', 'CASCADE', 'SET NULL'],
        ['review_screenshot_comments', 'review_screenshot_comments_screenshot_review_id_foreign', 'review_screenshot_comments_review_screenshot_id_foreign', 'review_screenshot_id', 'review_screenshot', 'id', 'RESTRICT', 'CASCADE'],
    ];

    public function up(): void
    {
        IndexRenamer::renameIndexes(self::INDEXES);
        IndexRenamer::renameForeignKeys(self::FOREIGN_KEYS);
    }

    public function down(): void
    {
        IndexRenamer::reverseForeignKeys(self::FOREIGN_KEYS);
        IndexRenamer::reverseIndexes(self::INDEXES);
    }
};
