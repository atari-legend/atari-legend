<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MenuImportParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class MenuImportParserTest extends TestCase
{
    /**
     * Column letters in the same order as MenuImportTemplate::COLUMNS.
     * A..E = Menu, F..K = Disk, L..Q = Content.
     */
    private const COLS = [
        'new_menu'        => 'A', 'menu_number' => 'B', 'menu_issue' => 'C', 'menu_version' => 'D', 'menu_date' => 'E',
        'new_disk'        => 'F', 'disk_part' => 'G', 'disk_condition' => 'H', 'disk_donated_by' => 'I', 'disk_notes' => 'J', 'disk_scrolltext' => 'K',
        'content_order'   => 'L', 'content_name' => 'M', 'content_type' => 'N',
        'content_subtype' => 'O', 'content_version' => 'P', 'content_requirements' => 'Q',
    ];

    private function spreadsheetFromRows(array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Import');

        // Data starts on row 3 (rows 1 & 2 are headers).
        $excelRow = 3;
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $sheet->setCellValue(self::COLS[$key] . $excelRow, $value);
            }
            $excelRow++;
        }

        return $spreadsheet;
    }

    public function testGroupsRowsIntoMenusDisksAndContents()
    {
        $spreadsheet = $this->spreadsheetFromRows([
            // Menu 1, disk A, first content (main release).
            [
                'new_menu'             => 'x', 'new_disk' => 'x',
                'menu_number'          => 1, 'menu_version' => 'final', 'menu_date' => '2021-03-05',
                'disk_part'            => 'A', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Bob',
                'disk_notes'           => 'n1', 'disk_scrolltext' => 's1',
                'content_order'        => 1, 'content_name' => 'Populous', 'content_type' => 'Game',
                'content_requirements' => '512KB',
            ],
            // Same menu + disk (no markers): an extra/doc.
            [
                'content_order'   => 2, 'content_name' => 'Populous', 'content_type' => 'Game',
                'content_subtype' => 'Docs',
            ],
            // Same menu, new disk B with one software content.
            [
                'new_disk'      => 'x', 'disk_part' => 'B', 'disk_condition' => 'Intact',
                'content_order' => 1, 'content_name' => 'Llamatron', 'content_type' => 'Software',
            ],
            // New menu 2, single disk with no content at all.
            [
                'new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 2, 'disk_condition' => 'Missing',
            ],
        ]);

        $menus = MenuImportParser::parseSpreadsheet($spreadsheet);

        $this->assertCount(2, $menus);

        // --- Menu 1 ---------------------------------------------------
        $menu1 = $menus[0];
        $this->assertSame('1', $menu1['number']);
        $this->assertSame('final', $menu1['version']);
        $this->assertSame('2021-03-05', $menu1['date']);
        $this->assertCount(2, $menu1['disks']);

        $diskA = $menu1['disks'][0];
        $this->assertSame('A', $diskA['part']);
        $this->assertSame('Intact', $diskA['condition']);
        $this->assertSame('Bob', $diskA['donated_by']);
        $this->assertCount(2, $diskA['contents']);

        $this->assertSame('Populous', $diskA['contents'][0]['name']);
        $this->assertSame('1', $diskA['contents'][0]['order']);
        $this->assertNull($diskA['contents'][0]['subtype']);
        $this->assertSame(3, $diskA['contents'][0]['row']);
        $this->assertSame('Docs', $diskA['contents'][1]['subtype']);

        $diskB = $menu1['disks'][1];
        $this->assertSame('B', $diskB['part']);
        $this->assertCount(1, $diskB['contents']);
        $this->assertSame('Software', $diskB['contents'][0]['type']);

        // --- Menu 2 ---------------------------------------------------
        $menu2 = $menus[1];
        $this->assertSame('2', $menu2['number']);
        $this->assertCount(1, $menu2['disks']);
        $this->assertSame('Missing', $menu2['disks'][0]['condition']);
        $this->assertCount(0, $menu2['disks'][0]['contents']);
    }

    public function testBlankRowsAreSkippedAndUnmarkedRowsContinueTheMenu()
    {
        $spreadsheet = $this->spreadsheetFromRows([
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 5, 'disk_part' => 'A', 'content_order' => 1, 'content_name' => 'Game A', 'content_type' => 'Game'],
            [], // fully blank row
            // No marker => still the same menu and disk.
            ['content_order' => 2, 'content_name' => 'Game B', 'content_type' => 'Game'],
        ]);

        $menus = MenuImportParser::parseSpreadsheet($spreadsheet);

        $this->assertCount(1, $menus);
        $this->assertCount(1, $menus[0]['disks']);
        $this->assertCount(2, $menus[0]['disks'][0]['contents']);
    }

    public function testNumberlessMenusAreSeparatedByTheMarker()
    {
        // Two menus, neither with a number, separated only by the "New menu?" mark.
        $spreadsheet = $this->spreadsheetFromRows([
            ['new_menu' => 'x', 'new_disk' => 'x', 'content_order' => 1, 'content_name' => 'A', 'content_type' => 'Game'],
            ['new_menu' => 'x', 'new_disk' => 'x', 'content_order' => 1, 'content_name' => 'B', 'content_type' => 'Game'],
        ]);

        $menus = MenuImportParser::parseSpreadsheet($spreadsheet);

        $this->assertCount(2, $menus);
        $this->assertNull($menus[0]['number']);
        $this->assertNull($menus[1]['number']);
        $this->assertSame('A', $menus[0]['disks'][0]['contents'][0]['name']);
        $this->assertSame('B', $menus[1]['disks'][0]['contents'][0]['name']);
    }
}
