<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Reads an uploaded bulk-import spreadsheet (see {@see MenuImportTemplate}) into
 * a nested structure of menus → disks → contents.
 *
 * The parser is intentionally free of any database access so it can be unit
 * tested in isolation. Name resolution, conflict detection and the link-mode
 * heuristic all happen later, in the Livewire review component.
 *
 * Returned shape:
 *   [
 *     ['number' => ?string, 'issue' => ?string, 'version' => ?string, 'date' => ?string,
 *      'disks' => [
 *         ['part' => ?string, 'condition' => ?string, 'donated_by' => ?string,
 *          'notes' => ?string, 'scrolltext' => ?string,
 *          'contents' => [
 *             ['row' => int, 'order' => ?string, 'name' => ?string, 'type' => ?string,
 *              'subtype' => ?string, 'version' => ?string, 'requirements' => ?string],
 *          ]],
 *      ]],
 *   ]
 */
class MenuImportParser
{
    /** First data row (rows 1 and 2 are the group + column headers). */
    private const FIRST_DATA_ROW = 3;

    /**
     * @return array<int, array<string, mixed>> List of parsed menus.
     */
    public static function parseFile(string $path): array
    {
        // Note: we deliberately do not enable read-data-only mode, otherwise
        // cell number formats are dropped and date cells come back as raw
        // Excel serial numbers instead of formatted "yyyy-mm-dd" strings.
        $reader = IOFactory::createReaderForFile($path);

        return self::parseSpreadsheet($reader->load($path));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function parseSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Import') ?? $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false);

        $keys = array_column(MenuImportTemplate::COLUMNS, 'key');

        $menus = [];
        $menuIndex = -1;
        $diskIndex = -1;

        foreach ($rows as $rowNumber => $rawRow) {
            // Spreadsheet rows are 1-based once we re-add the offset.
            $excelRow = $rowNumber + 1;
            if ($excelRow < self::FIRST_DATA_ROW) {
                continue;
            }

            $cells = self::mapRow($rawRow, $keys);

            if (self::isBlankRow($cells)) {
                continue;
            }

            // A new menu starts when the "New menu?" column is marked (or on the
            // very first data row). The marker — not the number — delimits menus,
            // so menus without a number (or with a repeated one) still work.
            if ($menuIndex < 0 || $cells['new_menu'] !== null) {
                $menus[] = [
                    'number'  => $cells['menu_number'],
                    'issue'   => $cells['menu_issue'],
                    'version' => $cells['menu_version'],
                    'date'    => $cells['menu_date'],
                    'disks'   => [],
                ];
                $menuIndex++;
                $diskIndex = -1;
            }

            // A new disk starts when the "New disk?" column is marked, or whenever
            // a new menu was just opened (its first disk).
            if ($diskIndex < 0 || $cells['new_disk'] !== null) {
                $menus[$menuIndex]['disks'][] = [
                    'part'       => $cells['disk_part'],
                    'condition'  => $cells['disk_condition'],
                    'donated_by' => $cells['disk_donated_by'],
                    'notes'      => $cells['disk_notes'],
                    'scrolltext' => $cells['disk_scrolltext'],
                    'contents'   => [],
                ];
                $diskIndex = array_key_last($menus[$menuIndex]['disks']);
            }

            if (self::hasContent($cells)) {
                $menus[$menuIndex]['disks'][$diskIndex]['contents'][] = [
                    'row'          => $excelRow,
                    'order'        => $cells['content_order'],
                    'name'         => $cells['content_name'],
                    'type'         => $cells['content_type'],
                    'subtype'      => $cells['content_subtype'],
                    'version'      => $cells['content_version'],
                    'requirements' => $cells['content_requirements'],
                ];
            }
        }

        return $menus;
    }

    /**
     * @param  array<int, mixed>  $rawRow
     * @param  array<int, string>  $keys
     * @return array<string, ?string>
     */
    private static function mapRow(array $rawRow, array $keys): array
    {
        $rawRow = array_values($rawRow);
        $cells = [];
        foreach ($keys as $index => $key) {
            $cells[$key] = self::clean($rawRow[$index] ?? null);
        }

        return $cells;
    }

    private static function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param array<string, ?string> $cells */
    private static function isBlankRow(array $cells): bool
    {
        return collect($cells)->every(fn ($v) => $v === null);
    }

    /** @param array<string, ?string> $cells */
    private static function hasContent(array $cells): bool
    {
        return $cells['content_name'] !== null
            || $cells['content_order'] !== null
            || $cells['content_type'] !== null
            || $cells['content_subtype'] !== null
            || $cells['content_version'] !== null
            || $cells['content_requirements'] !== null;
    }
}
