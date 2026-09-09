<tr>
    <td class="text-muted">{{ $index }}</td>
    <td>
        <a href="" class="sndh-play" data-sndh-url="{{ asset('storage/sndh/' . $sndh->sndh_archive_id . '/sndh_lf/' . $sndh->id . '.sndh') }}" data-sndh-subtune="{{ $subtune }}">
        <i class="fa fa-circle-play fa-fw" data-play-icon aria-hidden="true"></i></a>
    </td>
    <td>
        {{ $sndh->title }} @if ($sndh->subtunes > 1)<small class="text-muted">({{ $subtune }}/{{ $sndh->subtunes }})</small>@endif
    </td>
    <td class="text-nowrap text-end pe-3">
        <small class="text-muted ms-3">{{ $sndh->composer }}</small>
    </td>
</tr>
