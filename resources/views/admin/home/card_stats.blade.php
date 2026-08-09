{{-- A row of stat tiles, keyed by the labels AdminStatisticsHelper::headlines() uses --}}
@php
    $icons = [
        'Games'       => 'fas fa-gamepad',
        'Releases'    => 'fas fa-database',
        'Screenshots' => 'fas fa-image',
        'Individuals' => 'fas fa-user-tie',
        'Companies'   => 'fas fa-building',
        'Users'       => 'fas fa-user-friends',
    ];
@endphp

<div class="row">
    @foreach ($stats as $label => $count)
        <div class="col">
            @include('admin.home.card_stat', [
                'count' => $count,
                'label' => $label,
                'icon'  => $icons[$label] ?? '',
            ])
        </div>
    @endforeach
</div>
