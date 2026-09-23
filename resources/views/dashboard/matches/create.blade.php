{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.match.create')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.matches.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group row">
                        <label for="country_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.country.name')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="country_id" name="country_id">
                                <option value=""></option>
                                @foreach($countries as $key=>$value)
                                    <option value="{{$key}}" {{old('country_id') == $key ? 'selected' : '' }}>{{$value}}</option>
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
                        <label for="competition_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.name')}}</label>
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
                    <div class="form-group row">
                        <label for="competition_child_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.child')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="competition_child_id" name="competition_child_id">
                                <option value=""></option>
                            </select>
                            @error('competition_child_id')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label for="team1_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.team.one')}}</label>
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
                                <label for="team2_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.team.two')}}</label>
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
                                <input type="text" class="form-control datetimepicker-input" name="match_date" placeholder="Select date & time" data-target="#match_date"/>
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
                            <input type="text" name="week" class="form-control @error('week') is-invalid @enderror" id="week" value="{{old('week')}}" >
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
                                @foreach(['pending','soon','live','first half','second half','extra time','extra first half','extra last half','penalty kicks','end the match'] as $key=>$value)
                                    <option value="{{$value}}" {{old('status') == $value ? 'selected' : '' }}>{{$value}}</option>
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
                            <label for="{{$locale}}_location">@lang('site.'.$locale.'.location')</label>
                            <input type="text" name="{{$locale}}[location]" class="form-control @error($locale.'.location') is-invalid @enderror" id="{{$locale}}_location" value="{{old($locale.'.location')}}" >
                            @error($locale.'.location')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="{{$locale}}_channel">@lang('site.'.$locale.'.channel')</label>
                            <input type="text" name="{{$locale}}[channel]" class="form-control @error($locale.'.channel') is-invalid @enderror" id="{{$locale}}_channel" value="{{old($locale.'.channel')}}" >
                            @error($locale.'.channel')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="{{$locale}}_group">@lang('site.'.$locale.'.group')</label>
                            <input type="text" name="{{$locale}}[group]" class="form-control @error($locale.'.group') is-invalid @enderror" id="{{$locale}}_group" value="{{old($locale.'.group')}}" >
                            @error($locale.'.group')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="separator separator-dashed my-8"></div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.add')}}</button>
                    <a href="{{route('dashboard.matches.index')}}" class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
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
        var KTSelect2 = function() {
            // Private functions
            var demos = function() {
                // basic
                $('#competition_id,#country_id,#status,#competition_child_id').select2({
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
                init: function() {
                    demos();
                }
            };
        }();
        // Initialization
        jQuery(document).ready(function() {
            KTSelect2.init();
        });

        $(function (){
            var country_id = 0;
            $('body').on('change','#country_id',function (e){
                country_id = e.target.value
                competitions(e.target.value)
            })
            $(document).on('change','#competition_id',function (e){
                if (e.target.value){
                    competitions(country_id,e.target.value,'#competition_child_id')
                    teams(e.target.value)
                }
            })
            $(document).on('change','#competition_child_id',function (e){
                if (e.target.value){
                    teams(e.target.value)
                }
            })
            @if(old('country_id'))
            competitions('{{old('country_id')}}')
            @endif
            @if(old('competition_id'))
            teams('{{old('competition_id')}}')
            @endif
        })

        function competitions(country,parent=null,select='#competition_id'){
            var competitions=`<option value=""></option>`;
            $.ajax({
                url:'{{route('dashboard.countries.index')}}/'+country,
                data:{
                    _token:'{{csrf_token()}}',
                    'query[parent_id]':parent
                },
                success:function (data){
                    $.each(data,function (key,value){
                        competitions += `<option value="${value.id}">${value.name}</option>`
                    })
                    $(select).html(`
                            ${competitions}
                        `);
                }
            })
        }
        function teams(competition){
            var teams='<option value=""></option>';
            $.ajax({
                url:'{{route('dashboard.competitions.index')}}/'+competition,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        teams += `<option value="${value.id}" data-image="${value.image_path}">${value.name}</option>`
                    })
                    $('#team1_id,#team2_id').html(`
                                ${teams}
                            `);
                }
            })
        }
    </script>
@endsection
