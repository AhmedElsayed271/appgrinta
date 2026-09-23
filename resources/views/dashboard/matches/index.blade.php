{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')

    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.match.all')}}
{{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>
            <div class="card-toolbar">
                <!--begin::Button-->
                <a href="{{route('dashboard.matches.create')}}" class="btn btn-primary font-weight-bolder">
                <span class="svg-icon svg-icon-md">
                    <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <rect x="0" y="0" width="24" height="24"/>
                            <circle fill="#000000" cx="9" cy="15" r="6"/>
                            <path d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z" fill="#000000" opacity="0.3"/>
                        </g>
                    </svg>
                    <!--end::Svg Icon-->
                </span>{{__('site.operation.add')}}</a>
                <!--end::Button-->
            </div>
        </div>

        <div class="card-body">
            <div class="mb-7">
                <div class="row align-items-center">
                    <div class="col-lg-12 col-xl-12">
                        <div class="row align-items-center">
                            <div class="col-md-3 my-2 my-md-0">
                                <div class="input-icon">
                                    <input type="text" class="form-control" placeholder="Search..." id="kt_subheader_search_form" />
                                    <span>
                                    <i class="flaticon2-search-1 text-muted"></i>
                                </span>
                                </div>
                            </div>
                            <div class="col-md-3 my-2 my-md-0">
                                <div class="d-flex align-items-center">
                                    <label class="mr-3 mb-0 d-none d-md-block">Country:</label>
                                    <select class="form-control" id="kt_datatable_search_country_id">
                                        <option value="">All</option>
                                        @foreach($countries as $key=>$value)
                                            <option value="{{$key}}">{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 my-2 my-md-0">
                                <div class="d-flex align-items-center">
                                    <label class="mr-3 mb-0 d-none d-md-block">Competition:</label>
                                    <select class="form-control" id="kt_datatable_search_competition_id">
                                        <option value="">All</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 my-2 my-md-0">
                                <div class="d-flex align-items-center">
                                    <label class="mr-3 mb-0 d-none d-md-block">Competition Group:</label>
                                    <select class="form-control" id="kt_datatable_search_competition_child_id">
                                        <option value="">All</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 my-2 my-md-0 mt-3">
                                <div class="d-flex align-items-center">
                                    <label class="mr-3 mb-0 d-none d-md-block">Week:</label>
                                    <select class="form-control" id="kt_datatable_search_week">
                                        <option value="">All</option>
                                        @for($i=1;$i<40;$i++)
                                            <option value="{{$i}}">{{$i}}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>

        </div>

    </div>

@endsection

{{-- Scripts Section --}}
@section('scripts')
    <script !src="">
        "use strict";
        // Class definition
        var KTSelect2 = function() {
            // Private functions
            var demos = function() {
                // basic
                $('#kt_datatable_search_competition_id,#kt_datatable_search_week,#kt_datatable_search_country_id,#kt_datatable_search_competition_child_id').select2({
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
        // import date from "../../../metronic/plugins/formvalidation/src/js/validators/date";

        var KTAppsrolesListDatatable = function() {
            // Private functions

            // basic demo
            var _demo = function() {
                var token= $('meta[name="csrf-token"]').attr('content')
                var datatable = $('#kt_datatable').KTDatatable({
                    // datasource definition
                    data: {
                        type: 'remote',
                        source: {
                            read: {
                                url: '{{url()->current()}}',
                                method: 'GET',

                            },
                        },
                        pageSize: 20, // display 20 records per page
                        serverPaging: true,
                        serverFiltering: true,
                        serverSorting: true,

                    },
                    @if (app()->getLocale() == 'ar')
                    translate: {
                        records: {
                            processing: 'انتظر من فضلك...',
                            noRecords: 'لا توجد سجلات',
                        },
                        toolbar: {
                            pagination: {
                                items: {
                                    default: {
                                        first: 'الاول',
                                        prev: 'السابق',
                                        next: 'التالي',
                                        last: 'الأخير',
                                        more: 'More pages',
                                        input: 'رقم الصفحة',
                                        select: 'حدد حجم الصفحة',
                                        all: 'الكل',
                                    },

                                },
                            },
                        },
                    },
                    @endif
                    // layout definition
                    layout: {
                        scroll: false, // enable/disable datatable scroll both horizontal and vertical when needed.
                        footer: false, // display/hide footer
                    },

                    // column sorting
                    sortable: true,

                    pagination: true,

                    search: {
                        input: $('#kt_subheader_search_form'),
                        delay: 400,
                        key: 'generalSearch'
                    },

                    // columns definition
                    columns: [
                        {
                            field: 'id',
                            title: '#',
                            sortable: 'asc',
                            width: 40,
                            type: 'number',
                            selector: false,
                            textAlign: 'left',
                            template: function(data) {
                                return '<span class="font-weight-bolder">' + data.id + '</span>';
                            }
                        },
                        {
                            field: 'team1.name',
                            title: '{{__('site.team.one')}}',
                            template: function(data) {
                                return data.team1.name ;
                            }
                        },
                        {
                            field: 'team2.name',
                            title: '{{__('site.team.two')}}',
                            template: function(data) {
                                return data.team2.name ;
                            }
                        },
                        {
                            field: 'week',
                            title: '{{__('site.match.week')}}',
                            template: function(data) {
                                return data.week ;
                            }
                        },
                        {
                            field: 'status',
                            title: '{{__('site.match.status')}}',
                            template: function(data) {
                                return data.status ;
                            }
                        },
                        {
                            field: 'location',
                            title: '{{__('site.match.location')}}',
                            template: function(data) {
                                return data.location ;
                            }
                        },
                        {
                            field: 'competition.name',
                            title: '{{__('site.competition.name')}}',
                            template: function(data) {
                                return data.competition.name ;
                            }
                        },
                        {
                            field: 'actions',
                            title: '{{__('site.global.action')}}',
                            sortable: false,
                            overflow: 'visible',
                            autoHide: false,
                           template: function(data) {
                               return data.action ;
                           }
                        }
                    ],
                });

                $('#kt_datatable_search_status').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'Status');
                });

                $('#kt_datatable_search_type').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'Type');
                });
                $('#kt_datatable_search_week').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'week');
                });
                $('#kt_datatable_search_competition_id,#kt_datatable_search_competition_child_id').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'competition_id');
                });
                $('#kt_datatable_search_status, #kt_datatable_search_type').selectpicker();
            };

            return {
                // public functions
                init: function() {
                    _demo();
                },
            };
        }();

        jQuery(document).ready(function() {
            KTAppsrolesListDatatable.init();
            KTSelect2.init();
        });
        $(document).ready(function (){
            var country_id=0;
            $(document).on('change','#kt_datatable_search_country_id',function (e){
                country_id=e.target.value;
                if (e.target.value){
                    competitions(e.target.value)
                }
            });
            $(document).on('change','#kt_datatable_search_competition_id',function (e){
                if (e.target.value){
                    competitions(country_id,e.target.value,'#kt_datatable_search_competition_child_id')
                }
            });
        })
        function competitions(country,parent=null,select='#kt_datatable_search_competition_id'){
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
    </script>
@endsection

