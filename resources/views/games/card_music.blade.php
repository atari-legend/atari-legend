@if ($sndhs->isNotEmpty())
    <div class="card bg-dark mb-4 card-music">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Music</h2>
        </div>
        <div class="card-body p-2">
            <div class="overflow-scroll" style="max-height: 15rem;">
            <table class="table table-borderless table-sm mb-0 w-100" data-music-player>
                @php($index = 1)
                @foreach($game->sndhs as $sndh)
                    @if ($sndh->subtunes > 1)
                        @for ($i = 0; $i < $sndh->subtunes; $i++)
                            @include('games.music_row', [
                                'index' => $index,
                                'id' => $sndh->id,
                                'sndh_archive_id' => $sndh->sndh_archive_id,
                                'title' => $sndh->title,
                                'composer' => $sndh->composer,
                                'subtune' => $i + 1,
                                'sndh' => $sndh,
                            ])
                            @php($index++)
                        @endfor
                    @else
                        @include('games.music_row', [
                            'index' => $index,
                            'id' => $sndh->id,
                            'sndh_archive_id' => $sndh->sndh_archive_id,
                            'title' => $sndh->title,
                            'composer' => $sndh->composer,
                            'subtune' => 1,
                            'sndh' => $sndh,
                        ])
                        @php($index++)
                    @endif
                    
                @endforeach
            </table>
            </div>
        </div>
        <div class="card-footer text-muted text-center">
            <small>Songs provided by <a href="http://sndh.atari.org">The SNDH Archive</a>.</small>
        </div>
    </div>
@endif
