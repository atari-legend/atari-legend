<?php

use App\Helpers\FeedHelper;
use App\Http\Controllers\ChangelogController;

return [
    'feeds' => [
        'main' => [
            /*
             * Here you can specify which class and method will return
             * the items that should appear in the feed. For example:
             * [App\Model::class, 'getAllFeedItems']
             *
             * You can also pass an argument to that method.  Note that their key must be the name of the parameter:
             * [App\Model::class, 'getAllFeedItems', 'parameterName' => 'argument']
             */
            'items' => [FeedHelper::class, 'getFeedItems'],

            /*
             * The feed will be available on this url.
             */
            'url' => '',

            'title'       => 'Atari Legend - Latest News, Reviews, Interviews and Articles',
            'description' => 'Legends Never Die!',
            'language'    => 'en-US',

            /*
             * The image to display for the feed.  For Atom feeds, this is displayed as
             * a banner/logo; for RSS and JSON feeds, it's displayed as an icon.
             * An empty value omits the image attribute from the feed.
             */
            'image' => env('APP_URL') . '/images/favicon.png',

            /*
             * The format of the feed.  Acceptable values are 'rss', 'atom', or 'json'.
             */
            'format' => 'atom',

            /*
             * The view that will render the feed.
             */
            'view' => 'feed::atom',

            /*
             * The mime type to be used in the <link> tag.  Set to an empty string to automatically
             * determine the correct value.
             */
            'type' => '',

            /*
             * The content type for the feed response.  Set to an empty string to automatically
             * determine the correct value.
             */
            'contentType' => '',
        ],
        'changelog' => [
            'items'       => [ChangelogController::class, 'feed'],
            'url'         => 'changelog',
            'title'       => 'Atari Legend - Database Changes',
            'description' => 'Changes made to the Atari Legend database by the contributors.',
            'language'    => 'en-US',
            'image'       => env('APP_URL') . '/images/favicon.png',
            'format'      => 'atom',
            'view'        => 'feed::atom',
            'type'        => '',
            'contentType' => '',
        ],
    ],
];
