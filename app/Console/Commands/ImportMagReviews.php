<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Magazine;
use App\Models\MagazineIndex;
use App\Models\MagazineIndexType;
use App\Models\MagazineIssue;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Reader;
use League\Csv\Writer;

class ImportMagReviews extends Command
{

    const HEADER_FAILURE_REASON = "Import failure reason";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magreviews:import {csvfile : Path to CSV file of magazine reviews}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Magazine Reviews';

    private Writer $outCsv;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $csvFile = $this->argument('csvfile');
        $pathParts = pathinfo($csvFile);

        $csv = Reader::createFromPath($csvFile);
        $csv->setHeaderOffset(0);

        $this->outCsv = Writer::createFromPath($pathParts['dirname'].'/'.$pathParts['filename'].'-errors.csv', 'w+');
        $outHeader = $csv->getHeader();
        $outHeader[] = ImportMagReviews::HEADER_FAILURE_REASON;
        $this->outCsv->insertOne($outHeader);

        $indexTypeReview = MagazineIndexType::where('name', '=', 'Review')->first();

        foreach ($csv as $record) {
            $games = $this->getGames($record['Game']);
            if ($games->count() == 0) {
                $this->warn("No game found for '".$record['Game']."'");
                $this->writeOut($record, "No game found");
            } else if ($games->count() > 1) {
                $this->warn($games->count()." games found for '".$record['Game']."'");
                $this->writeOut($record, "More than 1 game found with that name");
            } else {
                $game = $games->first();
                $this->info("Found game ".$game->game_name);

                $magazine = Magazine::where('name', '=', $record['Magazine'])->first();
                if (!$magazine) {
                    $magazine = Magazine::create([
                        'name' => $record['Magazine']
                    ]);
                    $this->info('\tCreated magazine '.$magazine->name);
                } else {
                    $this->info("\tFound magazine ".$magazine->name);
                }

                $issueNumber = str_replace('Issue ', '', $record['Issue']);
                if (is_numeric($issueNumber)) {
                    $issue = $magazine->issues->where('issue', $issueNumber)->first();
                    if (!$issue) {
                        $issue = new MagazineIssue([
                            'issue' => $issueNumber,
                        ]);
                        $magazine->issues()->save($issue);
                        $this->info("\tCreated issue: ".$issueNumber);
                    } else {
                        $this->info("\tFound issue: ".$issue->display_label);
                    }

                    if ($issue->indices()->where('game_id', $game->getKey())->first()) {
                        $this->info("\tReview of '".$game->game_name."' already in database");
                    } else {
                        $index = new MagazineIndex([
                            'game_id'                => $game->getKey(),
                            'magazine_index_type_id' => $indexTypeReview->getKey(),
                            'score'                  => $this->getScore($record['Score or Evaluation']),
                        ]);
                        $issue->indices()->save($index);
                        $this->info("\tCreated index for '".$game->game_name."' in '".$issue->display_label."'");
                    }

                } else {
                    $this->warn("Unhandled issue number '".$record['Issue']."'");
                    $this->writeOut($record, "Unhandled issue number");
                }
            }
        }

        return Command::SUCCESS;
    }

    private function getGames($name)
    {
        $games = Game::where('game_name', '=', $name)->get();
        if ($games->count() < 1) {
            $games = Game::whereHas('releases', function(Builder $query) use ($name) {
                $query->where('name', '=', $name);
            })->get();
        }

        return $games;
    }

    private function getScore($score)
    {
        foreach([
            '/^[0-9]+%?$/',
            '/^[0-9]+\/[0-9]+$/'
        ] as $pattern) {
            if (preg_match($pattern, $score)) {
                return $score;
            }
        }

        $this->warn("Rejected unsupported score '".$score."'");
        return null;
    }

    private function writeOut(array $record, string $failureReason)
    {
        $outRecord = $record;
        $outRecord[ImportMagReviews::HEADER_FAILURE_REASON] = $failureReason;
        $this->outCsv->insertOne($outRecord);
    }
}
