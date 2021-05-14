<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Helpers\JsonLd;
use App\Models\Article;
use App\Models\Changelog;
use App\Models\Comment;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::select()
            ->join('article_text', 'article_text.article_id', '=', 'article_main.article_id')
            ->orderByDesc('article_text.article_date')
            ->paginate(5);

        return view('articles.index')
            ->with([
                'articles'  => $articles,
            ]);
    }

    public function show(Article $article)
    {
        $otherArticles = collect([]);

        if (isset($article->user)) {
            $otherArticles = Article::where('user_id', $article->user->user_id)
                ->where('article_id', '!=', $article->article_id)
                ->get();
        }

        $articles = Article::select()
            ->join('article_text', 'article_text.article_id', '=', 'article_main.article_id')
            ->orderByDesc('article_text.article_date')
            ->limit(5)
            ->get();

        $jsonLd = (new JsonLd('Article', url()->current()))
            ->add('headline', $article->texts->first()->article_title)
            ->add('author', Helper::user($article->user))
            ->add('datePublished', date('Y-m-d', $article->texts->first()->article_date));
        if ($article->screenshots->isNotEmpty()) {
            $jsonLd->add('image', asset('storage/images/article_screenshots/'.$article->screenshots->first()->screenshot->file));
        }

        return view('articles.show')
            ->with([
                'article'       => $article,
                'articles'      => $articles,
                'otherArticles' => $otherArticles,
                'jsonLd'        => $jsonLd,
            ]);
    }

    public function postComment(Article $article, Request $request)
    {
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $article->comments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Articles',
            'section_id'       => $article->getKey(),
            'section_name'     => $article->texts()->first()->article_title,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $article->texts()->first()->article_title,
        ]);

        return back();
    }
}
