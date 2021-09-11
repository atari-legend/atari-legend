<?php

namespace App\Helpers;

use App\Models\Changelog;
use ErrorException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Helper for Changelog.
 */
class ChangelogHelper
{
    /** List of keys the changelog data must have */
    const CHANGELOG_KEYS = [
        'action', 'section', 'section_id', 'section_name',
        'sub_section', 'sub_section_id', 'sub_section_name',
    ];

    public static function insert(array $data)
    {
        // Check if all keys are present in the data
        $missingKeys = ChangelogHelper::getMissingKeys($data);
        if (count($missingKeys) > 0) {
            throw new ErrorException('Missing changelog key(s) \''.join(', ', $missingKeys).'\'');
        }

        $log = Changelog::create(
            array_merge($data, [
                'user_id'          => $data['user_id'] ?? Auth::user()->user_id ?? -1,
                'timestamp'        => time(),
            ])
        );

        Log::debug("Saved changelog: {$log}");
    }

    /**
     * Find missing keys in the changelog data.
     *
     * @param  array  $data  Changelog data
     * @return array Missing keys, or an empty array if no keys are missing
     */
    private static function getMissingKeys(array $data): array
    {
        $keys = array_keys($data);

        return collect(ChangelogHelper::CHANGELOG_KEYS)
            ->reject(function ($item) use ($keys) {
                return in_array($item, $keys);
            })
            ->all();
    }
}
