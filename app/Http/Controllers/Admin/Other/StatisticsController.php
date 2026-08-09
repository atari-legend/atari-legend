<?php

namespace App\Http\Controllers\Admin\Other;

use App\Helpers\AdminStatisticsHelper;
use App\Http\Controllers\Controller;
use App\View\Components\Admin\Crumb;

class StatisticsController extends Controller
{
    public function index()
    {
        return view('admin.others.statistics.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.statistics.index'), 'Statistics'),
                ],
                'headlines'         => AdminStatisticsHelper::headlines(),
                'counts'            => AdminStatisticsHelper::counts(),
                'coverage'          => AdminStatisticsHelper::coverage(),
                'changesByMonth'    => AdminStatisticsHelper::changesByMonth(),
                'changesByYear'     => AdminStatisticsHelper::changesByYear(),
                'changesBySection'  => AdminStatisticsHelper::changesBySection(),
                'topContributors'   => AdminStatisticsHelper::topContributors(),
                'releasesByYear'    => AdminStatisticsHelper::releasesByYear(),
                'gamesByGenre'      => AdminStatisticsHelper::gamesByGenre(),
                'topPublishers'     => AdminStatisticsHelper::topPublishers(),
                'topDevelopers'     => AdminStatisticsHelper::topDevelopers(),
                'releasesByLicence' => AdminStatisticsHelper::releasesByLicence(),
                'releasesByType'    => AdminStatisticsHelper::releasesByType(),
                'dumpsByFormat'     => AdminStatisticsHelper::dumpsByFormat(),
                'menuDisksByYear'   => AdminStatisticsHelper::menuDisksByYear(),
                'sndhByYear'        => AdminStatisticsHelper::sndhByYear(),
                'contentByYear'     => AdminStatisticsHelper::contentByYear(),
                'userSignupsByYear' => AdminStatisticsHelper::userSignupsByYear(),
                'voteDistribution'  => AdminStatisticsHelper::voteDistribution(),
                'commentsByYear'    => AdminStatisticsHelper::commentsByYear(),
            ]);
    }
}
