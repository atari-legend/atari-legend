<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds the styled, downloadable Excel template used for the bulk import of
 * menus / disks / disk-contents into a menu set.
 *
 * The column layout defined here in {@see self::COLUMNS} is the single source
 * of truth shared with {@see MenuImportParser}, which reads uploaded files back
 * using the same column order.
 */
class MenuImportTemplate
{
    /**
     * Ordered column definition. The position in this array is the column index
     * (0-based) used by both the template writer and the parser.
     *
     * @var array<int, array{key: string, header: string, group: string}>
     */
    public const COLUMNS = [
        ['key' => 'new_menu',             'header' => 'New menu?',    'group' => 'Menu'],
        ['key' => 'menu_number',          'header' => 'Number',       'group' => 'Menu'],
        ['key' => 'menu_issue',           'header' => 'Issue',        'group' => 'Menu'],
        ['key' => 'menu_version',         'header' => 'Version',      'group' => 'Menu'],
        ['key' => 'menu_date',            'header' => 'Release date', 'group' => 'Menu'],
        ['key' => 'new_disk',             'header' => 'New disk?',    'group' => 'Disk'],
        ['key' => 'disk_part',            'header' => 'Part',         'group' => 'Disk'],
        ['key' => 'disk_condition',       'header' => 'Condition',    'group' => 'Disk'],
        ['key' => 'disk_donated_by',      'header' => 'Donated by',   'group' => 'Disk'],
        ['key' => 'disk_notes',           'header' => 'Notes',        'group' => 'Disk'],
        ['key' => 'disk_scrolltext',      'header' => 'Scrolltext',   'group' => 'Disk'],
        ['key' => 'content_order',        'header' => 'Order',        'group' => 'Content'],
        ['key' => 'content_name',         'header' => 'Name',         'group' => 'Content'],
        ['key' => 'content_type',         'header' => 'Type',         'group' => 'Content'],
        ['key' => 'content_subtype',      'header' => 'Sub-type',     'group' => 'Content'],
        ['key' => 'content_version',      'header' => 'Version',      'group' => 'Content'],
        ['key' => 'content_requirements', 'header' => 'Requirements', 'group' => 'Content'],
    ];

    /** Content type dropdown values (the two kinds of disk content). */
    public const CONTENT_TYPES = ['Game', 'Software'];

    /** Value used to mark the first row of a menu / disk. */
    public const MARKER = 'x';

    /** Strong group header colours and the lighter tint used for the column header row. */
    private const GROUP_STYLES = [
        'Menu'    => ['strong' => 'FF4472C4', 'light' => 'FFD9E1F2'],
        'Disk'    => ['strong' => 'FF548235', 'light' => 'FFE2EFDA'],
        'Content' => ['strong' => 'FFBF8F00', 'light' => 'FFFFF2CC'],
    ];

    /** Number of empty data rows pre-formatted (and validated) in the template. */
    private const DATA_ROWS = 200;

    /**
     * Build the template spreadsheet.
     *
     * @param  array<int, string>  $conditions  Disk condition names for the dropdown.
     */
    public static function build(array $conditions): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import');

        self::buildImportSheet($sheet, $conditions);
        self::buildHelpSheet($spreadsheet->createSheet()->setTitle('Help'));

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private static function buildImportSheet(Worksheet $sheet, array $conditions): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $firstDataRow = 3;
        $lastDataRow = $firstDataRow + self::DATA_ROWS - 1;

