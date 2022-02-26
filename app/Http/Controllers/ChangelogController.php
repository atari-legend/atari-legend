<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangelogController extends Controller
{
    public function index(Request $request)
    {
        $firstChange = Changelog::orderBy('timestamp', 'asc')
            ->first();

        [$section, $subsection] = $this->getFilter($request);

        $changes = $this->getChanges($section, $subsection)
            ->paginate(50);

        $sections = DB::table('change_log')
            ->select(['section', 'sub_section'])
            ->groupBy(['section', 'sub_section'])
            ->orderBy('section', 'asc')
            ->orderBy('sub_section', 'asc')
            ->get()
            ->groupBy('section');

        return view('changelog.index', [
            'changes'           => $changes,
            'firstChange'       => $firstChange,
            'sections'          => $sections,
            'currentSection'    => $section,
            'currentSubsection' => $subsection,
        ]);
    }

    public function feed(Request $request)
    {
        [$section, $subsection] = $this->getFilter($request);

        $changes = $this->getChanges($section, $subsection)
            ->limit(25);

        return $changes->get();
    }

    private function getChanges(?string $section, ?string $subsection)
    {
        $changes = Changelog::query();
        if ($section) {
            $changes = $changes->where('section', '=', $section);
            if ($subsection) {
                $changes = $changes->where('sub_section', '=', $subsection);
            }
        }

        return $changes->orderBy('timestamp', 'desc');
    }

    private function getFilter(Request $request): array
    {
        if ($request->filter) {
            $parts = explode(':', $request->filter);
            $section = $parts[0];
            $subsection = $parts[1] ?? null;

            return [$section, $subsection];
        } else {
            return [null, null];
        }
    }
}
