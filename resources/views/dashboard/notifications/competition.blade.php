{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.notification.competition')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.notifications.competition_post')}}" enctype="multipart/form-data">
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
                        <div class="form-group">
                            <label for="{{$locale}}_description">{{__('site.'.$locale.'.description')}}</label>
                            <textarea name="{{$locale}}[description]" class="summernote" id="{{$locale}}_description">
                                {{old($locale.'.description')}}
                            </textarea>
                            @error($locale.'.description')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    @endforeach
                    {{-- <div class="form-group row">
                        <label for="icon" class="col-form-label col-lg-3 col-sm-12">{{__('site.notification.icon')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="icon" >
                                <input type="text" name="icon" id="icon" class="form-control  @error('icon') is-invalid @enderror" value="{{old('icon')}}"  />
                                @error('icon')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div> --}}
                    <div class="form-group row">
                        <label for="image" class="col-form-label col-lg-3 col-sm-12">{{__('site.notification.image')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="image" >
                                <input type="file" name="image" id="image" class="form-control  @error('image') is-invalid @enderror" value="{{old('image')}}"  />
                                @error('image')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
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
                            <label for="screen" class="col-form-label col-lg-3 col-sm-12">{{__('screen')}}</label>
                            <div class=" col-lg-4 col-md-9 col-sm-12">
                                <select class="form-control select2" id="screen" name="screen">
                                    <option value=""></option>
                                    @foreach(['Results','Matches','Standing','Top Scorers','News'] as $key=>$value)
                                        <option value="{{$key}}" {{old('screen') == ($key) ? 'selected' : '' }}>{{$value}}</option>
                                    @endforeach
                                </select>
                                @error('screen')
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
                </div>
            </form>
        </div>

    </div>
@endsection

@section('scripts')
<script src="{{asset('js/summernote-ext-rtl.js')}}"></script>

    <script !src="">

        var avatar3 = new KTImageInput('kt_image_3');

        // Class definition
        var KTSelect2 = function() {
            // Private functions
            var demos = function() {
                // basic
                $('#match_id,#competition_id,#country_id,#screen').select2({
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

        var KTSummernoteDemo = function () {
            // Private functions
            var demos = function () {
                @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                $('#{{$locale}}_description').summernote({
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
            KTSummernoteDemo.init()
            KTSelect2.init();
        });
        $(function (){
            $(document).on('change','#country_id',function (e){
                competitions(e.target.value)
            })
        })

        function competitions(country){
            var competitions=`<option value=""></option>`;
            $.ajax({
                url:'{{route('dashboard.countries.index')}}/'+country,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        competitions += `<option value="${value.id}">${value.name}</option>`
                    })
                    $('#competition_id').html(`
                            ${competitions}
                        `);
                }
            })
        }
    </script>
@endsection
