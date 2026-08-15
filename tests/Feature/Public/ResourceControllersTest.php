<?php

namespace Tests\Feature\Public;

use App\Models\Game;
use App\Models\Individual;
use App\Models\IndividualText;
use App\Models\Release;
use App\Models\ReleaseScan;
use App\Models\Screenshot;
use App\Models\Sndh;
use App\Models\Spotlight;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The routes that serve a file rather than a page: box scans, avatars,
 * spotlight and link screenshots, the music player's cover art, and the SNDH
 * proxy.
 *
 * They all read from the public disk by a path the model derives from its id
 * and its stored extension, so the fixtures below are written to exactly those
 * paths - a file one directory out is the same as no file at all.
 *
 * The four `.webp` routes re-encode what they read, which needs GD built with
 * WebP. The php.dockerfile in the development environment builds it without,
 * so those assertions skip there and run in CI, which installs the full
 * extension. The cover art route is not affected: it answers in the format it
 * read.
 */
class ResourceControllersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A tune's key is its path inside the SNDH archive, which is also the path
     * the proxy asks the record site for.
     */
    private const TUNE = 'Musicians/Mad_Max/Turrican';

    private const TUNE_URL = 'http://sndhrecord.atari.org/mp3/Musicians/Mad_Max/Turrican';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function requireWebp(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD was built without WebP support, so the image cannot be re-encoded.');
        }
    }

    /**
     * PNG rather than JPEG: it is the one format GD is guaranteed to have here.
     */
    private function storePng(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();

        imagedestroy($image);

        Storage::disk('public')->put($path, $bytes);
    }

    private function assertRequested(string $url): void
    {
        Http::assertSent(fn ($request) => $request->url() === $url);
    }

    private function sizeOf(string $bytes): array
    {
        $size = getimagesizefromstring($bytes);

        $this->assertNotFalse($size, 'The response body is not an image.');

        return [$size[0], $size[1], $size[2]];
    }

    public function test_a_box_scan_is_served_as_a_webp_of_at_most_five_hundred_pixels(): void
    {
        $this->requireWebp();

        $release = Release::factory()->create();
        $scan = ReleaseScan::factory()->create([
            'game_release_id' => $release->getKey(),
            'imgext'          => 'png',
        ]);
        $this->storePng($scan->path, 800, 600);

        $response = $this->get(route('games.releases.boxscan', ['release' => $release, 'id' => $scan->getKey()]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');

        $this->assertSame([500, 375, IMAGETYPE_WEBP], $this->sizeOf($response->streamedContent()));
    }

    /**
     * The scans are cached hard, so a box scan swapped for another one is given
     * a different id rather than the same URL.
     */
    public function test_a_box_scan_is_cached_for_a_year(): void
    {
        $this->requireWebp();

        $release = Release::factory()->create();
        $scan = ReleaseScan::factory()->create([
            'game_release_id' => $release->getKey(),
            'imgext'          => 'png',
        ]);
        $this->storePng($scan->path, 100, 100);

        $this->get(route('games.releases.boxscan', ['release' => $release, 'id' => $scan->getKey()]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=31536000, private');
    }

    /**
     * The id in the URL is looked up among that release's own scans, so a real
     * scan id asked for under the wrong release is not served.
     */
    public function test_a_box_scan_from_another_release_is_a_404(): void
    {
        $scan = ReleaseScan::factory()->create(['imgext' => 'png']);
        $this->storePng($scan->path, 100, 100);

        $this->get(route('games.releases.boxscan', [
            'release' => Release::factory()->create(),
            'id'      => $scan->getKey(),
        ]))->assertNotFound();
    }

    public function test_an_unknown_box_scan_is_a_404(): void
    {
        $this->get(route('games.releases.boxscan', [
            'release' => Release::factory()->create(),
            'id'      => 9999,
        ]))->assertNotFound();
    }

    public function test_an_avatar_is_served_as_a_webp(): void
    {
        $this->requireWebp();

        $individual = Individual::factory()->create();
        $text = IndividualText::forceCreate([
            'ind_id'     => $individual->getKey(),
            'ind_imgext' => 'png',
        ]);
        $this->storePng($text->path, 800, 800);

        $response = $this->get(route('individuals.avatar', $individual))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');

        $this->assertSame([500, 500, IMAGETYPE_WEBP], $this->sizeOf($response->streamedContent()));
    }

    /**
     * A smaller portrait is served as it is: the resize only shrinks.
     */
    public function test_a_small_avatar_is_not_blown_up(): void
    {
        $this->requireWebp();

        $individual = Individual::factory()->create();
        $text = IndividualText::forceCreate([
            'ind_id'     => $individual->getKey(),
            'ind_imgext' => 'png',
        ]);
        $this->storePng($text->path, 120, 90);

        $response = $this->get(route('individuals.avatar', $individual))->assertOk();

        $this->assertSame([120, 90, IMAGETYPE_WEBP], $this->sizeOf($response->streamedContent()));
    }

    /**
     * Most individuals have an `individual_text` row with no picture in it,
     * which is what the empty extension means.
     */
    public function test_an_individual_with_no_picture_is_a_404(): void
    {
        $individual = Individual::factory()->withBio()->create();

        $this->get(route('individuals.avatar', $individual))->assertNotFound();
    }

    public function test_an_individual_with_no_text_row_is_a_404(): void
    {
        $individual = Individual::factory()->create();

        $this->get(route('individuals.avatar', $individual))->assertNotFound();
    }

    public function test_a_spotlight_screenshot_is_served_as_a_webp(): void
    {
        $this->requireWebp();

        $spotlight = Spotlight::factory()->create();
        $this->storePng($spotlight->screenshot->getPath('spotlight'), 640, 480);

        $response = $this->get(route('spotlights.screenshot', $spotlight))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');

        $this->assertSame([500, 375, IMAGETYPE_WEBP], $this->sizeOf($response->streamedContent()));
    }

    public function test_a_spotlight_with_no_screenshot_is_a_404(): void
    {
        $this->get(route('spotlights.screenshot', Spotlight::factory()->withoutScreenshot()->create()))
            ->assertNotFound();
    }

    public function test_a_link_screenshot_is_served_as_a_webp(): void
    {
        $this->requireWebp();

        $website = Website::factory()->create(['website_imgext' => 'png']);
        $this->storePng($website->path, 1024, 768);

        $response = $this->get(route('websites.screenshot', $website))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');

        $this->assertSame([500, 375, IMAGETYPE_WEBP], $this->sizeOf($response->streamedContent()));
    }

    /**
     * Links are submitted without a screenshot; an administrator adds one
     * later, and until then there is nothing to serve.
     */
    public function test_a_link_with_no_screenshot_is_a_404(): void
    {
        $this->get(route('websites.screenshot', Website::factory()->create()))->assertNotFound();
    }

    /**
     * The music player wants a square cover, and the screenshots are 4:3. The
     * shot is padded rather than cropped, so the square is as wide as it was.
     */
    public function test_a_music_cover_is_the_first_screenshot_on_a_square_canvas(): void
    {
        $game = Game::factory()->create();
        $screenshot = Screenshot::factory()->create();
        $game->screenshots()->attach($screenshot);
        $this->storePng($screenshot->getPath('game'), 320, 200);

        $response = $this->get(route('music.cover', $game))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertSame([320, 320, IMAGETYPE_PNG], $this->sizeOf($response->getContent()));
    }

    public function test_a_game_with_no_screenshot_has_no_cover(): void
    {
        $this->get(route('music.cover', Game::factory()->create()))
            ->assertNotFound()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSee('No screenshot for this game');
    }

    /**
     * The tunes are served over plain HTTP by sndhrecord.atari.org, which a
     * browser will not load into an HTTPS page - hence the proxy.
     */
    public function test_a_tune_is_proxied_from_the_sndh_record_site(): void
    {
        Http::fake([
            'sndhrecord.atari.org/*' => Http::response('ID3-the-mp3', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $sndh = Sndh::factory()->create(['id' => self::TUNE]);

        $this->get(route('music', $sndh))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('ID3-the-mp3');

        $this->assertRequested(self::TUNE_URL . '.mp3');
    }

    /**
     * Subtunes are separate files upstream, numbered from one and padded to
     * three digits.
     */
    public function test_a_subtune_is_asked_for_by_its_padded_number(): void
    {
        Http::fake(['sndhrecord.atari.org/*' => Http::response('ID3-the-mp3')]);

        $sndh = Sndh::factory()->withSubtunes(4)->create(['id' => self::TUNE]);

        $this->get(route('music', ['sndh' => $sndh, 'subtune' => 3]))->assertOk();

        $this->assertRequested(self::TUNE_URL . '-003.mp3');
    }

    /**
     * The first subtune is the file with no suffix, so a request for it must
     * not ask for a `-001` that is not there.
     */
    public function test_the_default_subtune_has_no_suffix(): void
    {
        Http::fake(['sndhrecord.atari.org/*' => Http::response('ID3-the-mp3')]);

        $sndh = Sndh::factory()->create(['id' => self::TUNE]);

        $this->get(route('music', ['sndh' => $sndh, 'subtune' => 0]))->assertOk();

        $this->assertRequested(self::TUNE_URL . '.mp3');
    }

    public function test_a_tune_missing_upstream_is_passed_through_as_a_404(): void
    {
        Http::fake(['sndhrecord.atari.org/*' => Http::response('Not found', 404)]);

        $sndh = Sndh::factory()->create(['id' => self::TUNE]);

        $this->get(route('music', $sndh))->assertNotFound();
    }

    public function test_an_unknown_tune_is_a_404(): void
    {
        Http::fake();

        $this->get('/music/Musicians/Nobody/Nothing')->assertNotFound();

        Http::assertNothingSent();
    }
}
