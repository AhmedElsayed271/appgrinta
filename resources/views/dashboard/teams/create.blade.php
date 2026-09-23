{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.team.create')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.teams.store')}}" enctype="multipart/form-data">
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
                                <div class="image-input-wrapper" style="background-image: url({{asset('storage/uploads/team_images/default.svg')}});background-position: center"></div>

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
{{--                    <div class="form-group row">--}}
{{--                            <label for="country_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.country.name')}}</label>--}}
{{--                            <div class=" col-lg-4 col-md-9 col-sm-12">--}}
{{--                                <select class="form-control select2" id="country_id" name="country_id">--}}
{{--                                    <option value=""></option>--}}
{{--                                    @foreach($countries as $key=>$value)--}}
{{--                                        <option value="{{$key}}" {{old('country_id') == $key ? 'selected' : '' }}>{{$value}}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                                @error('country_id')--}}
{{--                                <div class="invalid-feedback" role="alert">--}}
{{--                                    <strong>{{ $message }}</strong>--}}
{{--                                </div>--}}
{{--                                @enderror--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    <div class="form-group row">--}}
{{--                        <label for="competition_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.name')}}</label>--}}
{{--                        <div class=" col-lg-4 col-md-9 col-sm-12">--}}
{{--                            <select class="form-control select2" id="competition_id" name="competition_id">--}}
{{--                                <option value=""></option>--}}
{{--                            </select>--}}
{{--                            @error('competition_id')--}}
{{--                            <div class="invalid-feedback" role="alert">--}}
{{--                                <strong>{{ $message }}</strong>--}}
{{--                            </div>--}}
{{--                            @enderror--}}
{{--                        </div>--}}
{{--                    </div>--}}
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
                $('#competition_id,#country_id').select2({
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

        {{--$(function (){--}}
        {{--    $('body').on('change','#country_id',function (e){--}}
        {{--        var competitions=`<option value=""></option>`;--}}
        {{--        $.ajax({--}}
        {{--            url:'{{route('dashboard.countries.index')}}/'+e.target.value,--}}
        {{--            data:{--}}
        {{--                _token:'{{csrf_token()}}'--}}
        {{--            },--}}
        {{--            success:function (data){--}}
        {{--                $.each(data,function (key,value){--}}
        {{--                    competitions += `<option value="${value.id}">${value.name}</option>`--}}
        {{--                })--}}
        {{--                $('#competition_id').html(`--}}
        {{--                    ${competitions}--}}
        {{--                `);--}}
        {{--            }--}}
        {{--        })--}}
        {{--    })--}}
        {{--})--}}
    </script>
@endsection
