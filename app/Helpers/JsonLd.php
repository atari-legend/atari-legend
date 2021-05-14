<?php

namespace App\Helpers;

/**
 * Store JSON-LD data and format it as JSON.
 */
class JsonLd
{
    private array $data = [];

    /**
     * @param string $type Type of data (e.g. 'VideoGame')
     * @param string $url  Entity URL
     */
    public function __construct(
        private string $type,
        private string $url
    ) {
        return $this;
    }

    /**
     * Add metadata.
     *
     * @param string $key   Metadata name
     * @param string $value Metadata value
     *
     * @return JsonLd JsonLd object
     */
    public function add(string $key, mixed $value): JsonLd
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Serialize the JsonLd data as JSON.
     *
     * @return string JsonLd as JSON
     */
    public function json(): string
    {
        return json_encode(array_merge([
            '@context' => 'http://schema.org',
            '@type'    => $this->type,
            'url'      => $this->url,
        ], $this->data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
