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
            <form class="form" method="post" action="{{route('dashboard.notifications.match_post')}}" enctype="multipart/form-data">
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
                        <label class="col-form-label col-lg-3 col-sm-12">{{__('site.match.name')}}</label>
                        <div class="col-lg-9 col-md-9 col-sm-12">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select id="match_status_filter" class="form-control">
                                        <option value="all">كل الماتشات</option>
                                        <option value="live">جارية الآن</option>
                                        <option value="upcoming">لم تبدأ بعد</option>
                                        <option value="finished">انتهت</option>
                                        <option value="postponed">مؤجلة / ملغاة</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select id="match_date_filter" class="form-control">
                                        <option value="">كل الأوقات</option>
                                        <option value="today">اليوم</option>
                                        <option value="yesterday">أمس</option>
                                        <option value="last7">آخر 7 أيام</option>
                                        <option value="last30">آخر 30 يوم</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <select id="match_competition_filter" class="form-control select2">
                                        <option value="">كل البطولات</option>
                                        @foreach($competitions as $comp)
                                            <option value="{{$comp->id}}">{{ $comp->translate(app()->getLocale())?->name ?? $comp->translate('en')?->name ?? $comp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="date" id="match_from_date" class="form-control" placeholder="من تاريخ">
                                </div>
                                <div class="col-md-6">
                                    <input type="date" id="match_to_date" class="form-control" placeholder="إلى تاريخ">
                                </div>
                            </div>
                            <select class="form-control select2" id="match_id" name="match_id">
                                    <option value=""></option>
                                    @foreach($matches as $value)
                                        @php
                                            $t1 = $value->team1?->translate(app()->getLocale())?->name ?? $value->team1?->translate('en')?->name ?? '';
                                            $t2 = $value->team2?->translate(app()->getLocale())?->name ?? $value->team2?->translate('en')?->name ?? '';
                                            $comp = $value->competition?->translate(app()->getLocale())?->name ?? $value->competition?->translate('en')?->name ?? '';
                                            $dt = $value->match_date ? \Carbon\Carbon::parse($value->match_date)->format('Y-m-d H:i') : '';
                                        @endphp
                                        <option value="{{$value->id}}" data-status="{{$value->status}}" data-comp="{{$value->competition_id}}" data-date="{{ $value->match_date ? $value->match_date->format('Y-m-d') : '' }}" data-datetime="{{$dt}}">
                                            {{$t1}} vs {{$t2}}@if($comp) - {{$comp}}@endif @if($dt) [{{$dt}}]@endif ({{$value->status}})
                                        </option>
                                    @endforeach
                                </select>
                            @error('match_id')
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

            function getStatusBadge(status) {
                var map = {
                    'NS': {text: 'لم تبدأ', color: '#3699FF'},
                    'TBD': {text: 'لم تحدد', color: '#8950FC'},
                    '1H': {text: 'الشوط الأول', color: '#F64E60'},
                    'HT': {text: 'استراحة', color: '#FFA800'},
                    '2H': {text: 'الشوط الثاني', color: '#F64E60'},
                    'ET': {text: 'وقت إضافي', color: '#F64E60'},
                    'P': {text: 'ركلات ترجيح', color: '#F64E60'},
                    'PEN': {text: 'ركلات ترجيح', color: '#F64E60'},
                    'FT': {text: 'انتهت', color: '#1BC5BD'},
                    'AET': {text: 'انتهت بعد إضافي', color: '#1BC5BD'},
                    'PST': {text: 'مؤجلة', color: '#B5B5C3'},
                    'CANC': {text: 'ملغاة', color: '#B5B5C3'},
                    'SUSP': {text: 'موقوفة', color: '#B5B5C3'},
                    'ABD': {text: 'ألغيت', color: '#B5B5C3'},
                    'AWD': {text: 'فوز بالانسحاب', color: '#B5B5C3'},
                    'WO': {text: 'انسحاب', color: '#B5B5C3'},
                };
                var s = map[status] || {text: status, color: '#7E8299'};
                return "";
                return '<span style="display:inline-block;padding:0 6px;border-radius:4px;font-size:10px;color:#fff;background:' + s.color + ';">' + s.text + '</span>';
            }

            // Class definition
            var KTSelect2 = function() {

            function matchTemplate(data) {
                if (data.loading) {
                    return data.text;
                }
                if (!data.id) {
                    return data.text;
                }

                var details = [];
                if (data.competition) {
                    details.push(data.competition);
                }
                if (data.match_date) {
                    details.push(data.match_date);
                }
                if (data.group) {
                    details.push("الجولة: " + data.group);
                }
                if (data.week !== undefined && data.week !== null && data.week !== '') {
                    details.push("الأسبوع: " + data.week);
                }
                if (data.score) {
                    details.push("<strong>النتيجة: " + data.score + "</strong>");
                }

                var badge = getStatusBadge(data.status);
                var $container = $(
                    '<div>' +
                        '<div style="display:flex;align-items:center;gap:6px;font-weight:600;font-size:13px;">' + data.team1 + ' vs ' + data.team2 + ' ' + badge + '</div>' +
                        '<div style="font-size:11px;color:#7E8299;margin-top:2px;">' + details.join(' | ') + '</div>' +
                    '</div>'
                );
              
                return $container;
            }

            // Private functions
            var demos = function() {
                function saveMatchFilters() {
                    try {
                        localStorage.setItem('notif_match_filters', JSON.stringify({
                            status: $('#match_status_filter').val(),
                            date_filter: $('#match_date_filter').val(),
                            competition_id: $('#match_competition_filter').val(),
                            from: $('#match_from_date').val(),
                            to: $('#match_to_date').val()
                        }));
                    } catch (e) {}
                }

                function loadMatchFilters() {
                    try {
                        var saved = localStorage.getItem('notif_match_filters');
                        if (saved) {
                            var f = JSON.parse(saved);
                            if (f.status !== undefined) $('#match_status_filter').val(f.status);
                            if (f.date_filter !== undefined) $('#match_date_filter').val(f.date_filter);
                            if (f.competition_id !== undefined) {
                                $('#match_competition_filter').val(f.competition_id).trigger('change');
                            }
                            if (f.from !== undefined) $('#match_from_date').val(f.from);
                            if (f.to !== undefined) $('#match_to_date').val(f.to);
                        }
                    } catch (e) {}
                }

                loadMatchFilters();

                $('#match_id').select2({
                    placeholder: "ابحث باسم الفريق أو البطولة...",
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: "{{ route('dashboard.notifications.matches.search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            saveMatchFilters();
                            return {
                                q: params.term,
                                status: $('#match_status_filter').val(),
                                date_filter: $('#match_date_filter').val(),
                                competition_id: $('#match_competition_filter').val(),
                                from: $('#match_from_date').val(),
                                to: $('#match_to_date').val()
                            };
                        },
                        processResults: function (data) {
                            $('#match_results_count').text(data.count ? 'تم العثور على ' + data.count + ' نتيجة' : '');
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    templateResult: matchTemplate,
                    templateSelection: function (data) {
                        if (!data.id) {
                            return data.text;
                        }
                        var sel = data.team1 + ' vs ' + data.team2;
                        if (data.score) {
                            sel += ' (' + data.score + ')';
                        }
                        return sel;
                    },
                    escapeMarkup: function (markup) {
                        return markup;
                    }
                });

                $('#match_status_filter, #match_date_filter, #match_competition_filter, #match_from_date, #match_to_date').on('change', function () {
                    saveMatchFilters();
                    $('#match_id').val(null).trigger('change');
                    $('#match_results_count').text('');
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
    </script>
@endsection
