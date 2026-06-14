<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MenuImportParser;
use App\Helpers\MenuImportTemplate;
use PHPUnit\Framework\TestCase;

class MenuImportTemplateTest extends TestCase
{
    public function testBuildsTwoSheetsWithHeadersAndDropdowns()
    {
        $conditions = ['Intact', 'Slightly damaged', 'Missing'];
        $spreadsheet = MenuImportTemplate::build($conditions);

        $this->assertSame('Import', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Help', $spreadsheet->getSheet(1)->getTitle());

        $sheet = $spreadsheet->getSheetByName('Import');

        // Group headers on row 1, column headers on row 2.
        $this->assertSame('Menu', $sheet->getCell('A1')->getValue());
        $this->assertSame('New menu?', $sheet->getCell('A2')->getValue());
        $this->assertSame('Number', $sheet->getCell('B2')->getValue());
        $this->assertSame('Name', $sheet->getCell('M2')->getValue());

        // Condition dropdown lists the supplied conditions.
        $conditionValidation = $sheet->getCell('H3')->getDataValidation();
        $this->assertStringContainsString('Intact', $conditionValidation->getFormula1());
        $this->assertStringContainsString('Missing', $conditionValidation->getFormula1());

        // Content type dropdown offers Game / Software.
        $typeValidation = $sheet->getCell('N3')->getDataValidation();
        $this->assertStringContainsString('Game', $typeValidation->getFormula1());
        $this->assertStringContainsString('Software', $typeValidation->getFormula1());
    }

    public function testGeneratedTemplateParsesBackAsEmpty()
    {
        $spreadsheet = MenuImportTemplate::build(['Intact']);

        // An untouched template (headers only, no data rows) yields no menus.
        $this->assertSame([], MenuImportParser::parseSpreadsheet($spreadsheet));
    }
}
