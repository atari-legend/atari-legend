<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleText;
use App\Models\ArticleType;
use App\Models\Changelog;
use App\Models\Screenshot;
use App\Models\ScreenshotArticle;
use App\Models\ScreenshotArticleComment;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.articles.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.articles.articles.index'), 'Articles'),
                ],
            ]);
    }

    public function edit(Article $article)
    {
        $types = ArticleType::orderBy('article_type')
            ->get();

        return view('admin.articles.articles.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.articles.articles.index'), 'Articles'),
                    new Crumb('', $article->texts->first()->article_title),
                ],
                'article'     => $article,
                'types'       => $types,
            ]);
    }

    public function create()
    {
        $types = ArticleType::orderBy('article_type')
            ->get();

        return view('admin.articles.articles.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.articles.articles.index'), 'Articles'),
                    new Crumb('', 'Create'),
                ],
                'types'       => $types,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->getValidationRules());

        $article = Article::create([
            'user_id'         => $request->author,
            'article_type_id' => $request->type,
            'draft'           => $request->draft ? true : false,
        ]);

        $text = new ArticleText([
            'article_title' => $request->title,
            'article_date'  => Carbon::parse($request->date)->timestamp,
            'article_text'  => $request->text,
            'article_intro' => $request->intro,
        ]);

        $article->texts()->save($text);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Articles',
            'section_id'       => $article->getKey(),
            'section_name'     => $text->article_title,
            'sub_section'      => 'Article',
            'sub_section_id'   => $article->getKey(),
            'sub_section_name' => $text->article_title,
        ]);

        return redirect()->route('admin.articles.articles.index');
    }

    public function update(Request $request, Article $article)
    {
        $request->validate($this->getValidationRules());

        $article->update([
            'user_id'         => $request->author,
            'article_type_id' => $request->type,
            'draft'           => $request->draft ? true : false,
        ]);

        $oldTitle = $article->texts->first()->article_title;
        $article->texts->first()->update([
            'article_title' => $request->title,
            'article_date'  => Carbon::parse($request->date)->timestamp,
            'article_text'  => $request->text,
            'article_intro' => $request->intro,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Articles',
            'section_id'       => $article->getKey(),
            'section_name'     => $oldTitle,
            'sub_section'      => 'Article',
            'sub_section_id'   => $article->getKey(),
            'sub_section_name' => $article->texts->first()->article_title,
        ]);

        return redirect()->route('admin.articles.articles.index');
    }

    public function destroy(Article $article)
    {
        $oldTitle = $article->texts->first()->article_title;

        foreach ($article->screenshots as $screenshot) {
            Storage::disk('public')->delete($screenshot->getPath('article'));
            $screenshot->delete();
        }

        foreach ($article->texts as $text) {
            $text->delete();
        }

        $article->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Articles',
            'section_id'       => $article->getKey(),
            'section_name'     => $oldTitle,
            'sub_section'      => 'Article',
            'sub_section_id'   => $article->getKey(),
            'sub_section_name' => $oldTitle,
        ]);

        return redirect()->route('admin.articles.articles.index');
    }

    public function storeImage(Request $request, Article $article)
    {
        $request->validate([
            'image' => 'array',
        ]);

        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $screenshot = Screenshot::create([
                    'imgext' => strtolower($image->extension()),
                ]);

                $image->storeAs($screenshot->getFolder('article'), $screenshot->file, 'public');

                $article->screenshots()->attach($screenshot);

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Articles',
                    'section_id'       => $article->getKey(),
                    'section_name'     => $article->texts->first()->article_title,
                    'sub_section'      => 'Screenshots',
                    'sub_section_id'   => $screenshot->getKey(),
                    'sub_section_name' => $screenshot->file,
                ]);
            }
        }

        return redirect()->route('admin.articles.articles.edit', $article);
    }

    public function destroyImage(Article $article, Screenshot $image)
    {
        Storage::disk('public')->delete($image->getPath('article'));
        $image->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Articles',
            'section_id'       => $article->getKey(),
            'section_name'     => $article->texts->first()->article_title,
            'sub_section'      => 'Screenshots',
            'sub_section_id'   => $image->getKey(),
            'sub_section_name' => $image->file,
        ]);

        return redirect()->route('admin.articles.articles.edit', $article);
    }

    public function updateImage(Request $request, Article $article)
    {
        $request->collect()
            ->filter(fn ($value, $key) => str_starts_with($key, 'description-'))
            ->each(function ($value, $key) {
                $screenshotId = str_replace('description-', '', $key);
                $screenshotArticle = ScreenshotArticle::findOrFail($screenshotId);
                $comment = $screenshotArticle->comment;
                if (! $comment && $value) {
                    $comment = $screenshotArticle->comment()->save(new ScreenshotArticleComment([
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
            'section'          => 'Articles',
            'section_id'       => $article->getKey(),
            'section_name'     => $article->texts->first()->article_title,
            'sub_section'      => 'Screenshots',
            'sub_section_id'   => $article->getKey(),
            'sub_section_name' => $article->texts->first()->article_title,
        ]);

        return redirect()->route('admin.articles.articles.edit', $article);
    }

    private function getValidationRules(): array
    {
        return [
            'title'  => 'required',
            'author' => 'required|exists:users,user_id',
            'date'   => 'required|date',
            'intro'  => 'required',
            'text'   => 'required',
            'type'   => 'nullable',
            'draft'  => 'nullable',
        ];
    }
}
