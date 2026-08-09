<h3 id="catalogue" class="h4 mt-4 mb-3">Catalogue</h3>

<div class="row">
    <div class="col-12 col-xl-8">
        <x-admin.chart id="releases-by-year" title="Releases per year"
            :labels="$releasesByYear['labels']" :data="$releasesByYear['data']" label="Releases"
            :footnote="number_format($releasesByYear['undated']) . ' releases have no date and are not shown.'" />
    </div>
    <div class="col-12 col-xl-4">
        <x-admin.chart id="releases-by-licence" title="Release licences" type="doughnut"
            :labels="$releasesByLicence['labels']" :data="$releasesByLicence['data']" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6">
        <x-admin.chart id="games-by-genre" title="Games per genre"
            :labels="$gamesByGenre['labels']" :data="$gamesByGenre['data']"
            label="Games" horizontal height="700" />
    </div>
    <div class="col-12 col-xl-6">
        <x-admin.chart id="releases-by-type" title="Release types"
            :labels="$releasesByType['labels']" :data="$releasesByType['data']"
            label="Releases" horizontal height="300" />
        <x-admin.chart id="dumps-by-format" title="Dump formats" type="doughnut"
            :labels="$dumpsByFormat['labels']" :data="$dumpsByFormat['data']" height="320" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6">
        <x-admin.chart id="top-publishers" title="Top publishers, by release"
            :labels="$topPublishers['labels']" :data="$topPublishers['data']"
            label="Releases" horizontal height="400" />
    </div>
    <div class="col-12 col-xl-6">
        <x-admin.chart id="top-developers" title="Top developers, by game"
            :labels="$topDevelopers['labels']" :data="$topDevelopers['data']"
            label="Games" horizontal height="400" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6">
        <x-admin.chart id="menu-disks-by-year" title="Menu disks per year"
            :labels="$menuDisksByYear['labels']" :data="$menuDisksByYear['data']" label="Menu disks" />
    </div>
    <div class="col-12 col-xl-6">
        <x-admin.chart id="sndh-by-year" title="Music files per year"
            :labels="$sndhByYear['labels']" :data="$sndhByYear['data']" label="SNDH files" />
    </div>
</div>

<div class="row">
    <div class="col">
        <x-admin.chart id="content-by-year" title="Content published per year"
            :labels="$contentByYear['labels']" :datasets="$contentByYear['datasets']" />
    </div>
</div>
