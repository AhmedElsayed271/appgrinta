{{-- Extends layout --}}
@extends('layouts.dashboard.default')

@section('styles')
<style>
    .notif-match-search .select2-container { width: 100% !important; }
    .notif-match-search .select2-container--default .select2-selection--single {
        height: 46px;
        border: 1px solid #E4E6EF;
        border-radius: .42rem;
        background-color: #F3F6F9;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .notif-match-search .select2-container--default.select2-container--focus .select2-selection--single,
    .notif-match-search .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #6993FF;
        box-shadow: 0 0 0 .2rem rgba(105, 147, 255, .15);
    }
    .notif-match-search .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 44px;
        padding-inline-start: 44px;
        padding-inline-end: 32px;
        color: #3F4254;
        font-weight: 500;
    }
    .notif-match-search .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px; }
    .notif-match-search .select2-dropdown {
        border: 1px solid #E4E6EF;
        border-radius: .42rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
        overflow: hidden;
    }
    .notif-match-search .select2-search--dropdown { padding: 10px; background: #fff; }
    .notif-match-search .select2-search__field {
        border: 1px solid #E4E6EF !important;
        border-radius: .42rem;
        padding: 8px 12px;
        outline: none;
        width: 100% !important;
    }
    .notif-match-search .select2-results__options { max-height: 340px; background: #fff; }
    .notif-match-search .select2-results__option { padding: 10px 14px; transition: background-color .15s ease; border-bottom: 1px solid #f5f5f5; }
    .notif-match-search .select2-results__option:last-child { border-bottom: 0; }
    .notif-match-search .select2-results__option--highlighted { background-color: #dddddd !important; color: #1F1F1F !important; }
    .notif-match-search .select2-results__option:hover { background-color: #e9e9e9 !important; }
    .notif-match-search .select2-results__option[aria-selected=true] { background-color: #f0f0f0 !important; color: #1F1F1F !important; }
    .notif-match-search__icon {
        position: absolute;
        top: 50%;
        inset-inline-start: 16px;
        transform: translateY(-50%);
        z-index: 5;
        pointer-events: none;
        line-height: 0;
    }
    .notif-status-badge { box-shadow: 0 1px 2px rgba(0,0,0,.08); }
</style>
@endsection

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
                            <div class="row mb-4">
                                <div class="col-md-3 col-sm-12">
                                    <label class="font-weight-bold text-muted mb-1" for="match_status_filter">{{ app()->getLocale() == 'ar' ? 'الحالة' : 'Status' }}</label>
                                    <select id="match_status_filter" class="form-control form-control-solid">
                                        <option value="all">{{ app()->getLocale() == 'ar' ? 'كل الماتشات' : 'All' }}</option>
                                        <option value="live">{{ app()->getLocale() == 'ar' ? 'جارية الآن' : 'Live' }}</option>
                                        <option value="upcoming">{{ app()->getLocale() == 'ar' ? 'لم تبدأ بعد' : 'Upcoming' }}</option>
                                        <option value="finished">{{ app()->getLocale() == 'ar' ? 'انتهت' : 'Finished' }}</option>
                                        <option value="postponed">{{ app()->getLocale() == 'ar' ? 'مؤجلة / ملغاة' : 'Postponed / Cancelled' }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <label class="font-weight-bold text-muted mb-1" for="match_date_filter">{{ app()->getLocale() == 'ar' ? 'التاريخ' : 'Date' }}</label>
                                    <select id="match_date_filter" class="form-control form-control-solid">
                                        <option value="">{{ app()->getLocale() == 'ar' ? 'كل الأوقات' : 'All' }}</option>
                                        <option value="today">{{ app()->getLocale() == 'ar' ? 'اليوم' : 'Today' }}</option>
                                        <option value="yesterday">{{ app()->getLocale() == 'ar' ? 'أمس' : 'Yesterday' }}</option>
                                        <option value="last7">{{ app()->getLocale() == 'ar' ? 'آخر 7 أيام' : 'Last 7 days' }}</option>
                                        <option value="last30">{{ app()->getLocale() == 'ar' ? 'آخر 30 يوم' : 'Last 30 days' }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <label class="font-weight-bold text-muted mb-1" for="match_competition_filter">{{ app()->getLocale() == 'ar' ? 'البطولة' : 'Competition' }}</label>
                                    <select id="match_competition_filter" class="form-control form-control-solid">
                                        <option value="">{{ app()->getLocale() == 'ar' ? 'الكل' : 'All' }}</option>
                                        @foreach($competitions as $comp)
                                            <option value="{{$comp->id}}">{{ $comp->translate(app()->getLocale())?->name ?? $comp->translate('en')?->name ?? $comp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="font-weight-bold text-muted mb-1" for="match_id">{{ __('site.match.name') }}</label>
                                <div class="position-relative notif-match-search">
                                    <span class="notif-match-search__icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7E8299" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </span>
                                    <select class="form-control form-control-solid" id="match_id" name="match_id"></select>
                                </div>
                                <small id="match_results_count" class="text-muted d-block mt-2"></small>
                            </div>
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

            // Class definition
            var KTSelect2 = function() {

            function getStatusBadge(status) {
                var isAr = "{{ app()->getLocale() }}" === 'ar';
                var map = {
                    'NS': {text: isAr ? 'لم تبدأ' : 'Not Started', color: '#3699FF'},
                    'TBD': {text: isAr ? 'لم تحدد' : 'TBD', color: '#8950FC'},
                    '1H': {text: isAr ? 'الشوط الأول' : '1st Half', color: '#FF5C5C'},
                    'HT': {text: isAr ? 'استراحة' : 'HT', color: '#FFA800'},
                    '2H': {text: isAr ? 'الشوط الثاني' : '2nd Half', color: '#FF5C5C'},
                    'ET': {text: isAr ? 'وقت إضافي' : 'ET', color: '#FF5C5C'},
                    'P': {text: isAr ? 'ركلات ترجيح' : 'Penalties', color: '#FF5C5C'},
                    'PEN': {text: isAr ? 'ركلات ترجيح' : 'Penalties', color: '#FF5C5C'},
                    'FT': {text: isAr ? 'انتهت' : 'FT', color: '#1BC5BD'},
                    'AET': {text: isAr ? 'انتهت بعد إضافي' : 'AET', color: '#1BC5BD'},
                    'PST': {text: isAr ? 'مؤجلة' : 'Postponed', color: '#FFC542'},
                    'CANC': {text: isAr ? 'ملغاة' : 'Cancelled', color: '#F64E60'},
                    'SUSP': {text: isAr ? 'موقوفة' : 'Suspended', color: '#F64E60'},
                    'ABD': {text: isAr ? 'ألغيت' : 'Abandoned', color: '#F64E60'},
                    'AWD': {text: isAr ? 'فوز بالانسحاب' : 'AWD', color: '#D63384'},
                    'WO': {text: isAr ? 'انسحاب' : 'WO', color: '#D63384'}
                };
                var s = map[status] || {text: status, color: '#7E8299'};
                return '<span class="notif-status-badge" style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:600;color:#fff;background:' + s.color + ';white-space:nowrap;line-height:14px;">' + s.text + '</span>';
            }

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
                var isAr2 = "{{ app()->getLocale() }}" === 'ar';
                if (data.group) {
                    details.push((isAr2 ? "الجولة" : "Round") + ": " + data.group);
                }
                if (data.week !== undefined && data.week !== null && data.week !== '') {
                    details.push((isAr2 ? "الأسبوع" : "Week") + ": " + data.week);
                }
                if (data.score) {
                    details.push("<strong>" + (isAr2 ? "النتيجة" : "Result") + ": " + data.score + "</strong>");
                }

                var badge = getStatusBadge(data.status);
                return $(
                    '<div class="notif-match-result" style="padding:6px 4px;">' +
                        '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;font-weight:600;font-size:13px;">' +
                            '<span>' + data.team1 + ' vs ' + data.team2 + '</span>' +
                            badge +
                        '</div>' +
                        (details.length ? '<div style="font-size:11px;color:#7E8299;margin-top:3px;">' + details.join(' | ') + '</div>' : '') +
                    '</div>'
                );
            }

            // Private functions
            var demos = function() {
                function saveMatchFilters() {
                    try {
                        localStorage.setItem('notif_match_filters', JSON.stringify({
                            status: $('#match_status_filter').val(),
                            date_filter: $('#match_date_filter').val(),
                            competition_id: $('#match_competition_filter').val()
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
                        }
                    } catch (e) {}
                }

            $('#match_competition_filter').select2({
                placeholder: "{{ __('site.all') }}",
                allowClear: true,
                width: '100%'
            });

                loadMatchFilters();

                $('#match_id').select2({
                    placeholder: @if(app()->getLocale() == 'ar') "ابحث باسم الفريق أو البطولة..." @else "Search by team or competition..." @endif,
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
                                competition_id: $('#match_competition_filter').val()
                            };
                        },
                        processResults: function (data) {
                            var foundTxt = @if(app()->getLocale() == 'ar') "تم العثور على" @else "Found" @endif;
                            var resultTxt = @if(app()->getLocale() == 'ar') "نتيجة" @else "result(s)" @endif;
                            $('#match_results_count').text(data.count ? foundTxt + " " + data.count + " " + resultTxt : '');
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
                        return data.team1 + ' vs ' + data.team2;
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
