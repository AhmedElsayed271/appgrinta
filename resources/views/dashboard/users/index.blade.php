{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')

    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.user.all')}}
{{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>
            <div class="card-toolbar">
                <!--begin::Button-->
                <a href="{{route('dashboard.users.create')}}" class="btn btn-primary font-weight-bolder">
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
                    <div class="col-lg-9 col-xl-8">
                        <div class="row align-items-center">
                            <div class="col-md-4 my-2 my-md-0">
                                <div class="input-icon">
                                    <input type="text" class="form-control" placeholder="Search..." id="kt_subheader_search_form" />
                                    <span>
                                    <i class="flaticon2-search-1 text-muted"></i>
                                </span>
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

        // import date from "../../../metronic/plugins/formvalidation/src/js/validators/date";

        var KTAppsUsersListDatatable = function() {
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
                        }, {
                            field: 'full_name',
                            title: '{{__('site.user.name')}}',
                            template: function(data) {
                                return data.full_name ;
                            }
                        },
                        {
                            field: 'email',
                            title: '{{__('site.user.email')}}',
                            template: function(data) {
                                return data.email ;
                            }
                        },
                        {
                            field: 'image_path',
                            title: '{{__('site.global.image')}}',
                            sortable: false,
                            width: 130,
                            overflow: 'visible',
                            autoHide: false,
                            template: function (data) {
                                return `<img class="img-thumbnail" style="border-radius: 50% 50%" src="${data.image_path}" width="50" height="50" />`;
                            }
                        },
                        {
                            field: 'created_at',
                            title: '{{__('site.global.created_at')}}',
                            type: 'date',
                            format: 'MM/DD/YYYY',
                            template: function(data) {
                                return new Date(data.created_at).toDateString();
                            },
                        },
                       {
                            field: 'Actions',
                            title: '{{__('site.global.action')}}',
                            sortable: false,
                            width: 130,
                            overflow: 'visible',
                            autoHide: false,
                           template: function(data) {
                               return data.action ;
                           }
                        }],
                });

                $('#kt_datatable_search_status').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'Status');
                });

                $('#kt_datatable_search_type').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'Type');
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
            KTAppsUsersListDatatable.init();
        });
    </script>
@endsection

