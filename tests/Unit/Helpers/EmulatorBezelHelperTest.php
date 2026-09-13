<?php

namespace Tests\Unit\Helpers;

use App\Helpers\EmulatorBezelHelper;
use Tests\TestCase;

/**
 * The screen's place in the bezel, measured from the bezel's SVG.
 */
class EmulatorBezelHelperTest extends TestCase
{
    /**
     * @return array<string, string> The style's CSS variables, by name.
     */
    private function variables(): array
    {
        preg_match_all('/(--[a-z-]+): ([^;]+)/', EmulatorBezelHelper::screenStyle(), $matches);

        return array_combine($matches[1], $matches[2]);
    }

    public function test_the_screen_takes_the_bezels_proportions(): void
    {
        $svg = simplexml_load_file(resource_path(EmulatorBezelHelper::SVG));
        [, , $width, $height] = preg_split('/\s+/', trim((string) $svg['viewBox']));

        [$ratioWidth, $ratioHeight] = explode(' / ', $this->variables()['--bezel-ratio']);

        $this->assertEqualsWithDelta((float) $width / (float) $height, (float) $ratioWidth / (float) $ratioHeight, 0.0001);
    }

    /**
     * The SVG also states its hole in percent of the image, worked out when it
     * was drawn: measuring the hole has to come to the same.
     */
    public function test_the_screen_fits_the_hole_the_bezel_describes(): void
    {
        $svg = simplexml_load_file(resource_path(EmulatorBezelHelper::SVG));
        $described = array_combine(
            ['--video-left', '--video-top', '--video-width', '--video-height'],
            preg_split('/\s+/', trim((string) $svg['data-video-rect-pct'])),
        );

        $variables = $this->variables();

        foreach ($described as $name => $percent) {
            $this->assertStringEndsWith('%', $variables[$name]);
            $this->assertEqualsWithDelta((float) $percent, (float) $variables[$name], 0.0001, $name);
        }
    }
}
