{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.match.edit')}}
                    {{-- <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.matches.update', $match->id)}}"
                enctype="multipart/form-data">
                @csrf
                {{method_field('PUT')}}
                <div class="card-body">
                    <div class="form-group row">
                        <label for="country_id"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.country.name')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="country_id" name="country_id">
                                <option value=""></option>
                                @foreach($countries as $key => $value)
                                    <option value="{{$key}}" {{$match->competition->country_id == $key ? 'selected' : '' }}>
                                        {{$value}}</option>
                                @endforeach
                            </select>
                            @error('country_id')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="competition_id"
                            class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.name')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="competition_id" name="competition_id">
                                <option value=""></option>
                            </select>
                            @error('competition_id')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label for="team1_id"
                                    class="col-form-label col-lg-3 col-sm-12">{{__('site.team.one')}}</label>
                                <div class=" col-lg-9 col-md-9 col-sm-12">
                                    <select class="form-control select2" id="team1_id" name="team1_id">
                                        <option value=""></option>
                                    </select>
                                    @error('team1_id')
                                        <div class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label for="team2_id"
                                    class="col-form-label col-lg-3 col-sm-12">{{__('site.team.two')}}</label>
                                <div class=" col-lg-9 col-md-9 col-sm-12">
                                    <select class="form-control select2" id="team2_id" name="team2_id">
                                        <option value=""></option>
                                    </select>
                                    @error('team2_id')
                                        <div class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label text-right col-lg-3 col-sm-12">{{__('site.match.match_date')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="match_date" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" name="match_date"
                                    value="{{Carbon\Carbon::parse($match->match_date)->format('d.m.Y H:i')}}"
                                    placeholder="Select date & time" data-target="#match_date" />
                                <div class="input-group-append" data-target="#match_date" data-toggle="datetimepicker">
                                    <span class="input-group-text">
                                        <i class="ki ki-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        @error('match_date')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>
                    <div class="form-group row">
                        <label for="week" class="col-form-label col-lg-3 col-sm-12">{{__('site.match.week')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <input type="text" name="week" class="form-control @error('week') is-invalid @enderror"
                                id="week" value="{{$match->week}}">
                            @error('week')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="status" class="col-form-label col-lg-3 col-sm-12">{{__('site.match.status')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="status" name="status">
                                <option value=""></option>
                                @foreach(['pending', 'soon', 'live', 'first half', 'second half', 'extra time', 'extra first half', 'extra last half', 'penalty kicks', 'end the match'] as $key => $value)
                                    <option value="{{$value}}" {{$match->status == $value ? 'selected' : '' }}>{{$value}}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                        <div class="form-group">
                            <label for="{{$locale}}_location">@lang('site.' . $locale . '.location')</label>
                            <input type="text" name="{{$locale}}[location]"
                                class="form-control @error($locale . '.location') is-invalid @enderror" id="{{$locale}}_location"
                                value="{{ optional($match->translate($locale))->location ?? '' }}">
                            @error($locale . '.location')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="{{$locale}}_channel">@lang('site.' . $locale . '.channel')</label>
                            <input type="text" name="{{$locale}}[channel]"
                                class="form-control @error($locale . '.channel') is-invalid @enderror" id="{{$locale}}_channel"
                                value="{{ optional($match->translate($locale))->channel ?? '' }}">
                            @error($locale . '.channel')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="{{$locale}}_group">@lang('site.' . $locale . '.group')</label>
                            <input type="text" name="{{$locale}}[group]"
                                class="form-control @error($locale . '.group') is-invalid @enderror" id="{{$locale}}_group"
                                value="{{ optional($match->translate($locale))->group ?? '' }}">
                            @error($locale . '.group')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="separator separator-dashed my-8"></div>
                </div>
                <div class="card-footer">
                    <button type="submit"
                        class="btn btn-success font-weight-bold mr-2">{{__('site.operation.edit')}}</button>
                    <a href="{{route('dashboard.matches.index')}}"
                        class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
                </div>
            </form>
        </div>

    </div>
@endsection

@section('scripts')
    <script !src="">
        var avatar3 = new KTImageInput('kt_image_3');
        // Demo 1
        $('#match_date').datetimepicker({
            locale: 'en'
        });
        // Class definition
        var KTSelect2 = function () {
            // Private functions
            var demos = function () {
                // basic
                $('#competition_id,#country_id,#status').select2({
                    placeholder: "Select a state"
                });
                $('#team1_id,#team2_id').select2({
                    placeholder: "Select a state",
                    templateResult: teamImageSelect,
                    templateSelection: teamImageSelect,
                    escapeMarkup: function (m) {
                        return m;
                    }
                });

                function teamImageSelect(state) {
                    var image = $(state.element).data('image') ?? 'https://media.istockphoto.com/vectors/team-emblem-solid-icon-soccer-or-football-club-shield-with-ball-vector-id1213313849?k=20&m=1213313849&s=170667a&w=0&h=SfQWz7pC72I0K1li3dGpdzFSaOYD94i5Jj9UcQfQCwI=';
                    return $(`<span> <img src="${image}" width="100" height="100" alt=""> ${state.text} </span>`);
                }
            }
            // Public functions
            return {
                init: function () {
                    demos();
                }
            };
        }();
        // Initialization
        jQuery(document).ready(function () {
            KTSelect2.init();
        });

        function ajax_competitions(country_id) {
            if (country_id) {
                var competitions = `<option value=""></option>`;
                $.ajax({
                    url: '{{route('dashboard.countries.index')}}/' + country_id,
                    data: {
                        _token: '{{csrf_token()}}'
                    },
                    success: function (data) {
                        var competition = '{{$match->competition_id}}'
                        $.each(data, function (key, value) {
                            competitions += `<option value="${value.id}" ${competition == value.id ? 'selected' : ''}>${value.name}</option>`
                        })
                        $('#competition_id').html(`
                                ${competitions}
                            `);
                    }
                })
            }
        }
        function teams(competition) {
            var teams = '<option value=""></option>';
            $.ajax({
                url: '{{route('dashboard.competitions.index')}}/' + competition,
                data: {
                    _token: '{{csrf_token()}}'
                },
                success: function (data) {
                    $.each(data, function (key, value) {
                        teams += `<option value="${value.id}" data-image="${value.image_path}">${value.name}</option>`
                    })
                    $('#team1_id,#team2_id').html(`
                                    ${teams}
                                `);
                    $('#team1_id option:eq({{$match->team1_id}})').attr('selected', 'selected')
                    $('#team2_id option:eq({{$match->team2_id}})').attr('selected', 'selected')
                    $('#team1_id').val('{{$match->team1_id}}').change();
                    $('#team2_id').val('{{$match->team2_id}}').change();
                }
            })
        }
        $(function () {
            ajax_competitions($('#country_id').val())
            teams('{{$match->competition_id}}')
            $('body').on('change', '#country_id', function (e) {
                ajax_competitions($('#country_id').val())
            })
            $(document).on('change', '#competition_id', function (e) {
                if (e.target.value) {
                    teams(e.target.value)
                }
            })
        })
    </script>
@endsection