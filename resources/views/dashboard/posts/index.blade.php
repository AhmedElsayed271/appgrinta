{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')

    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.post.all')}}
{{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>
            <div class="card-toolbar">
                <!--begin::Button-->
                <a href="{{route('dashboard.posts.create')}}" class="btn btn-primary font-weight-bolder">
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
                            <div class="col-md-4 my-2 my-md-0">
                                <div class="d-flex align-items-center">
                                    <label class="mr-3 mb-0 d-none d-md-block">Main Category:</label>
                                    <select class="form-control" id="kt_datatable_search_parent_id">
                                        <option value="">All</option>
                                        @foreach($main_Categories as $key=>$value)
                                            <option value="{{$key}}">{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 my-2 my-md-0">
                                <div class="d-flex align-items-center">
                                    <label class="mr-3 mb-0 d-none d-md-block">Writer:</label>
                                    <select class="form-control" id="kt_datatable_search_writer">
                                        <option value="">All</option>
                                        @foreach(\App\Models\User::all() as $user)
                                            <option value="{{$user->id}}">{{$user->first_name .' '. $user->last_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12 my-2 my-md-0">


                                 {{-- ---------- --}}

                                <div class="mt-10 mb-5 collapse" id="kt_datatable_group_action_form" style="width: 150%;">
                                    <div class="d-flex align-items-center">
                                        <div class="font-weight-bold text-danger mr-3">
                                            Selected <span id="kt_datatable_selected_records">0</span> records:
                                        </div>
                                        @permission('delete-post', 'full-permissions')
                                        <button class="btn btn-danger mr-2" type="button" id="datatable_delete_all">
                                            Delete All
                                        </button>
                                        @endpermission
                                        <button class="btn btn-success mr-2" type="button" id="datatable_feacherd_all">
                                            Featured Selected Records
                                        </button>
                                        
                                        <button class="btn btn-warning mr-10" type="button" id="datatable_un_feacherd_all">
                                            Un featured Selected Records
                                        </button>
                                        {{-- <div class="border border-primary"> --}}
                                            <div class="d-flex align-items-center mr-2">
                                                <label for="user_id" class="mr-2">Assign to : </label>
                                            <select name="users" id="user_id" class="form-control mr-2">
                                                <option value="">{{ __('choose') }}</option>
                                                @foreach(\App\Models\User::all() as $user)
                                                    <option value="{{$user->id}}">{{$user->first_name.' '.$user->last_name}}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-info mr-6" type="button" id="datatable_assign_all">Assign</button>
                                            <small class="text text-danger" id="validate_user" style="display: none">Please select user first</small>

                                        </div>
                                        {{-- </div> --}}
                                    </div>
                                </div>


                                {{-- ---------- --}}
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

                    // selector: true,

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
                                field: 'checkbox',
                                title: '',
                                sortable: false,
                                width: 20,
                                textAlign: 'center',
                                selector: { class: 'kt-checkbox--solid' },
                                template: function(data) {
                                return  data.id;
                            }
                            },
                        {
                            field: 'name',
                            title: '{{__('site.post.name')}}',
                            template: function(data) {
                                return data.name ;
                            }
                        },
                        {
                            field: 'category.name',
                            sortable: false,
                            title: '{{__('site.post.category')}}',
                            template: function(data) {
                                return data.category.name ;
                            }
                        },
                        {
                            field: 'user_id',
                            sortable: false,
                            title: '{{__('site.post.author')}}',
                            template: function(data) {
                                return data.user_name ;
                                // return data.user.first_name + ' ' + data.user.last_name ;
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
                                return `<img class="img-thumbnail" src="${data.image_path}" width="50" height="50" />`;
                            }
                        },
                        {
                            field: 'sort',
                            title: 'sort',
                            sortable: false,
                            width: 75,
                            overflow: 'visible',
                            autoHide: false,
                            template: function (data) {
                                data.sort = data.sort == null ? "" : data.sort ;
                                return `<input type="text" class="form-control sort" id="${data.id}"  value="${data.sort}">`;
                            }
                        },
                        {
                            field: 'featured',
                            title: 'featured',
                            sortable: true,
                            width: 75,
                            overflow: 'visible',
                            autoHide: false,
                            template: function (data) {
                                data.featured = data.featured == 1 ? "featured" : "" ;
                                return data.featured;
                            }
                        },
                        {
                            field: 'Actions',
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
                    datatable.search($(this).val().toLowerCase(), 'type');
                });
                $('#kt_datatable_search_parent_id').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'category_id');
                });
                $('#kt_datatable_search_writer').on('change', function() {
                    datatable.search($(this).val().toLowerCase(), 'user_id');
                });

                // const sendIds = [];

                datatable.on(
                    'datatable-on-check datatable-on-uncheck',
                    function(e) {
                        var checkedNodes = datatable.rows('.datatable-row-active').nodes();
                        var count = checkedNodes.length;
                        $('#kt_datatable_selected_records').html(count);
                        if (count > 0) {
                            $('#kt_datatable_group_action_form').collapse('show');
                        } else {
                            $('#kt_datatable_group_action_form').collapse('hide');
                        }

                        var ids = datatable.rows('.datatable-row-active').
                        nodes().
                        find('.checkbox > [type="checkbox"]').
                        map(function(i, chk) {
                            return $(chk).val();
                        });
                        console.log(ids);
                 
                    });

                    function isNumber(value) {
                        return !isNaN(value) && parseFloat(Number(value)) === value && !isNaN(parseInt(value, 10));
                    }

                        $(document).on('click','#datatable_delete_all',function (e){
                            e.preventDefault();
                            const that = $(this);
                            Swal.fire({
                                title: "{{__('site.global.are_you_sure')}}",
                                text: '{{__('site.global.delete_this_row')}}',
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: "{{__('site.global.yes')}}",
                                cancelButtonText: "{{__('site.global.no')}}",
                                reverseButtons: true
                            }).then(function(result) {
                                if (result.value) {
                                    Swal.fire(
                                        "Deleted!",
                                        "Your records has been deleted.",
                                        "success"
                                    )

                                    var ids = datatable.rows('.datatable-row-active').
                                        nodes().
                                        find('.checkbox > [type="checkbox"]').
                                        map(function(i, chk) {
                                            var num = parseInt($(chk).val());
                                            return isNumber(num) ? num : '';
                                        });
                                        // console.log(ids);
                                        var arr = [];
                                        for (var i=0 ; i<ids.length ; i++) {
                                            arr.push(ids[i]);
                                        }
                                        console.log(arr);
                                        $.ajax({
                                                type: "POST",
                                                url: "{{ route('dashboard.posts.destroyAll') }}",
                                                data:{
                                                    _token:'{{csrf_token()}}',
                                                    'ids' : arr,
                                                },

                                            }).done(function(data) {
                                                if (data.success == true) {
                                                    window.location.reload();
                                                }
                                            });
                                  
                                } else if (result.dismiss === "cancel") {
                                    Swal.fire(
                                        "Cancelled",
                                        "Your imaginary data is safe :)",
                                        "error"
                                    )
                                }
                            });
                        });

                        $(document).on('click','#datatable_feacherd_all',function (e){
                            var ids = datatable.rows('.datatable-row-active').
                                        nodes().find('.checkbox > [type="checkbox"]').map(function(i, chk) {
                                            var num = parseInt($(chk).val());
                                            return isNumber(num) ? num : '';
                                        });
                                        var arr = [];
                                        for (var i=0 ; i<ids.length ; i++) {
                                            arr.push(ids[i]);
                                        }
                                        console.log(arr);
                                        $.ajax({
                                                type: "POST",
                                                url: "{{ route('dashboard.posts.featureAll') }}",
                                                data:{
                                                    _token:'{{csrf_token()}}',
                                                    'ids' : arr,
                                                },

                                            }).done(function(data) {
                                                if (data.success == true) {
                                                    window.location.reload();
                                                }
                                            });
                        });

                        $(document).on('click','#datatable_un_feacherd_all',function (e){
                            var ids = datatable.rows('.datatable-row-active').
                                        nodes().find('.checkbox > [type="checkbox"]').map(function(i, chk) {
                                            var num = parseInt($(chk).val());
                                            return isNumber(num) ? num : '';
                                        });
                                        var arr = [];
                                        for (var i=0 ; i<ids.length ; i++) {
                                            arr.push(ids[i]);
                                        }
                                        console.log(arr);
                                        $.ajax({
                                                type: "POST",
                                                url: "{{ route('dashboard.posts.unfeatureAll') }}",
                                                data:{
                                                    _token:'{{csrf_token()}}',
                                                    'ids' : arr,
                                                },

                                            }).done(function(data) {
                                                if (data.success == true) {
                                                    window.location.reload();
                                                }
                                            });
                        });

                        $(document).on('click','#datatable_assign_all',function (e){
                            if ($('#user_id').val() === "") {
                                document.getElementById('validate_user').style.display = 'block';
                            }else {
                                document.getElementById('validate_user').style.display = 'none';
                                var ids = datatable.rows('.datatable-row-active').
                                        nodes().find('.checkbox > [type="checkbox"]').map(function(i, chk) {
                                            var num = parseInt($(chk).val());
                                            return isNumber(num) ? num : '';
                                        });
                                        var arr = [];
                                        for (var i=0 ; i<ids.length ; i++) {
                                            arr.push(ids[i]);
                                        }
                                        console.log(arr);
                                        $.ajax({
                                                type: "POST",
                                                url: "{{ route('dashboard.posts.assignAll') }}",
                                                data:{
                                                    _token:'{{csrf_token()}}',
                                                    'ids' : arr,
                                                    'user_id': $('#user_id').val(),
                                                },

                                            }).done(function(data) {
                                                // console.log($data);
                                                if (data.success == true) {
                                                    window.location.reload();
                                                }
                                            });
                            }
                        });

                        
                $('#kt_datatable_search_status, #kt_datatable_search_type, #kt_datatable_search_parent_id').selectpicker();
            

       

       
       
            };

         
         


            $(document).ready(function (){
            $(document).on('change','.sort',function (e){
               // alert(this.value);
               var id = this.id;
               var value = this.value;

                $.ajax({
                    type: "POST",
                    url: "{{ route('dashboard.posts.changePostSort') }}",
                    data:{
                        _token:'{{csrf_token()}}',
                        'id' : id,
                        'value' : value,
                    },

                });
            });

          
            

        });

            return {
                // public functions
                init: function() {
                    _demo();
                },
            };
        }();

        jQuery(document).ready(function() {
            KTAppsrolesListDatatable.init();
        });
    </script>
@endsection

