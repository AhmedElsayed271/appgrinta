{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.competition.create')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.competitions.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                        <div class="form-group">
                            <label for="{{$locale}}_name">@lang('site.'.$locale.'.name')</label>
                            <input type="text" name="{{$locale}}[name]" class="form-control @error($locale.'.name') is-invalid @enderror" id="{{$locale}}_name" value="{{old($locale.'.name')}}" >
                            @error($locale.'.name')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="form-group row">
                        <label class="col-xl-3 col-lg-3 col-form-label">{{__('site.global.image')}}</label>
                        <div class="col-lg-6 col-xl-6">
                            <div class="image-input image-input-outline image-input-circle" id="kt_image_3">
                                <div class="image-input-wrapper" style="background-image: url({{asset('storage/uploads/competition_images/default.svg')}});background-position: center"></div>

                                <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
                                    <i class="fa fa-pen icon-sm text-muted"></i>
                                    <input type="file" name="image" accept=".png, .jpg, .jpeg, .svg"/>
                                    <input type="hidden" name="has_image"/>
                                </label>

                                <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="Cancel avatar">
                                    <i class="ki ki-bold-close icon-xs text-muted"></i>
                                </span>
                            </div>
                        </div>
                    </div>
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
                        <label for="season" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.season')}}</label>
                        <div class="col-lg-9 col-md-9 col-sm-12">
                            <div class="input-group date" id="season" >
                                <input type="text" name="season" id="season" class="form-control  @error('season') is-invalid @enderror"  value="{{old('season')}}"/>
                                @error('season')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.has_children')}}</label>
                        <div class="col-lg-9 col-md-9 col-sm-12">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_children" id="has_children_0" value="0" checked>
                                <label class="form-check-label" for="has_children_0">
                                    {{__('site.global.no')}}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_children" id="has_children_1" value="1" >
                                <label class="form-check-label" for="has_children_1">
                                    {{__('site.global.yes')}}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.has_parent')}}</label>
                        <div class="col-lg-9 col-md-9 col-sm-12">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_parent" id="has_parent_0" value="0" checked>
                                <label class="form-check-label" for="has_parent_0">
                                    {{__('site.global.no')}}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_parent" id="has_parent_1" value="1" >
                                <label class="form-check-label" for="has_parent_1">
                                    {{__('site.global.yes')}}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                            <label for="parent_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.parent')}}</label>
                            <div class=" col-lg-4 col-md-9 col-sm-12">
                                <select class="form-control select2" id="parent_id" name="parent_id">
                                    <option value=""></option>
                                </select>
                                @error('parent_id')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    <div class="separator separator-dashed my-8"></div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.add')}}</button>
                    <a href="{{route('dashboard.countries.index')}}" class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
                </div>
            </form>
        </div>

    </div>
@endsection

@section('scripts')
    <script !src="">
        var avatar3 = new KTImageInput('kt_image_3');
        // Class definition
        var KTSelect2 = function() {
            // Private functions
            var demos = function() {
                // basic
                $('#country_id,#parent_id').select2({
                    placeholder: "Select a state"
                });
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
            $('#parent_id').closest('.form-group').hide()
            $(document).on('change','[name=has_parent]',function (e){
                var value= e.target.value;
                if (value == 0){
                    $('#parent_id').closest('.form-group').hide()
                }else {
                    $('#parent_id').closest('.form-group').show()
                }
            });
            $(document).on('change','#country_id',function (e){
                if (e.target.value){
                    var competitions='<option value=""></option>';
                    $.ajax({
                        url:'{{route('dashboard.countries.index')}}/'+e.target.value +'?has_children=1',
                        data:{
                            _token:'{{csrf_token()}}'
                        },
                        success:function (data){
                            $.each(data,function (key,value){
                                competitions += `<option value="${value.id}">${value.name}</option>`
                            })
                            $('#parent_id').html(`
                                ${competitions}
                            `);
                        }
                    })
                }
            })
            $(document).on('change','#competition_id',function (e){
                if (e.target.value){
                    var teams='<option value=""></option>';
                    $.ajax({
                        url:'{{route('dashboard.competitions.index')}}/'+e.target.value,
                        data:{
                            _token:'{{csrf_token()}}'
                        },
                        success:function (data){
                            $.each(data,function (key,value){
                                teams += `<option value="${value.id}">${value.name}</option>`
                            })
                            $('#team_id').html(`
                                ${teams}
                            `);
                        }
                    })
                }
            })
            @if(old('country_id'))
            var competitions='<option value=""></option>';
            $.ajax({
                url:'{{route('dashboard.countries.index')}}/'+ {{old('country_id')}},
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        competitions += `<option value="${value.id}" ${value.id == '{{old('competition_id')}}'? 'selected':'' }>${value.name}</option>`
                    })
                    $('#competition_id').html(`
                                ${competitions}
                            `);
                }
            })
            @endif
            @if(old('competition_id'))
            var teams='<option value=""></option>';
            $.ajax({
                url:'{{route('dashboard.competitions.index')}}/'+{{old('competition_id')}},
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        teams += `<option value="${value.id}" ${value.id == '{{old('team')}}'? 'selected':'' }>${value.name}</option>`
                    })
                    $('#team_id').html(`
                                ${teams}
                            `);
                }
            })
            @endif
        })
    </script>
@endsection
