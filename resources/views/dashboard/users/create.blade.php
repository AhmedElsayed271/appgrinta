{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.user.create')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.users.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="first_name">{{__('site.user.first_name')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="first_name" >
                                <input type="text" name="first_name" id="first_name" class="form-control  @error('first_name') is-invalid @enderror" value="{{old('first_name')}}"  />
                                @error('first_name')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="first_name">{{__('site.user.last_name')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="last_name" >
                                <input type="text" name="last_name" id="last_name" class="form-control  @error('last_name') is-invalid @enderror"  value="{{old('last_name')}}"/>
                                @error('last_name')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">{{__('site.user.email')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="email" >
                                <input type="email" name="email" id="email" class="form-control  @error('email') is-invalid @enderror"  value="{{old('email')}}"/>
                                @error('email')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="phone">{{__('site.user.phone')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="phone" >
                                <input type="text" name="phone" id="phone" class="form-control  @error('phone') is-invalid @enderror"  value="{{old('phone')}}"/>
                                @error('phone')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">{{__('site.user.password')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="password" >
                                <input type="password" name="password" id="password" class="form-control  @error('password') is-invalid @enderror"  />
                                @error('password')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">{{__('site.user.password_confirmation')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" id="password_confirmation" >
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control  @error('password_confirmation') is-invalid @enderror"  />
                                @error('password_confirmation')
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-3 col-form-label">{{__('site.user.gender')}}</label>
                        <div class="col-9 col-form-label">
                            <div class="radio-inline">
                                <label class="radio radio-success">
                                    <input type="radio" name="gender" value="0" {{old('gender') == 0 ? 'checked' : ''}} />
                                    <span></span>
                                    {{__('site.user.male')}}
                                </label>
                                <label class="radio radio-danger">
                                    <input type="radio" name="gender" value="1" {{old('gender') == 1 ? 'checked' : ''}}/>
                                    <span></span>
                                    {{__('site.user.female')}}
                                </label>
                            </div>
                            @error('gender')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label  col-lg-3 col-sm-12" for="date_of_birth">{{__('site.user.date_of_birth')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <div class="input-group date" >
                                <input type="text" class="form-control" name="date_of_birth" readonly value="{{old('date_of_birth')}}"  id="date_of_birth"/>
                                <div class="input-group-append">
                                   <span class="input-group-text">
                                    <i class="la la-calendar"></i>
                                   </span>
                                </div>
                            </div>
                            @error('gender')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-xl-3 col-lg-3 col-form-label">{{__('site.global.image')}}</label>
                        <div class="col-lg-6 col-xl-6">
                            <div class="image-input image-input-outline image-input-circle" id="kt_image_3">
                                <div class="image-input-wrapper" style="background-image: url({{asset('storage/uploads/user_images/default.png')}})"></div>

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
                        <label class="col-form-label  col-lg-3 col-sm-12">{{__('site.user.roles')}}</label>
                        <div class=" col-lg-12 col-md-12 col-sm-12">
                            <select class="form-control select2" id="roles" name="roles[]" multiple="multiple">
                                @foreach ($roles as $role)
                                    <option value="{{$role->id}}"  {{old('roles') != null ? (in_array($role->id,old('roles'))? 'selected' : '') :''}}>{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>


                    <ul class="nav nav-tabs nav-tabs-line">
                        @foreach ($roles as $key => $role)
                        <li class="nav-item">
                            <a class="nav-link {{$key == 0 ? 'active' : ''}}" data-toggle="tab" href="#kt_tab_pane_{{$role->id}}">{{$role->name}}</a>
                        </li>
                        @endforeach
                    </ul>
                    <div class="tab-content mt-5" id="myTabContent">
                        @foreach ($roles as $key => $role)
                            <div class="tab-pane fade show {{$key == 0 ? 'active' : ''}}" id="kt_tab_pane_{{$role->id}}" role="tabpanel" aria-labelledby="kt_tab_pane_2">
                                <div class="checkbox-inline">
                                    @foreach ($role->permissions as $key => $permission)
                                        <label class="checkbox checkbox-success">
                                            <input type="checkbox" name="permissions[]" value="{{$permission->id}}" {{ old('permissions') != null ? (in_array($permission->id,old('permissions'))? 'checked' : '') : '' }}/>
                                            <span></span>
                                            {{$permission->name}}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                        <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.add')}}</button>
                    <a href="{{route('dashboard.users.index')}}" class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
                </div>
            </form>
        </div>

    </div>
@endsection

@section('scripts')
    <script !src="">
        var avatar3 = new KTImageInput('kt_image_3');
        // Class definition

        var KTBootstrapDatepicker = function () {

            var arrows;
            if (KTUtil.isRTL()) {
                arrows = {
                    leftArrow: '<i class="la la-angle-right"></i>',
                    rightArrow: '<i class="la la-angle-left"></i>'
                }
            } else {
                arrows = {
                    leftArrow: '<i class="la la-angle-left"></i>',
                    rightArrow: '<i class="la la-angle-right"></i>'
                }
            }

            // Private functions
            var demos = function () {
                // minimum setup

                // enable clear button
                $('#date_of_birth, #date_of_birth_validate').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayBtn: "linked",
                    clearBtn: true,
                    todayHighlight: true,
                    templates: arrows
                });

                // enable clear button for modal demo
                $('#date_of_birth').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayBtn: "linked",
                    clearBtn: true,
                    todayHighlight: true,
                    templates: arrows
                });



            }

            return {
                // public functions
                init: function() {
                    demos();
                }
            };
        }();



        // Class definition
        var KTSelect2 = function() {
            // Private functions
            var demos = function() {



                // multi select
                $('#roles').select2({
                    placeholder: "Select a state",
                });

            }

            // Public functions
            return {
                init: function() {
                    demos();
                }
            };
        }();

        jQuery(document).ready(function() {
            KTBootstrapDatepicker.init();
            KTSelect2.init();
        });
    </script>
@endsection
