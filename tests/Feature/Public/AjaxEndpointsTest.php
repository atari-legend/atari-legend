<?php

namespace Tests\Feature\Public;

use App\Models\Crew;
use App\Models\Engine;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Individual;
use App\Models\MenuSoftware;
use App\Models\PublisherDeveloper;
use App\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The autocomplete endpoints other than the two game ones, which
 * AutocompleteTest covers.
 *
 * They are all shaped the same way - a `q` matched anywhere in the name, an
 * alphabetical list, at most ten results - so those three rules are tested
 * against every endpoint at once. What differs is the keys each one returns,
 * and the boxes read them by name: `data-autocomplete-key` and
 * `data-autocomplete-id` in the search forms name the very keys asserted below,
 * so dropping or renaming one silently empties a dropdown.
 *
 * `ajax.release-years` is shaped differently - it matches a year prefix rather
 * than a name - so it is tested on its own at the end.
 */
class AjaxEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Route name and the key the front end reads the name out of.
     */
    public static function endpoints(): array
    {
        return [
            'companies'   => ['ajax.companies', 'pub_dev_name'],
            'crews'       => ['ajax.crews', 'crew_name'],
            'engines'     => ['ajax.engines', 'name'],
            'genres'      => ['ajax.genres', 'name'],
            'software'    => ['ajax.software', 'name'],
            'individuals' => ['ajax.individuals', 'ind_name'],
        ];
    }

    /**
     * Engines and genres have no factory - they are two-column reference tables
     * with nothing to generate - so they are created straight from the model.
     */
    private function create(string $route, string $name): void
    {
        match ($route) {
            'ajax.companies'   => PublisherDeveloper::factory()->create(['pub_dev_name' => $name]),
            'ajax.crews'       => Crew::factory()->create(['crew_name' => $name]),
            'ajax.engines'     => Engine::forceCreate(['name' => $name]),
            'ajax.genres'      => Genre::forceCreate(['name' => $name]),
            'ajax.software'    => MenuSoftware::factory()->named($name)->create(),
            'ajax.individuals' => Individual::factory()->create(['ind_name' => $name]),
        };
    }

    private function names(string $route, string $key, ?string $q = null): array
    {
        return array_column(
            $this->getJson(route($route, $q === null ? [] : ['q' => $q]))->assertOk()->json(),
            $key
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_an_endpoint_matches_anywhere_in_the_name(string $route, string $key): void
    {
        $this->create($route, 'Bitmap Brothers');
        $this->create($route, 'Psygnosis');

        $this->assertSame(['Bitmap Brothers'], $this->names($route, $key, 'rothe'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_an_endpoint_returns_an_empty_list_when_nothing_matches(string $route, string $key): void
    {
        $this->create($route, 'Bitmap Brothers');

        $this->assertSame([], $this->names($route, $key, 'Llamasoft'));
    }

    /**
     * With no term the box shows a starting point rather than nothing, and the
     * list has to be in a usable order.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_an_endpoint_lists_alphabetically_with_no_query(string $route, string $key): void
    {
        foreach (['Zeta', 'Alpha', 'Mu'] as $name) {
            $this->create($route, $name);
        }

        $this->assertSame(['Alpha', 'Mu', 'Zeta'], $this->names($route, $key));
    }

    /**
     * The dropdown is a shortlist, not a listing: it stops at ten however many
     * rows match.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_an_endpoint_returns_at_most_ten_results(string $route, string $key): void
    {
        foreach (range(10, 24) as $i) {
            $this->create($route, 'Delta ' . $i);
        }

        $this->assertCount(10, $this->names($route, $key, 'Delta'));
        $this->assertCount(10, $this->names($route, $key));
    }

    public function test_a_company_carries_its_id(): void
    {
        $company = PublisherDeveloper::factory()->create(['pub_dev_name' => 'Psygnosis']);

        $results = $this->getJson(route('ajax.companies', ['q' => 'Psy']))->assertOk()->json();

        $this->assertSame('Psygnosis', $results[0]['pub_dev_name']);
        $this->assertSame($company->getKey(), $results[0]['pub_dev_id']);
    }

    public function test_a_crew_carries_its_id_and_nothing_else(): void
    {
        $crew = Crew::factory()->create(['crew_name' => 'Automation']);

        $results = $this->getJson(route('ajax.crews', ['q' => 'Auto']))->assertOk()->json();

        $this->assertSame(['crew_name' => 'Automation', 'id' => $crew->getKey()], $results[0]);
    }

    /**
     * Engines and genres are picked by name into a text field - there is no id
     * to send back - so the rows are a single key.
     */
    public function test_an_engine_and_a_genre_come_back_as_a_name_alone(): void
    {
        Engine::forceCreate(['name' => 'AGOS']);
        Genre::forceCreate(['name' => 'Shoot-em-up']);

        $this->assertSame(
            ['name' => 'AGOS'],
            $this->getJson(route('ajax.engines', ['q' => 'AGO']))->assertOk()->json()[0]
        );

        $this->assertSame(
            ['name' => 'Shoot-em-up'],
            $this->getJson(route('ajax.genres', ['q' => 'Shoot']))->assertOk()->json()[0]
        );
    }

    public function test_a_piece_of_software_carries_its_id(): void
    {
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $results = $this->getJson(route('ajax.software', ['q' => 'Xtra']))->assertOk()->json();

        $this->assertSame(['name' => 'Xtracker', 'id' => $software->getKey()], $results[0]);
    }

    public function test_an_individual_carries_their_id(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);

        $results = $this->getJson(route('ajax.individuals', ['q' => 'Hippel']))->assertOk()->json();

        $this->assertSame(['ind_name' => 'Jochen Hippel', 'ind_id' => $individual->getKey()], $results[0]);
    }

    /**
     * Real names mean little on their own here, so the nicknames an individual
     * is credited under are spelled out in the label - which is all that tells
     * two people with similar names apart.
     */
    public function test_an_individual_is_labelled_with_their_nicknames(): void
    {
        Individual::factory()->nicknamed('Mad Max')->create(['ind_name' => 'Jochen Hippel']);

        $results = $this->getJson(route('ajax.individuals', ['q' => 'Hippel']))->assertOk()->json();

        $this->assertSame('Jochen Hippel (aka: Mad Max)', $results[0]['ind_name']);
    }

    /**
     * A nickname is an individual too, and its label names the people it stands
     * for - the same relation read from the other end.
     */
    public function test_a_nickname_is_labelled_with_the_individuals_behind_it(): void
    {
        Individual::factory()->nicknamed('Mad Max')->create(['ind_name' => 'Jochen Hippel']);

        $results = $this->getJson(route('ajax.individuals', ['q' => 'Mad Max']))->assertOk()->json();

        $this->assertSame('Mad Max (aka: Jochen Hippel)', $results[0]['ind_name']);
    }

    /**
     * The games an individual worked on are appended so an editor can tell who
     * they are picking, and someone credited twice on one game must not have it
     * listed twice.
     */
    public function test_an_individual_is_labelled_with_the_games_they_worked_on(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);
        $game = Game::factory()->named('Xenon')->create();

        foreach (['Coder', 'Music'] as $role) {
            $individual->games()->attach($game, [
                'individual_role_id' => DB::table('individual_role')->insertGetId(['name' => $role]),
            ]);
        }

        $results = $this->getJson(route('ajax.individuals', ['q' => 'Hippel']))->assertOk()->json();

        $this->assertSame('Jochen Hippel [Xenon]', $results[0]['ind_name']);
    }

    /**
     * A prolific individual would otherwise push a label past the width of the
     * dropdown, so the list of games is cut short.
     */
    public function test_a_long_list_of_games_is_cut_short(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);
        $roleId = DB::table('individual_role')->insertGetId(['name' => 'Music']);

        foreach (range(1, 10) as $i) {
            $individual->games()->attach(
                Game::factory()->named('Wonderful Game ' . $i)->create(),
                ['individual_role_id' => $roleId]
            );
        }

        $label = $this->getJson(route('ajax.individuals', ['q' => 'Hippel']))->assertOk()->json()[0]['ind_name'];

        $this->assertSame(
            'Jochen Hippel [Wonderful Game 1, Wonderful Game 2, Wonderful…]',
            $label
        );
    }

    public function test_the_release_year_endpoint_is_routed(): void
    {
        $this->assertSame(url('/ajax/release-years.json'), route('ajax.release-years'));
    }

    public function test_the_release_year_endpoint_filters_on_the_year_typed_so_far(): void
    {
        foreach (['1988-05-02', '1989-11-30', '1992-01-01'] as $date) {
            Release::factory()->create(['date' => $date]);
        }

        $years = $this->getJson(route('ajax.release-years', ['q' => '198']))->assertOk()->json();

        $this->assertSame(['1988', '1989'], array_column($years, 'year'));
    }

    /**
     * `q` reaches a whereRaw(), so it has to arrive as a binding rather than be
     * pasted into the SQL. Pasted, the first payload closes the quoted term and
     * ors a true condition onto it - handing back every year in the database -
     * and the second leaves a quote unbalanced, which is a syntax error rather
     * than an empty dropdown.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('injectionPayloads')]
    public function test_the_release_year_endpoint_treats_the_query_as_data(string $payload): void
    {
        Release::factory()->create(['date' => '1988-05-02']);

        $this->getJson(route('ajax.release-years', ['q' => $payload]))
            ->assertOk()
            ->assertExactJson([]);
    }

    public static function injectionPayloads(): array
    {
        return [
            'always true'      => ["1900' or '1' like '1"],
            'unbalanced quote' => ["19'"],
        ];
    }
}
