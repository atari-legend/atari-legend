<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Generic controller for all configuration tables.
 */
class GameConfigurationController extends Controller
{
    /** Mapping of configuration types to tables in the DB. */
    const CONFIG_TYPES_TABLES = [
        'tos'                  => 'tos',
        'engine'               => 'engine',
        'control'              => 'control',
        'port'                 => 'port',
        'memory'               => 'memory',
        'copy-protection'      => 'copy_protection',
        'disk-protection'      => 'disk_protection',
        'trainer'              => 'trainer_option',
        'enhancement'          => 'enhancement',
        'genre'                => 'game_genre',
        'individual-role'      => 'individual_role',
        'sound'                => 'sound_hardware',
        'language'             => 'programming_language',
        'progress'             => 'game_progress_system',
        'developer-role'       => 'developer_role',
        'resolution'           => 'resolution',
        'system'               => 'system',
        'emulator'             => 'emulator',
        'media-type'           => 'media_type',
        'media-scan-type'      => 'media_scan_type',
    ];

    /** Categories of configuration types. */
    const CONFIG_TYPES_CATEGORIES = [
        'Games' => [
            'engine', 'language', 'genre', 'port', 'progress', 'control',
            'individual-role', 'developer-role', 'sound',
        ],
        'Releases' => [
            'resolution', 'system', 'emulator', 'tos', 'memory', 'enhancement',
            'copy-protection', 'disk-protection', 'trainer',
        ],
        'Dumps' => ['media-type', 'media-scan-type'],
    ];

    /** Mapping of configuration types to changelog sub-sections. */
    const CONFIG_TYPES_CHANGELOG = [
        'tos'             => 'TOS',
        'engine'          => 'Games Engine',
        'control'         => 'Control',
        'port'            => 'Port',
        'memory'          => 'Memory',
        'copy-protection' => 'Copy Protection',
        'disk-protection' => 'Disk Protection',
        'trainer'         => 'Trainer',
        'enhancement'     => 'Enhancement',
        'genre'           => 'Genre',
        'individual-role' => 'Individual Role',
        'system'          => 'System',
        'sound'           => 'Sound hardware',
        'language'        => 'Programming Language',
        'progress'        => 'Progress System',
        'developer-role'  => 'Developer Role',
        'resolution'      => 'Resolution',
        'system'          => 'System',
        'emulator'        => 'Emulator',
        'media-type'      => 'Media Type',
        'media-scan-type' => 'Media Scan Type',
    ];

    /** List of configuration types that have a description column. */
    const CONFIG_HAS_DESCRIPTION = ['engine', 'sound', 'language'];

    public function index(string $type)
    {
        $items = DB::table(GameConfigurationController::CONFIG_TYPES_TABLES[$type])
            ->orderBy('name')
            ->get();

        $label = GameConfigurationController::CONFIG_TYPES_CHANGELOG[$type];

        return view('admin.games.configuration.show')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Game configuration'),
                    new Crumb(route('admin.games.configuration.show', $type), $label),
                ],
                'categories'  => GameConfigurationController::CONFIG_TYPES_CATEGORIES,
                'types'       => GameConfigurationController::CONFIG_TYPES_CHANGELOG,
                'items'       => $items,
                'type'        => $type,
                'label'       => $label,
                'hasDescription' => in_array($type, GameConfigurationController::CONFIG_HAS_DESCRIPTION),
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);

        if ($request->type && GameConfigurationController::CONFIG_TYPES_TABLES[$request->type]) {
            $data = [
                'name' => $request->name,
            ];
            if (in_array($request->type, GameConfigurationController::CONFIG_HAS_DESCRIPTION)) {
                $data = array_merge($data, ['description' => $request->description]);
            }
            $id = DB::table(GameConfigurationController::CONFIG_TYPES_TABLES[$request->type])
                ->insert($data);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Games Config',
                'section_id'       => $id,
                'section_name'     => $request->name,
                'sub_section'      => GameConfigurationController::CONFIG_TYPES_CHANGELOG[$request->type],
                'sub_section_id'   => $id,
                'sub_section_name' => $request->name,
            ]);
        }

        return redirect()->route('admin.games.configuration.show', $request->type);
    }

    public function update(Request $request)
    {
        if ($request->type && GameConfigurationController::CONFIG_TYPES_TABLES[$request->type]) {
            $data = [
                'name' => $request->name,
            ];
            if (in_array($request->type, GameConfigurationController::CONFIG_HAS_DESCRIPTION)) {
                $data = array_merge($data, ['description' => $request->description]);
            }
            DB::table(GameConfigurationController::CONFIG_TYPES_TABLES[$request->type])
                ->where('id', '=', $request->id)
                ->update($data);

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Games Config',
                'section_id'       => $request->id,
                'section_name'     => $request->name,
                'sub_section'      => GameConfigurationController::CONFIG_TYPES_CHANGELOG[$request->type],
                'sub_section_id'   => $request->id,
                'sub_section_name' => $request->name,
            ]);
        }

        return redirect()->route('admin.games.configuration.show', $request->type);
    }

    public function destroy(Request $request)
    {
        if ($request->type && GameConfigurationController::CONFIG_TYPES_TABLES[$request->type]) {
            $name = DB::table(GameConfigurationController::CONFIG_TYPES_TABLES[$request->type])
                ->select('name')
                ->where('id', '=', $request->id)
                ->first()
                ->name;

            DB::table(GameConfigurationController::CONFIG_TYPES_TABLES[$request->type])
                ->where('id', '=', $request->id)
                ->delete();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Games Config',
                'section_id'       => $request->id,
                'section_name'     => $name,
                'sub_section'      => GameConfigurationController::CONFIG_TYPES_CHANGELOG[$request->type],
                'sub_section_id'   => $request->id,
                'sub_section_name' => $name,
            ]);
        }

        return redirect()->route('admin.games.configuration.show', $request->type);
    }
}
