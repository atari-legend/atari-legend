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
        $articles = Article::orderByDesc('article_date')
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
            $otherArticles = Article::where('user_id', $article->user->getKey())
                ->whereKeyNot($article->getKey())
                ->get();
        }

        $articles = Article::orderByDesc('article_date')
            ->limit(5)
            ->get();

        $jsonLd = (new JsonLd('Article', url()->current()))
            ->add('headline', $article->article_title)
            ->add('author', Helper::user($article->user))
            ->add('datePublished', $article->article_date->format('Y-m-d'));
        if ($article->screenshots->isNotEmpty()) {
            $jsonLd->add('image', $article->screenshots->first()->getUrl('article'));
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
            'section_name'     => $article->article_title,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $article->article_title,
        ]);

        return back();
    }
}
