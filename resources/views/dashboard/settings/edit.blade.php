{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')

    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.setting.edit')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.settings.update',$setting->id)}}" enctype="multipart/form-data">
                @csrf
                {{method_field('PUT')}}
                <div class="card-body">
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                        <div class="form-group">
                            <label for="{{$locale}}_description">{{__('site.'.$locale.'.description')}}</label>
                            <textarea name="{{$locale}}[description]" id="{{$locale}}_description">
                                {{$setting->translate($locale)->description}}
                            </textarea>
                            @error($locale.'.description')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="{{$locale}}_privacy">{{__('site.'.$locale.'.privacy')}}</label>
                            <textarea name="{{$locale}}[privacy]" id="{{$locale}}_privacy">
                                {{$setting->translate($locale)->privacy}}
                            </textarea>
                            @error($locale.'.privacy')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="form-group">
                        <label for="name">{{__('site.setting.name')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="name" >
                                <input type="text" name="name" id="name" class="form-control  @error('name') is-invalid @enderror" value="{{$setting->name}}"  />
                                @error('name')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="firebase_key">{{__('site.setting.firebase_key')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="firebase_key" >
                                <input type="text" name="firebase_key" id="firebase_key" class="form-control  @error('firebase_key') is-invalid @enderror" value="{{$setting->firebase_key}}"  />
                                @error('firebase_key')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="football_key">{{__('site.setting.football_key')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="football_key" >
                                <input type="text" name="football_key" id="football_key" class="form-control  @error('football_key') is-invalid @enderror" value="{{$setting->football_key}}"  />
                                @error('football_key')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-xl-3 col-lg-3 col-form-label">{{__('site.global.image')}}</label>
                        <div class="col-lg-6 col-xl-6">
                            <div class="image-input image-input-outline image-input-circle" id="kt_image_3">
                                <div class="image-input-wrapper" style="background-image: url({{$setting->logo_path ?? asset('storage/uploads/setting_logos/default.png')}})"></div>
                                <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
                                    <i class="fa fa-pen icon-sm text-muted"></i>
                                    <input type="file" name="image" accept=".png, .jpg, .jpeg"/>
                                    <input type="hidden" name="has_image"/>
                                </label>
                                <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="Cancel avatar">
                            <i class="ki ki-bold-close icon-xs text-muted"></i>
                        </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Title Icon</label>
                        <div></div>
                        <div class="custom-file">
                            <input type="file" name="title_icon" class="custom-file-input" id="title_icon" accept=".ico" />
                            <label class="custom-file-label" for="title_icon">Choose file</label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-xl-3 col-lg-3 col-form-label">{{__('site.global.loader_image')}}</label>
                        <div class="col-lg-6 col-xl-6">
                            <div class="image-input image-input-outline image-input-circle" id="kt_image_3">
                                <div class="image-input-wrapper" style="background-image: url({{$setting->loader_image_path ?? asset('storage/uploads/setting_logos/default.png')}})"></div>
                                <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
                                    <i class="fa fa-pen icon-sm text-muted"></i>
                                    <input type="file" name="loader_image" accept=".png, .jpg, .jpeg"/>
                                    <input type="hidden" name="has_image"/>
                                </label>
                                <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="Cancel avatar">
                            <i class="ki ki-bold-close icon-xs text-muted"></i>
                        </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.edit')}}</button>
                    <a href="{{route('dashboard.settings.index')}}" class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
                </div>
            <form method="post" action="{{route('dashboard.settings.update',$setting->id)}}" enctype="multipart/form-data">

                <div class="card-footer">
                    <div class="form-group row">
                        <label class="col-form-label" for="leagues">Leagues: </label>
                        <div class="col-lg-4">
                            <div class="input-group" >
                                <input type="text" class="form-control" name="leagues" placeholder="" value="{{ $setting->leagues }}" />

                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label" for="season">season: </label>
                        <div class="col-lg-4">
                            <div class="input-group" >
                                <input type="text" class="form-control" name="season" id="season" placeholder="" value="{{ $setting->season }}"/>

                            </div>
                        </div>
                    </div>
                    <!--<div class="form-group row">-->
                    <!--    <label class="col-form-label" for="apikey">Football-Api_Key: </label>-->
                    <!--    <div class="col-lg-4">-->
                    <!--        <div class="input-group" >-->
                    <!--            <input type="text" class="form-control" name="season" id="apikey" placeholder="" value="{{ $setting->apikey }}"/>-->

                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    <input type="submit" value="Update Data" class="btn btn-light-success font-weight-bold">

                </div>
                </form>
        </div>

    </div>
@endsection

@section('scripts')
    <script src="{{asset('js/summernote-ext-rtl.js')}}"></script>
    <script !src="">
        var KTSummernoteDemo = function () {
            // Private functions
            var demos = function () {
                @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                $('#{{$locale}}_description,#{{$locale}}_privacy').summernote({
                    height: 150,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph','style']],
                        ['insert',['ltr','rtl']],
                        ['insert', ['link','picture', 'video', 'hr']],
                        ['view', ['fullscreen', 'codeview']]
                    ],
                    styleTags: [
                        'p',
                        { title: 'Blockquote', tag: 'blockquote', className: 'blockquote', value: 'blockquote' },
                        'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
                    ],
                    popover: {
                        image: [],
                        link: [],
                        air: []
                    },
                    callbacks:{
                        onImageUpload: function(files, editor, welEditable) {
                            sendFile(files[0],$('#{{$locale}}_description'));
                        },
                        onMediaDelete:function(target) {
                            // alert(target[0].src)
                            deleteFile(target[0].src);
                        }
                    }
                });
                @endforeach
            }

            return {
                // public functions
                init: function() {
                    demos();
                }
            };
        }();


        // Initialization
        jQuery(document).ready(function() {
            KTSummernoteDemo.init();
        });
    </script>
@endsection
