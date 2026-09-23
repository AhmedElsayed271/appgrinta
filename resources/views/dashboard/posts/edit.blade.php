{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.post.edit')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.posts.update',$post->id)}}" enctype="multipart/form-data">
                @csrf
                {{method_field('PUT')}}
                <div class="card-body">
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                        <div class="form-group">
                            <label for="{{$locale}}_name">@lang('site.'.$locale.'.name')</label>
                            <input type="text" name="{{$locale}}[name]" class="form-control @error($locale.'.name') is-invalid @enderror" id="{{$locale}}_name" value="{{$post->translate($locale)->name}}" >
                            @error($locale.'.name')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="{{$locale}}_description">{{__('site.'.$locale.'.description')}}</label>
                            <textarea name="{{$locale}}[description]" id="{{$locale}}_description">
                                {{$post->translate($locale)->description}}
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
                        <input type="text" name="youtube_link" class="form-control" value="{{ $post->youtube_link }}" id="youtube">
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
                                <div class="image-input-wrapper" style="background-image: url({{$post->image_path}})"></div>

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
                        <label for="parent_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.post.parent_id')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="parent_id" name="parent_id">
                                <option value=""></option>
                                @foreach($main_Categories as $key=>$value)
                                    <option value="{{$key}}" {{ (($post->category->parent_id != null) && ($post->category->parent_id == $key)||($post->category_id == $key))  ? 'selected' : '' }}>{{$value}}</option>
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
                                    <option value="{{$key}}" {{ in_array($key,$post_competitions) ? 'selected' : '' }}>{{$value}}</option>
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
                                    <option value="{{$key}}" {{ in_array($key,$post_teams) ? 'selected' : '' }}>{{$value}}</option>
                                @endforeach --}}
                            </select>
                            @error('team_id')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="user_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.post.author')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control" id="user_id" name="user_id">
                                <option value=""></option>
                                @foreach(\App\Models\User::all() as $user)
                                    <option value="{{ $user->id }}" {{ $user->id == $post->user_id ? 'selected' : '' }}>{{ $user->first_name .' '. $user->last_name }}</option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="separator separator-dashed my-8"></div>

                    <div class="checkbox-inline">
                        <label class="checkbox checkbox-success">
                            <input type="checkbox" name="featured" @if ($post->featured == 1) checked @endif>
                            <span></span>
                            featured
                        </label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.edit')}}</button>
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
        {{--var KTCkeditor = function () {--}}
        {{--    // Private functions--}}
        {{--    var demos = function () {--}}
        {{--        @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)--}}
        {{--        ClassicEditor--}}
        {{--            .create( document.querySelector( '#{{$locale}}_description' ) )--}}
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
            KTSummernoteDemo    .init();
        });

        $(document).ready(function () {
            var postTeams = @json($post_teams);
            postTeams.forEach(function (e) {
                team(e);
            });
        });

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
                        // var selected = '';
                        // if (arr.includes(value.id)) {
                        //     selected = 'selected';
                        //     console.log('selected' + value.id);
                        // }
                        // console.log(arr);
                        teams += `<option value="${value.id}"  data-image="${value.image_path}">${value.name}</option>`
                    })
                    $('#team_id').html(`
                                ${teams}
                            `);
                }
            })
        }

        function team(id){
            // var teams='<option value=""></option>';
            // var teams = $('#team_id').html();
            $.ajax({
                url: '{{ url('/') }}' + '/ar/dashboard/post/team/' + id,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    console.log(data);
                    // $.each(data,function (key,value){                       
                        // console.log(arr);
                        document.getElementById('team_id').innerHTML += `<option value="${data.id}" selected data-image="${data.image_path}">${data.name}</option>`;
                    // })
                    // $('#team_id').html(`${teams}`);
                }
            })
        }


        $('body').on('change','#parent_id',function (e){
            ajax(e.target.value , 0)
        })
        var parent_id = $('#parent_id').val() ?? '';
        var category_id = '{{$category->parent_id ?? ''}}';
        ajax(parent_id , category_id)

        function ajax(parent_id , category_id){
            if (parent_id!==''){
                $.ajax({
                    url:'{{route('dashboard.categories.index')}}/'+parent_id,
                    data:{
                        _token:'{{csrf_token()}}'
                    },
                    success:function (data){
                        var sub_category='';
                        $.each(data,function (key,value){
                            sub_category += `<option value="${value.id}"  ${category_id != '' && category_id==value.id ? 'selected' : ''}>${value.name}</option>`
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
            }
        }
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
    </script>
@endsection
