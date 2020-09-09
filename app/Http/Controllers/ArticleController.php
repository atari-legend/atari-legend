<?php

namespace App\Http\Controllers;

use App\Article;

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
}
