<?php

namespace App\Helpers;

/**
 * The monitor bezel drawn around the emulator screen.
 */
class EmulatorBezelHelper
{
    /** Relative to resources/, where _emulator.scss also points. */
    const SVG = 'images/emulator/emulator-bezel.svg';

    /**
     * Get the CSS variables that fit the screen into the bezel's hole.
     *
     * @return string Style attribute value with the bezel's aspect ratio and the hole's position, from the SVG.
     */
    public static function screenStyle(): string
    {
        $svg = simplexml_load_file(resource_path(self::SVG));
        $svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

        [, , $width, $height] = array_map('floatval', preg_split('/\s+/', trim((string) $svg['viewBox'])));
        $hole = $svg->xpath('//svg:rect[@id="video-area"]')[0];

        $percent = fn ($value, float $of) => round((float) $value / $of * 100, 4) . '%';

        return collect([
            '--bezel-ratio'  => "{$width} / {$height}",
            '--video-left'   => $percent($hole['x'], $width),
            '--video-top'    => $percent($hole['y'], $height),
            '--video-width'  => $percent($hole['width'], $width),
            '--video-height' => $percent($hole['height'], $height),
        ])->map(fn ($value, $name) => "{$name}: {$value}")->join('; ');
    }
}
