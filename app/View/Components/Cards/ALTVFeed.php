<?php

namespace App\View\Components\Cards;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Laminas\Feed\Reader\Reader;

class ALTVFeed extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        try {
            $feed = Reader::import('https://www.youtube.com/feeds/videos.xml?channel_id=UCN7ePamELHkBVAHwXa4cubg');
            $xpath = $feed->getXPath();
            $xpath->registerNamespace('media', 'http://search.yahoo.com/mrss/');

            $entries = [];

            foreach ($feed as $entry) {
                $xpathPrefix = $entry->getXpathPrefix();
                $thumbnail = $xpath->evaluate('string('.$xpathPrefix.'/media:group/media:thumbnail/@url)');
                $entries[] = [
                    'title'     => $entry->getTitle(),
                    'link'      => $entry->getLink(),
                    'date'      => $entry->getDateCreated(),
                    'thumbnail' => $thumbnail,
                ];
            }

            return view('components.cards.altv-feed')
                ->with([
                    'entries' => $entries,
                ]);
        } catch (Exception $e) {
            Log::warning('Error retrieving ALTV YouTube feed', ['Exception' => $e]);

            return view('components.cards.altv-feed-fallback');
        }
    }
}
