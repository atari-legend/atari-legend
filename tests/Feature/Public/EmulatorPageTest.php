<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\EmulatorController;
use App\Models\Dump;
use App\Models\GameRelease;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The page a dump is played on in the emulator.
 */
class EmulatorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_release_page_links_each_dump_to_the_emulator(): void
    {
        $dump = Dump::factory()->create();
        $release = $dump->media->release;

        $this->get(route('games.releases.show', $release))
            ->assertOk()
            ->assertSee(route('games.releases.emulator', ['release' => $release, 'dump' => $dump]));
    }

    public function test_the_emulator_boots_the_dump_on_the_tos(): void
    {
        $dump = Dump::factory()->create();

        $this->get(route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]))
            ->assertOk()
            ->assertSee($dump->download_url)
            ->assertSee(asset('storage/' . EmulatorController::TOS));
    }

    public function test_the_emulator_offers_every_disk_of_the_release(): void
    {
        $first = Dump::factory()->create();
        $release = $first->media->release;
        $secondMedia = Media::factory()->create(['game_release_id' => $release->getKey(), 'label' => 'Disk 2']);
        $second = Dump::factory()->create(['media_id' => $secondMedia->getKey()]);

        $this->get(route('games.releases.emulator', ['release' => $release, 'dump' => $first]))
            ->assertOk()
            ->assertSeeInOrder([$first->media->label, 'Disk 2'])
            ->assertSee('data-emulator-disk="' . $second->getKey() . '"', false)
            ->assertSee($second->download_url);
    }

    public function test_dumps_of_the_same_disk_are_told_apart_by_their_format(): void
    {
        $stx = Dump::factory()->create(['format' => 'stx']);
        Dump::factory()->create(['media_id' => $stx->media_id, 'format' => 'msa']);

        $this->get(route('games.releases.emulator', ['release' => $stx->media->release, 'dump' => $stx]))
            ->assertOk()
            ->assertSee($stx->media->label . ' (STX)')
            ->assertSee($stx->media->label . ' (MSA)');
    }

    public function test_a_release_on_a_single_disk_has_no_disk_buttons(): void
    {
        $dump = Dump::factory()->create();

        $this->get(route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]))
            ->assertOk()
            ->assertDontSee('data-emulator-disk', false);
    }

    public function test_a_dump_is_not_played_under_another_release(): void
    {
        $dump = Dump::factory()->create();
        $otherRelease = GameRelease::factory()->create();

        $this->get(route('games.releases.emulator', ['release' => $otherRelease, 'dump' => $dump]))
            ->assertNotFound();
    }
}
