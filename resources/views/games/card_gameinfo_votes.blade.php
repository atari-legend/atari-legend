<div class="row p-2 g-0">
    <div class="col-4 text-muted">Score</div>
    <div class="col-8">
        <div>
            <span class="text-audiowide fs-5 align-top">
                @if ($score)
                    {{ number_format($score, 2) }}<span class="text-muted">/5</span>
                @else
                    <span class="text-muted">-/5</span>
                @endif
            </span>
            @auth
                <form action="{{ route('games.vote', $game) }}" class="d-inline" method="POST">
                    @csrf
                    <div class="dropdown float-xxl-end mt-2 mt-xxl-0">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Your rating: @isset($vote) {{ $vote->label }} @else ? @endif
                        </button>
                        <ul class="dropdown-menu">
                            @foreach (\App\Models\GameVote::LABELS as $score => $label)
                                <li>
                                    <button class="dropdown-item {{ (isset($vote) && $vote->score == $score)     ? 'active' : '' }}"
                                        type="submit" name="score" value="{{ $score }}">
                                        <i class="{{ \App\Models\GameVote::ICONS[$score] }} fa-fw text-muted"></i> {{ $label }}
                                        @if (isset($vote) && $vote->score == $score)
                                            <small class="text-muted ps-2">{{ $vote?->updated_at->ago() }}</small>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                            @isset($vote)
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <button class="dropdown-item" type="submit" name="remove" value="remove">
                                        <i class="fas fa-fw fa-times text-danger"></i> Discard my vote
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </form>
            @else
                <a class="text-danger float-end" href="{{ route('login') }}">Please login to vote</a>
            @endauth
        </div>
        <div class="text-muted mt-1">
            @if ($voteCount > 0)
                <small class="text-nowrap">{{ $voteCount }} {{ Str::plural('vote', $voteCount) }}</small>
                {!! GameVoteHelper::getVoteDistributionSvg($game) !!}
            @else
                <small>No votes yet - be the first!</small>
            @endif
        </div>
    </div>
</div>
