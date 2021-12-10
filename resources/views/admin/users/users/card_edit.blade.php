<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">{{ $user->userid }}</h2>

        @if ($user->email_verified_at)
            <small class="text-success"><i class="fas fa-fw fa-check"></i> Email verified
                {{ Carbon\Carbon::make($user->email_verified_at)->diffForHumans() }}</small>
        @else
            <small class="text-warning"><i class="fas fa-fw fa-exclamation-triangle"></i> Email not verified</small>
        @endif

        <div class="row mt-3">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="joined" class="form-label">Joined</label>
                    @if ($user->join_date)
                        <input type="text" readonly class="form-control" id="joined"
                            value="{{ Carbon\Carbon::createFromTimestamp($user->join_date)->toDayDateTimeString() }}">
                    @else
                        <input type="text" readonly class="form-control-plaintext" id="joined" value="-">
                    @endif
                </div>

                <div class="mb-3">
                    <label for="visit" class="form-label">Last visit</label>
                    @if ($user->last_visit)
                        <input type="text" readonly class="form-control" id="visit"
                            value="{{ Carbon\Carbon::createFromTimestamp($user->last_visit)->diffForHumans() }}">
                    @else
                        <input type="text" readonly class="form-control-plaintext" id="visit" value="-">
                    @endif
                </div>
            </div>
            <div class="col-12 col-md-6 text-center">
                <img class="p-1 border border-dark shadow-sm" style="max-height: 9rem"
                    src="{{ $user->avatar ?? asset('images/unknown.jpg') }}" alt="User avatar">
                <form action="{{ route('admin.users.users.delete-avatar', $user) }}" method="post"
                    onsubmit="javascript:return confirm('The avatar will be deleted')">
                    @csrf
                    @method('DELETE')

                    <button class="btn btn-link" type="submit"><i class="fas fa-trash-alt text-danger"></i></button>
                </form>
            </div>
        </div>

        <form action="{{ route('admin.users.users.update', $user) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" required class="form-control @error('email') is-invalid @enderror" name="email"
                            id="email" value="{{ old('email', $user->email) }}">

                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="avatar" class="form-label">Avatar</label>
                        <input type="file" class="form-control @error('avatar') is-invalid @enderror" name="avatar">
                        @if ($user->avatar)
                            <div class="form-text">Select a file to replace the current avatar.</div>
                        @endif

                        @error('avatar')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="permission" class="form-label">Permission</label>
                        <select class="form-select @error('permission') is-invalid @enderror" name="permission"
                            id="permission" required>
                            <option value="{{ App\Models\User::PERMISSION_USER }}" @if (old('permission', $user->permission) === App\Models\User::PERMISSION_USER) selected @endif>
                                User
                            </option>
                            <option value="{{ App\Models\User::PERMISSION_ADMIN }}" @if (old('permission', $user->permission) === App\Models\User::PERMISSION_ADMIN) selected @endif>
                                Admin
                            </option>
                        </select>

                        @error('permission')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check pt-2">
                            <input class="form-check-input @error('active') is-invalid @enderror" name="active"
                                type="checkbox" value="true" @if (old('active', $user->inactive) !== 1) checked @endif id="active">
                            <label class="form-check-label" for="active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="facebook" class="form-label"><i class="fab fa-fw fa-facebook-square"></i>
                            Facebook</label>
                        <input type="url" class="form-control @error('facebook') is-invalid @enderror" name="facebook"
                            id="facebook" value="{{ old('facebook', $user->user_fb) }}">

                        @error('facebook')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="twitter" class="form-label"><i class="fab fa-fw fa-twitter-square"></i>
                            Twitter</label>
                        <input type="url" class="form-control @error('twitter') is-invalid @enderror" name="twitter"
                            id="twitter" value="{{ old('twitter', $user->user_twitter) }}">

                        @error('twitter')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="forum" class="form-label"><i class="fas fa-fw fa-comments"></i> Atari
                            Forum</label>
                        <input type="url" class="form-control @error('forum') is-invalid @enderror" name="forum"
                            id="forum" value="{{ old('forum', $user->user_af) }}">

                        @error('forum')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="website" class="form-label"><i class="fas fa-fw fa-globe"></i> Website</label>
                        <input type="url" class="form-control @error('website') is-invalid @enderror" name="website"
                            id="website" value="{{ old('website', $user->user_website) }}">

                        @error('website')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.users.users.index') }}" class="btn btn-link">Cancel</a>

        </form>
    </div>
</div>
