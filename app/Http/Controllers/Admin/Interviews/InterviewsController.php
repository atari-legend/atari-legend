<?php

namespace App\Http\Controllers\Admin\Interviews;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Interview;
use App\Models\InterviewText;
use App\Models\Screenshot;
use App\Models\ScreenshotInterview;
use App\Models\ScreenshotInterviewComment;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InterviewsController extends Controller
{
    public function index()
    {
        return view('admin.interviews.interviews.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.interviews.interviews.index'), 'Interviews'),
                ],
            ]);
    }

    public function edit(Interview $interview)
    {
        return view('admin.interviews.interviews.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.interviews.interviews.index'), 'Interviews'),
                    new Crumb('', $interview->individual->ind_name),
                ],
                'interview' => $interview,
            ]);
    }

    public function create()
    {
        return view('admin.interviews.interviews.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.interviews.interviews.index'), 'Interviews'),
                    new Crumb('', 'Create'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(array_merge(
            $this->getValidationRules(),
            ['individual' => 'required|exists:individuals,ind_id']
        ));

        $interview = new Interview([
            'user_id' => $request->author,
            'ind_id'  => $request->individual,
            'draft'   => $request->draft ? true : false,
        ]);
        $interview->save();

        $text = new InterviewText([
            'interview_id'       => $interview->interview_id,
            'interview_text'     => $request->text,
            'interview_intro'    => $request->intro,
            'interview_chapters' => $request->chapters,
            'interview_date'     => Carbon::parse($request->date)->timestamp,
        ]);
        $text->save();

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interview->individual->ind_name,
            'sub_section'      => 'Interview',
            'sub_section_id'   => $interview->getKey(),
            'sub_section_name' => $interview->individual->ind_name,
        ]);

        if ($request->stay) {
            return redirect()->route('admin.interviews.interviews.edit', $interview);
        } else {
            return redirect()->route('admin.interviews.interviews.index');
        }
    }

    public function update(Request $request, Interview $interview)
    {
        $request->validate($this->getValidationRules());

        $interview->update([
            'user_id' => $request->author,
            'draft'   => $request->draft ? true : false,
        ]);

        $text = $interview->texts->first() ?? new InterviewText(['interview_id' => $interview->interview_id]);
        $text->interview_text = $request->text;
        $text->interview_intro = $request->intro;
        $text->interview_chapters = $request->chapters;
        $text->interview_date = Carbon::parse($request->date)->timestamp;
        $text->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interview->individual->ind_name,
            'sub_section'      => 'Interview',
            'sub_section_id'   => $interview->getKey(),
            'sub_section_name' => $interview->individual->ind_name,
        ]);

        if ($request->stay) {
            return redirect()->route('admin.interviews.interviews.edit', $interview);
        } else {
            return redirect()->route('admin.interviews.interviews.index');
        }
    }

    public function destroy(Interview $interview)
    {
        $interviewIndividualName = $interview->individual->ind_name;

        $interview->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interviewIndividualName,
            'sub_section'      => 'Interview',
            'sub_section_id'   => $interview->getKey(),
            'sub_section_name' => $interviewIndividualName,
        ]);

        return redirect()->route('admin.interviews.interviews.index');
    }

    public function storeImage(Request $request, Interview $interview)
    {
        $request->validate([
            'image' => 'array',
        ]);

        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $screenshot = Screenshot::create([
                    'imgext' => strtolower($image->extension()),
                ]);

                $image->storeAs($screenshot->getFolder('interview'), $screenshot->file, 'public');

                $interview->screenshots()->attach($screenshot);

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Interviews',
                    'section_id'       => $interview->getKey(),
                    'section_name'     => $interview->individual->ind_name,
                    'sub_section'      => 'Screenshots',
                    'sub_section_id'   => $screenshot->getKey(),
                    'sub_section_name' => $screenshot->file,
                ]);
            }
        }

        return redirect()->route('admin.interviews.interviews.edit', $interview);
    }

    public function destroyImage(Interview $interview, Screenshot $image)
    {
        Storage::disk('public')->delete($image->getPath('interview'));
        $image->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interview->individual->ind_name,
            'sub_section'      => 'Screenshots',
            'sub_section_id'   => $image->getKey(),
            'sub_section_name' => $image->file,
        ]);

        return redirect()->route('admin.interviews.interviews.edit', $interview);
    }

    public function updateImage(Request $request, Interview $interview)
    {
        $request->collect()
            ->filter(fn ($value, $key) => str_starts_with($key, 'description-'))
            ->each(function ($value, $key) {
                $screenshotId = str_replace('description-', '', $key);
                $screenshotInterview = ScreenshotInterview::findOrFail($screenshotId);
                $comment = $screenshotInterview->comment;
                if (! $comment && $value) {
                    $screenshotInterview->comment()->save(new ScreenshotInterviewComment([
                        'comment_text' => $value,
                    ]));
                } elseif ($comment && $value) {
                    $comment->update([
                        'comment_text' => $value,
                    ]);
                } elseif ($comment && ! $value) {
                    $comment->delete();
                }
            });

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interview->individual->ind_name,
            'sub_section'      => 'Screenshots',
            'sub_section_id'   => $interview->getKey(),
            'sub_section_name' => $interview->individual->ind_name,
        ]);

        return redirect()->route('admin.interviews.interviews.edit', $interview);
    }

    private function getValidationRules(): array
    {
        return [
            'author'   => 'required|exists:users,user_id',
            'date'     => 'required|date',
            'text'     => 'required',
            'intro'    => 'nullable',
            'chapters' => 'nullable',
            'draft'    => 'nullable',
        ];
    }
}
