{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.post.create')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.posts.store')}}" enctype="multipart/form-data">
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
                    <div class="form-group">
                        <label for="youtube">{{ __('YouTube Link Id') }}</label>
                        <input type="text" name="youtube_link" class="form-control" id="youtube">
                        @error('youtube_link')
                        <div class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group row">
                        <label class="col-xl-3 col-lg-3 col-form-label">{{__('site.global.image')}}</label>
                        <div class="col-lg-6 col-xl-6">
                            <div class="image-input image-input-outline image-input-circle" id="kt_image_3">
                                <div class="image-input-wrapper" style="background-image: url({{asset('storage/uploads/post_images/default.png')}})"></div>

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
                    <div class="form-group row">
                        <label for="parent_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.post.category')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="parent_id" name="parent_id">
                                <option value=""></option>
                                @foreach($main_Categories as $key=>$value)
                                    <option value="{{$key}}" {{old('parent_id') == $key ? 'selected' : '' }}>{{$value}}</option>
                                @endforeach
                            </select>
                            @error('parent_id')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div id="show_sub_category"></div>

                    <div class="form-group row">
                            <label for="competition_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.competition.name')}}</label>
                            <div class=" col-lg-4 col-md-9 col-sm-12">
                                <select class="form-control select2" id="competition_id" name="competitions[]" multiple>
                                    <option value=""></option>
                                    @foreach($competitions as $key=>$value)
                                        <option value="{{$key}}" {{ in_array($key,old('competitions')??[]) ? 'selected' : '' }}>{{$value}}</option>
                                    @endforeach
                                </select>
                                @error('competition_id')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                    </div>
                    <div class="form-group row">
                            <label for="team_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.team.name')}}</label>
                            <div class=" col-lg-4 col-md-9 col-sm-12">
                                <select class="form-control select2" id="team_id" name="teams[]" multiple>
                                    <option value=""></option>
                                    {{-- @foreach($teams as $key=>$value)
                                        <option value="{{$key}}" data-image="{{ $image_path }}" {{ in_array($key,old('teams')??[]) ? 'selected' : '' }}>{{$value}}</option>
                                    @endforeach --}}

                                    {{-- @foreach($teams as $key=>$value)
                                        <option value="{{$value->id}}" {{ in_array($value->id,old('teams')??[]) ? 'selected' : '' }}> <span> {{$value->name}} <img src="{{ $value->image_path }}" width="100" height="100" alt=""> </span> </option>
                                    @endforeach --}}
                                </select>
                                @error('team_id')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    <div class="separator separator-dashed my-8"></div>

                    <div class="checkbox-inline">
                        <label class="checkbox checkbox-success">
                            <input type="checkbox" name="featured"/>
                            <span></span>
                            featured
                        </label>
                    </div>

                </div>



                <div class="card-footer">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.add')}}</button>
                    <a href="{{route('dashboard.posts.index')}}" class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
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
                $('#parent_id,#competition_id').select2({
                    placeholder: "Select a state"
                });

                $('#team_id').select2({
                    placeholder: "Select a state",
                    templateResult: teamImageSelect,
                    templateSelection: teamImageSelect,
                    escapeMarkup: function (m) {
                        return m;
                    }
                });

                function teamImageSelect(state) {
                    var image = $(state.element).data('image') ?? 'https://media.istockphoto.com/vectors/team-emblem-solid-icon-soccer-or-football-club-shield-with-ball-vector-id1213313849?k=20&m=1213313849&s=170667a&w=0&h=SfQWz7pC72I0K1li3dGpdzFSaOYD94i5Jj9UcQfQCwI=';
                    return $(`<span> ${state.text} <img src="${image}" width="100" height="100" alt=""> </span>`);
                }
            }
            // Public functions
            return {
                init: function() {
                    demos();
                }
            };
        }();

        // Class definition

        {{--var KTCkeditor = function () {--}}
        {{--    // Private functions--}}
        {{--    var demos = function () {--}}
        {{--        @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)--}}
        {{--        ClassicEditor--}}
        {{--            .create(--}}
        {{--                document.querySelector( '#{{$locale}}_description' ), {--}}
        {{--                    toolbar : [--}}
        {{--                        { name: 'document', items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },--}}
        {{--                        { name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },--}}
        {{--                        { name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },--}}
        {{--                        { name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },--}}
        {{--                        '/',--}}
        {{--                        { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat' ] },--}}
        {{--                        { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language' ] },--}}
        {{--                        { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },--}}
        {{--                        { name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },--}}
        {{--                        '/',--}}
        {{--                        { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },--}}
        {{--                        { name: 'colors', items: [ 'TextColor', 'BGColor' ] },--}}
        {{--                        { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },--}}
        {{--                        { name: 'about', items: [ 'About' ] }--}}
        {{--                    ]--}}
        {{--                })--}}
        {{--            .then( editor => {--}}
        {{--                console.log( editor );--}}
        {{--            } )--}}
        {{--            .catch( error => {--}}
        {{--                console.error( error );--}}
        {{--            } );--}}
        {{--        @endforeach--}}
        {{--    }--}}

        {{--    return {--}}
        {{--        // public functions--}}
        {{--        init: function() {--}}
        {{--            demos();--}}
        {{--        }--}}
        {{--    };--}}
        {{--}();--}}


        // Class definition

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
            KTSelect2.init();
            KTSummernoteDemo.init();
        });


        function deleteFile(src) {
            $.ajax({
                data: {src : src},
                type: "POST",
                url: "{{route('dashboard.ckeditor.delete',['_token' => csrf_token() ])}}",
                cache: false,
                success: function(resp) {
                    console.log(resp);
                }
            });
        }
        function sendFile(file,that) {
            data = new FormData();
            data.append("upload", file);
            $.ajax({
                data: data,
                type: "POST",
                url: "{{route('dashboard.ckeditor.upload',['_token' => csrf_token() ])}}",
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {

                    var img = document.createElement('img');
                    img.src = JSON.parse(response).default;
                    img.style.width='25%';
                    that.summernote('insertNode', img);
                    //editor.insertImage(welEditable, image);
                }
            });
        }
        $(function (){

            $(document).on('change','#competition_id',function (e){
                teams(e.target.value);
            })

        function teams(competition){
            // var teams='<option value=""></option>';
            var teams = $('#team_id').html();
            console.log(competition);
            $.ajax({
                url:'{{route('dashboard.competitions.index')}}/'+competition,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        teams += `<option value="${value.id}" data-image="${value.image_path}">${value.name}</option>`
                    })
                    $('#team_id').html(`
                                ${teams}
                            `);
                }
            })
        }

            $('body').on('change','#parent_id',function (e){
                var sub_category='';
                $.ajax({
                    url:'{{route('dashboard.categories.index')}}/'+e.target.value,
                    data:{
                        _token:'{{csrf_token()}}'
                    },
                    success:function (data){
                        $.each(data,function (key,value){
                            sub_category += `<option value="${value.id}">${value.name}</option>`
                        })
                        $('#show_sub_category').html(`
                            <div class="form-group row">
                                <label for="category_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.post.category')}}</label>
                                <div class=" col-lg-4 col-md-9 col-sm-12">
                                    <select class="form-control select2" id="category_id" name="category_id">
                                        <option value=""></option>
                                        ${sub_category}
                                    </select>
                                </div>
                            </div>
                        `);

                        // Class definition
                        var KTSelect2_2 = function() {
                            // Private functions
                            var demos = function() {
                                // basic
                                $('#category_id').select2({
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
                            KTSelect2_2.init();
                        });
                    }
                })
            })
        })
    </script>
@endsection
