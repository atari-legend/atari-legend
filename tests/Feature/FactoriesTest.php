<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Comment;
use App\Models\CopyProtection;
use App\Models\Crew;
use App\Models\Dump;
use App\Models\Game;
use App\Models\GameGenre;
use App\Models\GameRelease;
use App\Models\GameReleaseScan;
use App\Models\Individual;
use App\Models\Interview;
use App\Models\Language;
use App\Models\Magazine;
use App\Models\MagazineIndex;
use App\Models\MagazineIndexType;
use App\Models\MagazineIssue;
use App\Models\Media;
use App\Models\MediaScan;
use App\Models\MediaScanType;
use App\Models\Memory;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskDump;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\News;
use App\Models\NewsSubmission;
use App\Models\PubDev;
use App\Models\Resolution;
use App\Models\Review;
use App\Models\Screenshot;
use App\Models\Spotlight;
use App\Models\TrainerOption;
use App\Models\Trivia;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The factories exist so that other tests do not have to know the legacy
 * schema. That only holds if the factories themselves are right, and the ways
 * they go wrong here are quiet ones: a column the legacy table requires but the
 * definition omits, or an attribute silently dropped because the model does not
 * list it as fillable.
 *
 * So every factory is created and read back at least once.
 */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Round-trip a model through the database, so a column the factory got
     * wrong surfaces as a failure here rather than inside an unrelated test.
     */
    private function assertPersists(Model $model): Model
    {
        $this->assertTrue($model->exists, get_class($model) . ' was not saved.');

        $fresh = $model->fresh();
        $this->assertNotNull($fresh, get_class($model) . ' could not be read back.');

        return $fresh;
    }

    public static function factoryProvider(): array
    {
        return [
            'game'                => [Game::class],
            'release'             => [GameRelease::class],
            'screenshot'          => [Screenshot::class],
            'publisher/developer' => [PubDev::class],
            'individual'          => [Individual::class],
            'crew'                => [Crew::class],
            'genre'               => [GameGenre::class],
            'language'            => [Language::class],
            'resolution'          => [Resolution::class],
            'memory'              => [Memory::class],
            'trainer'             => [TrainerOption::class],
            'copy protection'     => [CopyProtection::class],
            'review'              => [Review::class],
            'interview'           => [Interview::class],
            'article'             => [Article::class],
            'article type'        => [ArticleType::class],
            'news'                => [News::class],
            'website'             => [Website::class],
            'website category'    => [WebsiteCategory::class],
            'menu set'            => [MenuSet::class],
            'menu'                => [Menu::class],
            'menu disk'           => [MenuDisk::class],
            'menu software'       => [MenuSoftware::class],
            'magazine'            => [Magazine::class],
            'magazine issue'      => [MagazineIssue::class],
            'magazine index'      => [MagazineIndex::class],
            'magazine index type' => [MagazineIndexType::class],
            'release scan'        => [GameReleaseScan::class],
            'media'               => [Media::class],
            'media scan'          => [MediaScan::class],
            'media scan type'     => [MediaScanType::class],
            'dump'                => [Dump::class],
            'menu disk dump'      => [MenuDiskDump::class],
            'spotlight'           => [Spotlight::class],
            'comment'             => [Comment::class],
            'news submission'     => [NewsSubmission::class],
            'trivia'              => [Trivia::class],
            'user'                => [User::class],
        ];
    }

    /**
     * A comment reaches whatever it is attached to through one of four pivot
     * tables, and `type` throws outright when it is attached to none of them.
     * Each state has to land in the right table.
     */
    public function test_comment_states_attach_to_their_target(): void
    {
        $this->assertSame(Comment::TYPE_GAME, Comment::factory()->onGame()->create()->type);
        $this->assertSame(Comment::TYPE_REVIEW, Comment::factory()->onReview()->create()->type);
        $this->assertSame(Comment::TYPE_ARTICLE, Comment::factory()->onArticle()->create()->type);
        $this->assertSame(Comment::TYPE_INTERVIEW, Comment::factory()->onInterview()->create()->type);
    }

    public function test_a_comment_can_be_attached_to_a_named_game(): void
    {
        $game = Game::factory()->named('Turrican')->create();

        $comment = Comment::factory()->onGame($game)->create();

        $this->assertSame('Turrican', $comment->target);
        $this->assertSame($game->getKey(), $comment->target_id);
    }

    /**
     * The scan types are what the release and media scan panels group by, so a
     * scan created with a type has to read that type back.
     */
    public function test_scan_factories_carry_their_type(): void
    {
        $scan = GameReleaseScan::factory()->ofType(GameReleaseScan::TYPE_BOX_BACK)->create();
        $this->assertSame(GameReleaseScan::TYPE_BOX_BACK, $scan->type);

        $mediaScan = MediaScan::factory()->create();
        $this->assertNotNull($mediaScan->type);
        $this->assertSame(MediaScanType::TYPE_OTHER, $mediaScan->type->name);
    }

    /**
     * A spotlight with no screenshot is a real state - the image is keyed on
     * `screenshot_id`, so a null one means no image rather than a broken one.
     */
    public function test_a_spotlight_can_have_no_screenshot(): void
    {
        $spotlight = Spotlight::factory()->withoutScreenshot()->create();

        $this->assertNull($spotlight->screenshot_id);
        $this->assertNull($spotlight->screenshot);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('factoryProvider')]
    public function test_factory_creates_a_persisted_model(string $model): void
    {
        $this->assertPersists($model::factory()->create());
    }

    /**
     * Two calls must not collide, which is the failure mode for the tables with
     * a unique index - `game.slug` above all.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('factoryProvider')]
    public function test_factory_can_create_several_models(string $model): void
    {
        $models = $model::factory()->count(3)->create();

        $this->assertCount(3, $models);
        $this->assertCount(3, $models->pluck($models->first()->getKeyName())->unique());
    }

    public function test_game_slug_follows_the_name(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();

        $this->assertSame('Bubble Bobble', $game->game_name);
        $this->assertSame('bubble-bobble', $game->slug);
    }

    /**
     * The public game page binds on the slug, so a game the factory made has to
     * be reachable by it.
     */
    public function test_a_factory_game_is_reachable_on_the_public_site(): void
    {
        $game = Game::factory()->named('Rick Dangerous')->withScreenshot()->withRelease()->create();

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Rick Dangerous');
    }

    public function test_release_states_attach_their_relations(): void
    {
        $release = GameRelease::factory()
            ->publishedBy('Ocean')
            ->crackedBy('The Replicants')
            ->inLanguages('en', 'fr')
            ->inResolutions('Low')
            ->withTrainer('Infinite lives')
            ->create();

        $this->assertSame('Ocean', $release->publisher->pub_dev_name);
        $this->assertSame(['The Replicants'], $release->crews->pluck('crew_name')->all());
        $this->assertSame(['en', 'fr'], $release->languages->pluck('id')->sort()->values()->all());
        $this->assertSame(['Low'], $release->resolutions->pluck('name')->all());
        $this->assertSame(['Infinite lives'], $release->trainers->pluck('name')->all());
    }

    /**
     * `language.id` is an ISO code, not a generated integer. Eloquent would
     * otherwise replace it with the driver's last insert id.
     */
    public function test_language_keeps_the_code_it_was_given(): void
    {
        $language = Language::factory()->create(['id' => 'de', 'name' => 'German']);

        $this->assertSame('de', $language->id);
        $this->assertSame('de', Language::find('de')->id);
    }

    public function test_undated_releases_report_no_year(): void
    {
        $this->assertSame('[no date]', GameRelease::factory()->undated()->create()->year);
    }

    public function test_review_states_build_a_complete_review(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $review = Review::factory()
            ->forGame($game->getKey())
            ->scored(graphics: 5, sound: 3, gameplay: 4, overall: 4)
            ->create();

        $this->assertSame('Xenon', $review->games->first()->game_name);
        $this->assertSame(5, $review->review_graphics);
        $this->assertSame(3, $review->review_sound);
    }

    /**
     * Every view assumes an article and an interview carry a body, so the
     * factories always fill one in.
     */
    public function test_interviews_and_articles_come_with_their_text(): void
    {
        $this->assertNotNull(Interview::factory()->create()->interview_text);
        $this->assertNotNull(Article::factory()->create()->article_title);
    }

    public function test_article_can_be_given_a_title(): void
    {
        $article = Article::factory()->titled('Coding the blitter')->create();

        $this->assertSame('Coding the blitter', $article->article_title);
    }

    /**
     * Menu disks default to intact; the set listing counts everything else as
     * missing.
     */
    public function test_menu_disks_default_to_intact(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey()]);

        MenuDisk::factory()->create(['menu_id' => $menu->getKey(), 'part' => 'A']);
        MenuDisk::factory()->damaged()->create(['menu_id' => $menu->getKey(), 'part' => 'B']);

        $listed = $this->get(route('menus.index'))->assertOk()->viewData('menusets')->first();

        $this->assertSame(2, $listed->disks);
        $this->assertSame(1, $listed->missing);
    }

    public function test_individual_bio_is_optional(): void
    {
        $this->assertNull(Individual::factory()->create()->ind_profile);
        $this->assertSame(
            'Member of Dune.',
            Individual::factory()->withBio()->create()->ind_profile
        );
    }

    public function test_website_starts_visible_and_can_be_hidden(): void
    {
        $this->assertFalse((bool) Website::factory()->create()->inactive);
        $this->assertTrue((bool) Website::factory()->inactive()->create()->inactive);
    }

    /**
     * Guard against a factory quietly losing an attribute because the model
     * does not list it as fillable - the trap this legacy schema sets, since
     * several models declare a narrow $fillable.
     */
    public function test_non_fillable_attributes_still_reach_the_database(): void
    {
        $publisher = PubDev::factory()->create();

        // pub_dev_id is not fillable on Release
        $release = GameRelease::factory()->create(['pub_dev_id' => $publisher->getKey()]);

        $this->assertSame($publisher->getKey(), $release->fresh()->pub_dev_id);
        $this->assertFalse(
            in_array('pub_dev_id', (new GameRelease())->getFillable(), true),
            'This test is only meaningful while pub_dev_id is outside GameRelease::$fillable.'
        );
    }

    public function test_unrelated_models_do_not_leak_between_factories(): void
    {
        Game::factory()->count(2)->create();

        $this->assertSame(2, Game::query()->count());
        $this->assertSame(0, GameRelease::query()->count());
        $this->assertSame(0, Screenshot::query()->count());
    }

    /**
     * Every model now resolves its key as `id`. This started life as a check
     * that the legacy prefixed keys resolved; it is kept, inverted, as the
     * assertion that none of them came back.
     */
    public function test_primary_keys_all_resolve_to_id(): void
    {
        $this->assertSame('id', (new Game())->getKeyName());
        $this->assertSame('id', (new Individual())->getKeyName());
        $this->assertSame('id', (new Screenshot())->getKeyName());
        $this->assertSame('id', (new Review())->getKeyName());

        $this->assertNotNull(Game::factory()->create()->getKey());
        $this->assertNotNull(Individual::factory()->create()->id);
    }

    public function test_faker_helpers_produce_usable_slugs(): void
    {
        foreach (Game::factory()->count(5)->create() as $game) {
            $this->assertSame($game->slug, Str::slug($game->slug), "Slug '{$game->slug}' is not slug-safe.");
            $this->assertNotSame('', $game->slug);
        }
    }
}
