<?php

namespace App\Http\Controllers;

use App\Article;
use App\Comment;
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

        return view('articles.show')
            ->with([
                'article'       => $article,
                'articles'      => $articles,
                'otherArticles' => $otherArticles,
            ]);
    }

    public function postComment(Article $article, Request $request)
    {
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $article->comments()->save($comment);

        return back();
    }
}
