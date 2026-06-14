<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\MenuImportTemplate;
use App\Http\Controllers\Controller;
use App\Models\MenuDiskCondition;
use App\Models\MenuSet;
use App\View\Components\Admin\Crumb;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuImportController extends Controller
{
    /**
     * Show the bulk-import page for a menu set (template download + upload /
     * review Livewire component).
     */
    public function index(MenuSet $set)
    {
        return view('admin.menus.import.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $set), $set->name),
                    new Crumb('', 'Import'),
                ],
                'set' => $set,
            ]);
    }

    /**
     * Download the empty, styled Excel template.
     */
    public function template(MenuSet $set): StreamedResponse
    {
        $conditions = MenuDiskCondition::orderBy('name')->pluck('name')->all();
        $spreadsheet = MenuImportTemplate::build($conditions);

        $filename = 'menu-import-template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
