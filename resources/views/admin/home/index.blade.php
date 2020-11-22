@extends('admin.layouts.admin')

@section('content')
    <h1>Administration</h1>

    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Last {{ $users->count() }} users</h2>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Avatar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->userid }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <abbr title="{{ date('F j, Y H:i', $user->join_date) }}">
                                            {{ Carbon\Carbon::createFromTimestamp($user->join_date)->diffForHumans()}}
                                        </abbr>
                                    </td>
                                    <td>
                                        @if ($user->avatar !== null)
                                            <img class="rounded-circle border border-dark" src="{{ $user->avatar }}" height="24" alt="User avatar">
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="#">View all users</a>
                </div>
            </div>
        </div>
        <div class="col">

        </div>
    </div>
@endsection