        // --- Row 1: merged group headers ---------------------------------
        $groupRanges = self::groupColumnRanges();
        foreach ($groupRanges as $group => [$from, $to]) {
            $sheet->mergeCells("{$from}1:{$to}1");
            $sheet->setCellValue("{$from}1", $group);
            $sheet->getStyle("{$from}1")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => self::GROUP_STYLES[$group]['strong']],
                ],
            ]);
        }

        // --- Row 2: column headers ---------------------------------------
        foreach (self::COLUMNS as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$letter}2", $column['header']);
            $sheet->getStyle("{$letter}2")->applyFromArray([
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => self::GROUP_STYLES[$column['group']]['light']],
                ],
            ]);
            $sheet->getColumnDimension($letter)->setWidth(self::columnWidth($column['key']));
        }

        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->freezePane('A3');

        // --- Borders around the header + empty data grid -----------------
        $sheet->getStyle("A1:{$lastColumn}{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']],
            ],
        ]);

        // --- Data validations & number formats ---------------------------
        self::applyListValidation(
            $sheet,
            self::rangeFor('new_menu', $firstDataRow, $lastDataRow),
            [self::MARKER],
            'Start of a new menu',
            'Put an "x" on the first row of each menu. Leave blank to continue the menu above.'
        );
        self::applyListValidation(
            $sheet,
            self::rangeFor('new_disk', $firstDataRow, $lastDataRow),
            [self::MARKER],
            'Start of a new disk',
            'Put an "x" on the first row of each disk. Leave blank to continue the disk above.'
        );
        self::applyListValidation(
            $sheet,
            self::rangeFor('disk_condition', $firstDataRow, $lastDataRow),
            $conditions,
            'Pick a disk condition',
            'Choose one of the listed conditions.'
        );
        self::applyListValidation(
            $sheet,
            self::rangeFor('content_type', $firstDataRow, $lastDataRow),
            self::CONTENT_TYPES,
            'Game or Software',
            'Choose "Game" for game releases/extras, or "Software" for demos, utilities, music, docs, etc.'
        );
        self::applyWholeNumberValidation(
            $sheet,
            self::rangeFor('menu_number', $firstDataRow, $lastDataRow),
            'Menu number',
            'Whole number, e.g. 1, 2, 3…'
        );
        self::applyWholeNumberValidation(
            $sheet,
            self::rangeFor('content_order', $firstDataRow, $lastDataRow),
            'Order',
            'Whole number giving the position of this item on the disk.'
        );

        // Format the release-date column and hint the expected format.
        $dateRange = self::rangeFor('menu_date', $firstDataRow, $lastDataRow);
        $sheet->getStyle($dateRange)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    }

    private static function buildHelpSheet(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(110);

        $lines = [
            ['How to fill in this import sheet', true],
            ['', false],
            ['Each row in the "Import" sheet describes ONE content item on a disk.', false],
            ['Put an "x" in the "New menu?" column on the FIRST row of each menu, and an "x" in the "New disk?" column on the FIRST row of each disk.', false],
            ['Rows without those marks continue the menu / disk above them, so you only fill the Menu and Disk columns once, on their first row.', false],
            ['The "x" markers — not the Number — are what separate the menus, so menus with no number (or a repeated number) still work.', false],
            ['', false],
            ['Menu columns', true],
            ['• New menu? — put an "x" here to start a new menu (required on the first row of every menu).', false],
            ['• Number — the sequence number of the menu within the set (whole number, optional).', false],
            ['• Issue — an issue label when there is no number (e.g. a letter or special name). Fill Number OR Issue.', false],
            ['• Version — optional version string of the menu.', false],
            ['• Release date — optional, formatted as YYYY-MM-DD.', false],
            ['', false],
            ['Disk columns', true],
            ['• New disk? — put an "x" here to start a new disk (required on the first row of every disk).', false],
            ['• Part — disk part label (e.g. A, B, Part I). Leave blank if the menu has a single disk.', false],
            ['• Condition — pick one value from the dropdown.', false],
            ['• Donated by — name of the individual who donated the disk (must already exist in the database).', false],
            ['• Notes / Scrolltext — free text.', false],
            ['', false],
            ['Content columns', true],
            ['• Order — position of the item on the disk (whole number).', false],
            ['• Name — the exact name of the game or software. It MUST already exist in the database.', false],
            ['• Type — "Game" or "Software" (dropdown).', false],
            ['• Sub-type — leave BLANK for a normal game release. Fill it in (e.g. "Docs", "Cheat", "Trainer") to mark the row as an EXTRA / documentation rather than the main release.', false],
            ['• Version / Requirements — optional free text.', false],
            ['', false],
            ['How "Type" and "Sub-type" decide what is linked', true],
            ['• Type = Game, Sub-type empty → a new game release is created on the menu.', false],
            ['• Type = Game, Sub-type filled, and the same game already appears (with an empty sub-type) on the SAME menu → the row is linked as an extra of that release.', false],
            ['• Type = Game, Sub-type filled, but the game is NOT a release on this menu → the row is linked as a standalone game document / extra.', false],
            ['• Type = Software → the row is linked to the matching software entry.', false],
            ['', false],
            ['You can review and override every one of these choices on screen after uploading, before anything is saved.', false],
            ['Games and software must already exist in the database — unknown names are flagged as errors to fix before importing.', false],
        ];

        foreach ($lines as $row => [$text, $heading]) {
            $cell = 'A' . ($row + 1);
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
            if ($heading) {
                $sheet->getStyle($cell)->getFont()->setBold(true)->setSize($row === 0 ? 14 : 12);
            }
        }
    }

    /**
     * Map each group to its first/last column letter (groups are contiguous).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private static function groupColumnRanges(): array
    {
        $ranges = [];
        foreach (self::COLUMNS as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            if (! isset($ranges[$column['group']])) {
                $ranges[$column['group']] = [$letter, $letter];
            } else {
                $ranges[$column['group']][1] = $letter;
            }
        }

        return $ranges;
    }

    private static function rangeFor(string $key, int $firstRow, int $lastRow): string
    {
        foreach (self::COLUMNS as $index => $column) {
            if ($column['key'] === $key) {
                $letter = Coordinate::stringFromColumnIndex($index + 1);

                return "{$letter}{$firstRow}:{$letter}{$lastRow}";
            }
        }

        throw new \InvalidArgumentException("Unknown column key: {$key}");
    }

    private static function applyListValidation(Worksheet $sheet, string $range, array $values, string $promptTitle, string $prompt): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid value')
            ->setError('Please pick a value from the list.')
            ->setPromptTitle($promptTitle)
            ->setPrompt($prompt)
            ->setFormula1('"' . implode(',', $values) . '"');

        $sheet->setDataValidation($range, $validation);
    }

    private static function applyWholeNumberValidation(Worksheet $sheet, string $range, string $promptTitle, string $prompt): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_WHOLE)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
            ->setAllowBlank(true)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid value')
            ->setError('Please enter a whole number.')
            ->setPromptTitle($promptTitle)
            ->setPrompt($prompt)
            ->setFormula1('0');

        $sheet->setDataValidation($range, $validation);
    }

    private static function columnWidth(string $key): float
    {
        return match ($key) {
            'disk_notes', 'disk_scrolltext', 'content_name' => 32,
            'content_requirements', 'content_subtype', 'disk_donated_by' => 18,
            'menu_date'                                                   => 14,
            'new_menu', 'new_disk'                                        => 11,
            default                                                       => 12,
        };
    }
}
