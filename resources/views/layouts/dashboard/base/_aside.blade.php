    <!--begin::Aside-->
<div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto" id="kt_aside">
    <!--begin::Brand-->
    <div class="brand flex-column-auto" id="kt_brand">
        <!--begin::Logo-->
        <a href="{{route('dashboard.welcome',['locale' => app()->getLocale()])}}" class="brand-logo">
            <img alt="Logo" src="{{ $site_settings->logo_path ?? asset('media/logos/logo-default-inverse.png')}}" />
        </a>
        <!--end::Logo-->
        <!--begin::Toggle-->
        <button class="brand-toggle btn btn-sm px-0" id="kt_aside_toggle">
            <span class="svg-icon svg-icon svg-icon-xl">
                <!--begin::Svg Icon | path:assets/media/svg/icons/Navigation/Angle-double-left.svg-->
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <polygon points="0 0 24 0 24 24 0 24" />
                        <path d="M5.29288961,6.70710318 C4.90236532,6.31657888 4.90236532,5.68341391 5.29288961,5.29288961 C5.68341391,4.90236532 6.31657888,4.90236532 6.70710318,5.29288961 L12.7071032,11.2928896 C13.0856821,11.6714686 13.0989277,12.281055 12.7371505,12.675721 L7.23715054,18.675721 C6.86395813,19.08284 6.23139076,19.1103429 5.82427177,18.7371505 C5.41715278,18.3639581 5.38964985,17.7313908 5.76284226,17.3242718 L10.6158586,12.0300721 L5.29288961,6.70710318 Z" fill="#000000" fill-rule="nonzero" transform="translate(8.999997, 11.999999) scale(-1, 1) translate(-8.999997, -11.999999)" />
                        <path d="M10.7071009,15.7071068 C10.3165766,16.0976311 9.68341162,16.0976311 9.29288733,15.7071068 C8.90236304,15.3165825 8.90236304,14.6834175 9.29288733,14.2928932 L15.2928873,8.29289322 C15.6714663,7.91431428 16.2810527,7.90106866 16.6757187,8.26284586 L22.6757187,13.7628459 C23.0828377,14.1360383 23.1103407,14.7686056 22.7371482,15.1757246 C22.3639558,15.5828436 21.7313885,15.6103465 21.3242695,15.2371541 L16.0300699,10.3841378 L10.7071009,15.7071068 Z" fill="#000000" fill-rule="nonzero" opacity="0.3" transform="translate(15.999997, 11.999999) scale(-1, 1) rotate(-270.000000) translate(-15.999997, -11.999999)" />
                    </g>
                </svg>
                <!--end::Svg Icon-->
            </span>
        </button>
        <!--end::Toolbar-->
    </div>
    <!--end::Brand-->
    <!--begin::Aside Menu-->
    <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
        <!--begin::Menu Container-->
        <div id="kt_aside_menu" class="aside-menu my-4" data-menu-vertical="1" data-menu-scroll="1" data-menu-dropdown-timeout="500">
            <!--begin::Menu Nav-->
            <ul class="menu-nav">
                <li class="menu-item {{url()->current() == route('dashboard.welcome') ?'menu-item-active' : ''}}" aria-haspopup="true">
                    <a href="{{route('dashboard.welcome',['locale' => app()->getLocale()])}}" class="menu-link">
                        <span class="svg-icon menu-icon">
                            <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Layers.svg-->
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <polygon points="0 0 24 0 24 24 0 24" />
                                    <path d="M3.95709826,8.41510662 L11.47855,3.81866389 C11.7986624,3.62303967 12.2013376,3.62303967 12.52145,3.81866389 L20.0429,8.41510557 C20.6374094,8.77841684 21,9.42493654 21,10.1216692 L21,19.0000642 C21,20.1046337 20.1045695,21.0000642 19,21.0000642 L4.99998155,21.0000673 C3.89541205,21.0000673 2.99998155,20.1046368 2.99998155,19.0000673 L2.99999828,10.1216672 C2.99999935,9.42493561 3.36258984,8.77841732 3.95709826,8.41510662 Z M10,13 C9.44771525,13 9,13.4477153 9,14 L9,17 C9,17.5522847 9.44771525,18 10,18 L14,18 C14.5522847,18 15,17.5522847 15,17 L15,14 C15,13.4477153 14.5522847,13 14,13 L10,13 Z" fill="#000000"/>
                                </g>
                            </svg>
                            <!--end::Svg Icon-->
                        </span>
                        <span class="menu-text">{{__('site.global.dashboard')}}</span>
                    </a>
                </li>
                @role('admin,user')
                {{-- Start users--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.users.index') || url()->current() == route('dashboard.users.create')
                            ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <span class="svg-icon svg-icon-primary svg-icon-2x"><!--begin::Svg Icon | path:/var/www/preview.keenthemes.com/metronic/releases/2021-02-01-052524/theme/html/demo2/dist/../src/media/svg/icons/Communication/Address-card.svg--><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24"/>
                                    <path d="M6,2 L18,2 C19.6568542,2 21,3.34314575 21,5 L21,19 C21,20.6568542 19.6568542,22 18,22 L6,22 C4.34314575,22 3,20.6568542 3,19 L3,5 C3,3.34314575 4.34314575,2 6,2 Z M12,11 C13.1045695,11 14,10.1045695 14,9 C14,7.8954305 13.1045695,7 12,7 C10.8954305,7 10,7.8954305 10,9 C10,10.1045695 10.8954305,11 12,11 Z M7.00036205,16.4995035 C6.98863236,16.6619875 7.26484009,17 7.4041679,17 C11.463736,17 14.5228466,17 16.5815,17 C16.9988413,17 17.0053266,16.6221713 16.9988413,16.5 C16.8360465,13.4332455 14.6506758,12 11.9907452,12 C9.36772908,12 7.21569918,13.5165724 7.00036205,16.4995035 Z" fill="#000000"/>
                                </g>
                                </svg><!--end::Svg Icon-->
                            </span>
                        </span>
                        <span class="menu-text">{{__('site.user.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.user.show')}}</span>
                                </span>
                            </li>
                            @permission('view-user,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.users.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.users.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.user.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-user,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.users.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.users.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.user.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End users--}}
                @endrole
                @role('admin,role')
                {{-- Start roles--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.roles.index') || url()->current() == route('dashboard.roles.create')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <span class="svg-icon svg-icon-primary svg-icon-2x">
                               <!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\themes\metronic\theme\html\demo1\dist/../src/media/svg/icons\Code\Lock-circle.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"/>
                                        <circle fill="#000000" opacity="0.3" cx="12" cy="12" r="10"/>
                                        <path d="M14.5,11 C15.0522847,11 15.5,11.4477153 15.5,12 L15.5,15 C15.5,15.5522847 15.0522847,16 14.5,16 L9.5,16 C8.94771525,16 8.5,15.5522847 8.5,15 L8.5,12 C8.5,11.4477153 8.94771525,11 9.5,11 L9.5,10.5 C9.5,9.11928813 10.6192881,8 12,8 C13.3807119,8 14.5,9.11928813 14.5,10.5 L14.5,11 Z M12,9 C11.1715729,9 10.5,9.67157288 10.5,10.5 L10.5,11 L13.5,11 L13.5,10.5 C13.5,9.67157288 12.8284271,9 12,9 Z" fill="#000000"/>
                                    </g>
                                </svg><!--end::Svg Icon-->
                            </span>
                        </span>
                        <span class="menu-text">{{__('site.role.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.role.show')}}</span>
                                </span>
                            </li>
                            @permission('view-role,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.roles.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.roles.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.role.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-role,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.roles.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.roles.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.role.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End roles--}}
                @endrole

                @role('admin,category')
                {{-- Start categories--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.categories.index') || url()->current() == route('dashboard.categories.create')
                            ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <span class="svg-icon svg-icon-primary svg-icon-2x">
                                <!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\themes\metronic\theme\html\demo1\dist/../src/media/svg/icons\Layout\Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"/>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"/>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"/>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                        </span>
                        <span class="menu-text">{{__('site.category.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.category.show')}}</span>
                                </span>
                            </li>
                            @permission('view-category,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.categories.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.categories.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.category.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-category,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.categories.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.categories.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.category.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End categories--}}
                @endrole

                @role('admin,post')
                {{-- Start posts--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.posts.index') || url()->current() == route('dashboard.posts.create')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <span class="svg-icon svg-icon-primary svg-icon-2x">
                                <!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\themes\metronic\theme\html\demo1\dist/../src/media/svg/icons\Layout\Layout-grid.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"/>
                                        <rect fill="#000000" opacity="0.3" x="4" y="4" width="4" height="4" rx="1"/>
                                        <path d="M5,10 L7,10 C7.55228475,10 8,10.4477153 8,11 L8,13 C8,13.5522847 7.55228475,14 7,14 L5,14 C4.44771525,14 4,13.5522847 4,13 L4,11 C4,10.4477153 4.44771525,10 5,10 Z M11,4 L13,4 C13.5522847,4 14,4.44771525 14,5 L14,7 C14,7.55228475 13.5522847,8 13,8 L11,8 C10.4477153,8 10,7.55228475 10,7 L10,5 C10,4.44771525 10.4477153,4 11,4 Z M11,10 L13,10 C13.5522847,10 14,10.4477153 14,11 L14,13 C14,13.5522847 13.5522847,14 13,14 L11,14 C10.4477153,14 10,13.5522847 10,13 L10,11 C10,10.4477153 10.4477153,10 11,10 Z M17,4 L19,4 C19.5522847,4 20,4.44771525 20,5 L20,7 C20,7.55228475 19.5522847,8 19,8 L17,8 C16.4477153,8 16,7.55228475 16,7 L16,5 C16,4.44771525 16.4477153,4 17,4 Z M17,10 L19,10 C19.5522847,10 20,10.4477153 20,11 L20,13 C20,13.5522847 19.5522847,14 19,14 L17,14 C16.4477153,14 16,13.5522847 16,13 L16,11 C16,10.4477153 16.4477153,10 17,10 Z M5,16 L7,16 C7.55228475,16 8,16.4477153 8,17 L8,19 C8,19.5522847 7.55228475,20 7,20 L5,20 C4.44771525,20 4,19.5522847 4,19 L4,17 C4,16.4477153 4.44771525,16 5,16 Z M11,16 L13,16 C13.5522847,16 14,16.4477153 14,17 L14,19 C14,19.5522847 13.5522847,20 13,20 L11,20 C10.4477153,20 10,19.5522847 10,19 L10,17 C10,16.4477153 10.4477153,16 11,16 Z M17,16 L19,16 C19.5522847,16 20,16.4477153 20,17 L20,19 C20,19.5522847 19.5522847,20 19,20 L17,20 C16.4477153,20 16,19.5522847 16,19 L16,17 C16,16.4477153 16.4477153,16 17,16 Z" fill="#000000"/>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                        </span>
                        <span class="menu-text">{{__('site.post.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.post.show')}}</span>
                                </span>
                            </li>
                            @permission('view-post,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.posts.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.posts.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.post.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-post,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.posts.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.posts.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.post.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End posts--}}
                @endrole

                @role('admin,country')
                {{-- Start countries--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.countries.index') || url()->current() == route('dashboard.countries.create')
                            ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <i class="flaticon2-world icon-md text-primary"></i>
                        </span>
                        <span class="menu-text">{{__('site.country.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.country.show')}}</span>
                                </span>
                            </li>
                            @permission('view-country,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.countries.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.countries.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.country.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-country,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.countries.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.countries.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.country.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End countries--}}
                @endrole

                @role('admin,competition')
                {{-- Start competitions--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.competitions.index') || url()->current() == route('dashboard.competitions.create')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <i class="flaticon2-cup icon-md text-primary"></i>
                        </span>
                        <span class="menu-text">{{__('site.competition.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.competition.show')}}</span>
                                </span>
                            </li>
                            @permission('view-competition,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.competitions.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.competitions.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.competition.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-competition,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.competitions.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.competitions.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.competition.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.createCompetitionFootballApi') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.createCompetitionFootballApi')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.competition.create2')}}</span>
                                </a>
                            </li>
                            @endpermission

                        </ul>
                    </div>
                </li>
                {{-- End competitions--}}
                @endrole

                @role('admin,team')
                {{-- Start teams--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.teams.index') || url()->current() == route('dashboard.teams.create')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                       <span class="svg-icon menu-icon">
                            <i class="fas fa-users icon-md text-primary"></i>
                        </span>
                        <span class="menu-text">{{__('site.team.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.team.show')}}</span>
                                </span>
                            </li>
                            @permission('view-team,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.teams.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.teams.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.team.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-team,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.teams.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.teams.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.team.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End teams--}}
                @endrole
                @role('admin,player')
                {{-- Start players--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.players.index') || url()->current() == route('dashboard.players.create')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <i class="fas fa-users-cog icon-md text-primary"></i>
                        </span>
                        <span class="menu-text">{{__('site.player.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.player.show')}}</span>
                                </span>
                            </li>
                            @permission('view-player,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.players.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.players.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.player.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-player,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.players.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.players.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.player.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End players--}}
                @endrole
                @role('admin,match')
                {{-- Start matches--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.matches.index') || url()->current() == route('dashboard.matches.create')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <i class="icon-xl la la-volleyball-ball text-primary"></i>
                        </span>
                        <span class="menu-text">{{__('site.match.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">
                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.match.show')}}</span>
                                </span>
                            </li>
                            @permission('view-match,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.matches.index') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.matches.index')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.match.all')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('create-match,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.matches.create') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.matches.create')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.match.create')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End matches--}}
                @endrole

                @role('admin,notification')
                {{-- Start notifications--}}
                <li class="menu-item menu-item-submenu  {{ url()->current() == route('dashboard.notifications.url') || url()->current() == route('dashboard.notifications.match') || url()->current() == route('dashboard.notifications.post') || url()->current() == route('dashboard.notifications.competition') || url()->current() == route('dashboard.notifications.team')
                    ? 'menu-item-open' : ''}}" aria-haspopup="true" data-menu-toggle="hover">
                    <a href="javascript:;" class="menu-link menu-toggle">
                        <span class="svg-icon menu-icon">
                            <i class="flaticon2-bell-4 text-primary"></i>
                        </span>
                        <span class="menu-text">{{__('site.notification.show')}}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="menu-submenu">
                        <i class="menu-arrow"></i>
                        <ul class="menu-subnav">

                            <li class="menu-item menu-item-parent" aria-haspopup="true">
                                <span class="menu-link">
                                    <span class="menu-text">{{__('site.notification.show')}}</span>
                                </span>
                            </li>
                            @permission('post,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.notifications.post') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.notifications.post')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.notification.post')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('match,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.notifications.match') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.notifications.match')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.notification.match')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('url,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.notifications.url') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.notifications.url')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.notification.url')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('team,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.notifications.team') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.notifications.team')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.notification.team')}}</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('competition,full-permissions')
                            <li class="menu-item {{url()->current() == route('dashboard.notifications.competition') ? 'menu-item-active' : ''}}" aria-haspopup="true">
                                <a href="{{route('dashboard.notifications.competition')}}" class="menu-link">
                                    <i class="menu-bullet menu-bullet-line">
                                        <span></span>
                                    </i>
                                    <span class="menu-text">{{__('site.notification.competition')}}</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                {{-- End notifications--}}
                @endrole

                @role('admin,client')
                {{-- Start clients--}}
                <li class="menu-item {{url()->current() == route('dashboard.clients.index') ?'menu-item-active' : ''}}" aria-haspopup="true">
                    @permission('view-client,full-permissions')
                    <a href="{{route('dashboard.clients.index')}}" class="menu-link">
                        <span class="svg-icon menu-icon">
                            <!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\themes\metronic\theme\html\demo1\dist/../src/media/svg/icons\Shopping\clientss.svg-->
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect opacity="0.200000003" x="0" y="0" width="24" height="24"/>
                                    <path d="M4.5,7 L9.5,7 C10.3284271,7 11,7.67157288 11,8.5 C11,9.32842712 10.3284271,10 9.5,10 L4.5,10 C3.67157288,10 3,9.32842712 3,8.5 C3,7.67157288 3.67157288,7 4.5,7 Z M13.5,15 L18.5,15 C19.3284271,15 20,15.6715729 20,16.5 C20,17.3284271 19.3284271,18 18.5,18 L13.5,18 C12.6715729,18 12,17.3284271 12,16.5 C12,15.6715729 12.6715729,15 13.5,15 Z" fill="#000000" opacity="0.3"/>
                                    <path d="M17,11 C15.3431458,11 14,9.65685425 14,8 C14,6.34314575 15.3431458,5 17,5 C18.6568542,5 20,6.34314575 20,8 C20,9.65685425 18.6568542,11 17,11 Z M6,19 C4.34314575,19 3,17.6568542 3,16 C3,14.3431458 4.34314575,13 6,13 C7.65685425,13 9,14.3431458 9,16 C9,17.6568542 7.65685425,19 6,19 Z" fill="#000000"/>
                                </g>
                            </svg><!--end::Svg Icon-->
                        </span>
                        <span class="menu-text">{{__('site.client.show')}}</span>
                    </a>
                    @endpermission
                </li>
                {{-- End clients--}}
                @endrole
                @role('admin')
                {{-- Start Setting--}}
                <li class="menu-item {{url()->current() == route('dashboard.settings.index') ?'menu-item-active' : ''}}" aria-haspopup="true">
                    <a href="{{route('dashboard.settings.index')}}" class="menu-link">
                        <span class="svg-icon menu-icon">
                            <!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\themes\metronic\theme\html\demo1\dist/../src/media/svg/icons\Shopping\Settings.svg-->
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect opacity="0.200000003" x="0" y="0" width="24" height="24"/>
                                    <path d="M4.5,7 L9.5,7 C10.3284271,7 11,7.67157288 11,8.5 C11,9.32842712 10.3284271,10 9.5,10 L4.5,10 C3.67157288,10 3,9.32842712 3,8.5 C3,7.67157288 3.67157288,7 4.5,7 Z M13.5,15 L18.5,15 C19.3284271,15 20,15.6715729 20,16.5 C20,17.3284271 19.3284271,18 18.5,18 L13.5,18 C12.6715729,18 12,17.3284271 12,16.5 C12,15.6715729 12.6715729,15 13.5,15 Z" fill="#000000" opacity="0.3"/>
                                    <path d="M17,11 C15.3431458,11 14,9.65685425 14,8 C14,6.34314575 15.3431458,5 17,5 C18.6568542,5 20,6.34314575 20,8 C20,9.65685425 18.6568542,11 17,11 Z M6,19 C4.34314575,19 3,17.6568542 3,16 C3,14.3431458 4.34314575,13 6,13 C7.65685425,13 9,14.3431458 9,16 C9,17.6568542 7.65685425,19 6,19 Z" fill="#000000"/>
                                </g>
                            </svg><!--end::Svg Icon-->
                        </span>
                        <span class="menu-text">{{__('site.global.settings')}}</span>
                    </a>
                </li>
                {{-- End Setting--}}
                @endrole
            </ul>
            <!--end::Menu Nav-->
        </div>
        <!--end::Menu Container-->
    </div>
    <!--end::Aside Menu-->
</div>
<!--end::Aside-->
