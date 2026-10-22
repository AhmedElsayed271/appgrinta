{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.notification.match')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.notifications.post_post')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                        <div class="form-group">
                            <label for="{{$locale}}_name">@lang('site.'.$locale.'.name')</label>
                            <div class="input-group">
                                <input type="text" name="{{$locale}}[name]" class="form-control @error($locale.'.name') is-invalid @enderror" id="{{$locale}}_name" value="{{old($locale.'.name')}}" dir="{{ in_array($locale, ['ar','he','fa','ur']) ? 'rtl' : 'ltr' }}" >
                                @if($locale === 'en')
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary btn-translate" data-source="ar" data-target="en" data-field="name">
                                            ترجمة
                                        </button>
                                    </div>
                                @endif
                            </div>
                            @error($locale.'.name')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="{{$locale}}_description">{{__('site.'.$locale.'.description')}}</label>
                            <textarea name="{{$locale}}[description]" class="summernote" id="{{$locale}}_description" dir="{{ in_array($locale, ['ar','he','fa','ur']) ? 'rtl' : 'ltr' }}">
                                {{old($locale.'.description')}}
                            </textarea>
                            @if($locale === 'en')
                                <button type="button" class="btn btn-primary btn-sm mt-2 btn-translate" data-source="ar" data-target="en" data-field="description">
                                    ترجمة
                                </button>
                            @endif
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
                                <input type="file" name="icon" id="icon" class="form-control  @error('icon') is-invalid @enderror" value="{{old('icon')}}"  />
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
                            <label for="post_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.post.name')}}</label>
                            <div class=" col-lg-4 col-md-9 col-sm-12">
                                <select class="form-control select2" id="post_id" name="post_id">
                                    <option value=""></option>
                                    @foreach($posts as $key=>$value)
                                        <option value="{{$key}}" {{old('post_id') == $key ? 'selected' : '' }}>{{$value}}</option>
                                    @endforeach
                                </select>
                                @error('post_id')
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
                $('#post_id').select2({
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
                $('#{{$locale}}_description').next('.note-editor').find('.note-editable')
                    .attr('dir', '{{ in_array($locale, ['ar','he','fa','ur']) ? 'rtl' : 'ltr' }}')
                    .css('text-align', '{{ in_array($locale, ['ar','he','fa','ur']) ? 'right' : 'left' }}');
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

            $('.btn-translate').on('click', function () {
                var source = $(this).data('source');
                var target = $(this).data('target');
                var field  = $(this).data('field');
                var isHtml = field === 'description';
                var $btn   = $(this);

                var text = isHtml
                    ? $('#' + source + '_' + field).summernote('code')
                    : $('#' + source + '_' + field).val();

                if (!text || (isHtml && text === '<p><br></p>')) {
                    alert('اكتب المحتوى بالعربية أولاً');
                    return;
                }

                $btn.prop('disabled', true).text('...');

                $.ajax({
                    url: '{{route('dashboard.translate')}}',
                    type: 'POST',
                    data: {
                        _token: '{{csrf_token()}}',
                        text: text,
                        source: source,
                        target: target
                    },
                    success: function (resp) {
                        if (resp.success) {
                            if (isHtml) {
                                $('#' + target + '_' + field).summernote('code', resp.translation);
                            } else {
                                $('#' + target + '_' + field).val(resp.translation);
                            }
                        } else {
                            alert(resp.message || 'فشلت الترجمة');
                        }
                    },
                    error: function () {
                        alert('حدث خطأ أثناء الترجمة');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('ترجمة');
                    }
                });
            });
        });


     </script>


@endsection
