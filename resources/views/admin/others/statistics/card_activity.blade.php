<h3 id="activity" class="h4 mt-4 mb-3">Activity</h3>

<div class="row">
    <div class="col-12 col-xl-8">
        <x-admin.chart id="changes-by-month" title="Changes per month, last two years"
            :labels="$changesByMonth['labels']" :datasets="$changesByMonth['datasets']" stacked />
    </div>
    <div class="col-12 col-xl-4">
        <x-admin.chart id="changes-by-year" title="Changes per year"
            :labels="$changesByYear['labels']" :data="$changesByYear['data']" label="Changes" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6">
        <x-admin.chart id="changes-by-section" title="Most edited sections"
            :labels="$changesBySection['labels']" :data="$changesBySection['data']"
            label="Changes" horizontal height="400" />
    </div>
    <div class="col-12 col-xl-6">
        <x-admin.chart id="top-contributors" title="Top contributors"
            :labels="$topContributors['labels']" :data="$topContributors['data']"
            label="Changes" horizontal height="400" />
    </div>
</div>
