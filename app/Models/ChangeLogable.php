<?php

namespace App\Models;

/**
 * Interface to implement for Models that need their changes to be logged
 * in the change_log table.
 */
interface ChangeLogable
{

    /** List of keys the changelog data must have */
    const CHANGELOG_KEYS = [
        'section', 'section_id', 'section_name',
        'sub_section', 'sub_section_id', 'sub_section_name'
    ];

    /**
     * Get data to populate the changelog
     *
     * @return array Changelog data
     */
    public function getChangelogData(): array;

}
