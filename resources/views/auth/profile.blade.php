@extends('layouts.app')
@section('title', 'Profile')

@section('content')
<h1 class="sr-only">Profile</h1>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark mb-4">
                <div class="card-header">Update profile</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('auth.profile') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="userid" class="col-md-4 col-form-label text-md-right">{{ __('Username') }}</label>

                            <div class="col-md-6">
                                <input id="userid" readonly type="text" class="form-control-plaintext" value="{{ Auth::user()->userid }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', Auth::user()->email) }}" autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <br>

                        <div class="row mb-3">
                            <label for="website" class="col-md-4 col-form-label text-md-right">{{ __('Website') }}</label>

                            <div class="col-md-6">
                                <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website', Auth::user()->user_website) }}" placeholder="e.g. https://example.org/">

                                @error('website')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="facebook" class="col-md-4 col-form-label text-md-right">{{ __('Facebook') }}</label>

                            <div class="col-md-6">
                                <input id="facebook" type="url" class="form-control @error('facebook') is-invalid @enderror" name="facebook" value="{{ old('facebook', Auth::user()->user_fb) }}" placeholder="e.g. https://www.facebook.com/...">

                                @error('facebook')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="twitter" class="col-md-4 col-form-label text-md-right">{{ __('Twitter') }}</label>

                            <div class="col-md-6">
                                <input id="twitter" type="url" class="form-control @error('twitter') is-invalid @enderror" name="twitter" value="{{ old('twitter', Auth::user()->user_twitter) }}" placeholder="e.g. https://twitter.com/...">

                                @error('twitter')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="af" class="col-md-4 col-form-label text-md-right">{{ __('Atari-Forum profile') }}</label>

                            <div class="col-md-6">
                                <input id="af" type="url" class="form-control @error('af') is-invalid @enderror" name="af" value="{{ old('af', Auth::user()->user_af) }}" placeholder="e.g. https://www.atari-forum.com/memberlist.php?mode=viewprofile&u=...">

                                @error('af')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3 mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">Update profile</button>
                            </div>
                        </div>
                    </form>

                    <hr>
                    <form method="POST" action="{{ route('auth.password') }}">
                    @csrf

                        <div class="row mb-3">
                            <label for="password-current" class="col-md-4 col-form-label text-md-right">Current Password</label>

                            <div class="col-md-6">
                                <input id="password-current" type="password" class="form-control @error('password_current') is-invalid @enderror" name="password_current" autocomplete="current-password">

                                @error('password_current')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('New Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm New Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-3 mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">Change password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
