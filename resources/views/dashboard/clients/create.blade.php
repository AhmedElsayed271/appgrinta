{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.client.create')}}</h3>
            </div>
        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.clients.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group row">
                        <label for="full_name"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.client.full_name')}}</label>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            <input type="text" name="full_name"
                                class="form-control @error('full_name') is-invalid @enderror" id="full_name"
                                value="{{ old('full_name') }}">
                            @error('full_name')
                                <div class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="email" class="col-form-label col-lg-3 col-sm-12">{{__('site.client.email')}}</label>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="password"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.client.password')}}</label>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" id="password">
                            @error('password')
                                <div class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="password_confirmation"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.client.password_confirmation')}}</label>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            <input type="password" name="password_confirmation" class="form-control"
                                id="password_confirmation">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="timezone"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.client.timezone')}}</label>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            @php $timezones = \DateTimeZone::listIdentifiers(); @endphp
                            <select name="timezone" id="timezone"
                                class="form-control @error('timezone') is-invalid @enderror">
                                @foreach($timezones as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone', config('app.timezone')) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="timezone"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.client.timezone')}}</label>
                        <div class="col-lg-6 col-md-9 col-sm-12">
                            @php $timezones = \DateTimeZone::listIdentifiers(); @endphp
                            <select name="timezone" id="timezone"
                                class="form-control @error('timezone') is-invalid @enderror">
                                @foreach($timezones as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone', config('app.timezone')) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></div>
                            @enderror
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>
                </div>
                <div class="card-footer">
                    <button type="submit"
                        class="btn btn-success font-weight-bold mr-2">{{__('site.operation.add')}}</button>
                    <a href="{{route('dashboard.clients.index')}}"
                        class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
                </div>
            </form>
        </div>

    </div>
@endsection