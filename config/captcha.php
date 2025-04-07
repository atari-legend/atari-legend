<?php

/*
 * Secret key and Site key get on https://dashboard.hcaptcha.com/sites
 * */
return [
    'secret'      => env('CAPTCHA_SECRET'),
    'sitekey'     => env('CAPTCHA_SITEKEY', 'ce638d28-bb25-498f-aabe-db003adaa817'),
    // \GuzzleHttp\Client used is the default client
    'http_client' => \Buzz\LaravelHCaptcha\HttpClient::class,
    'options'     => [
        'multiple' => false,
    ],
    'attributes' => [
        'data-theme' => 'dark',
    ],
];
