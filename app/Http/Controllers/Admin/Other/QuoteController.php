<?php

namespace App\Http\Controllers\Admin\Other;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\TriviaQuote;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = TriviaQuote::orderByDesc('trivia_quote_id')
            ->get();

        return view('admin.others.quotes.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.quotes.index'), 'Quotes'),
                ],
                'quotes'     => $quotes,
            ]);
    }

    public function update(Request $request, TriviaQuote $quote)
    {
        $request->validate([
            'text' => 'required',
        ]);

        $oldText = $quote->trivia_quote;

        $quote->trivia_quote = $request->text;
        $quote->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Trivia',
            'section_id'       => $quote->getKey(),
            'section_name'     => $oldText,
            'sub_section'      => 'Quote',
            'sub_section_id'   => $quote->getKey(),
            'sub_section_name' => $quote->trivia_quote,
        ]);

        return redirect()->route('admin.others.quotes.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required',
        ]);

        $quote = TriviaQuote::create([
            'trivia_quote' => $request->text,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Trivia',
            'section_id'       => $quote->getKey(),
            'section_name'     => $quote->trivia_quote,
            'sub_section'      => 'Quote',
            'sub_section_id'   => $quote->getKey(),
            'sub_section_name' => $quote->trivia_quote,
        ]);

        return redirect()->route('admin.others.quotes.index');
    }

    public function destroy(TriviaQuote $quote)
    {
        $quote->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Trivia',
            'section_id'       => $quote->getKey(),
            'section_name'     => $quote->trivia_quote,
            'sub_section'      => 'Quote',
            'sub_section_id'   => $quote->getKey(),
            'sub_section_name' => $quote->trivia_quote,
        ]);

        return redirect()->route('admin.others.quotes.index');
    }
}
