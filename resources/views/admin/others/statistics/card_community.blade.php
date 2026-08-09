<h3 id="community" class="h4 mt-4 mb-3">Community</h3>

<div class="row">
    <div class="col-12 col-xl-4">
        <x-admin.chart id="signups-by-year" title="Sign-ups per year"
            :labels="$userSignupsByYear['labels']" :data="$userSignupsByYear['data']" label="Users" />
    </div>
    <div class="col-12 col-xl-4">
        <x-admin.chart id="comments-by-year" title="Comments per year"
            :labels="$commentsByYear['labels']" :data="$commentsByYear['data']" label="Comments" />
    </div>
    <div class="col-12 col-xl-4">
        <x-admin.chart id="vote-distribution" title="Game votes"
            :labels="$voteDistribution['labels']" :data="$voteDistribution['data']" label="Votes" />
    </div>
</div>
