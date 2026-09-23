{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')

    {{-- Dashboard 1 --}}

    <div class="row">
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-danger gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                            <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\Category::query()->count()}}</div>
                            <a href="{{ route('dashboard.categories.index', ['locale' => app()->getLocale()]) }}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.category.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-success gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                    <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\Post::query()->count()}}</div>
                            <a href="{{ route('dashboard.posts.index', ['locale' => app()->getLocale()]) }}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.post.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-warning gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\User::query()->count()}}</div>
                            <a href="{{route('dashboard.users.index', ['locale' => app()->getLocale()])}}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.user.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-info gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\Country::query()->count()}}</div>
                            <a href="{{route('dashboard.countries.index', ['locale' => app()->getLocale()])}}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.country.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-primary gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\Competition::query()->count()}}</div>
                            <a href="{{route('dashboard.competitions.index', ['locale' => app()->getLocale()])}}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.competition.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-danger gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\Team::query()->count()}}</div>
                            <a href="{{route('dashboard.teams.index', ['locale' => app()->getLocale()])}}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.team.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xxl-4">
            <div class="row">
                <div class="col-xl-12">
                    <!--begin::Tiles Widget 11-->
                    <div class="card card-custom bg-dark gutter-b" style="height: 140px">
                        <div class="card-body">
                            <span class="svg-icon svg-icon-3x svg-icon-white ml-n2">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Layout/Layout-4-blocks.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"></rect>
                                        <rect fill="#000000" x="4" y="4" width="7" height="7" rx="1.5"></rect>
                                        <path d="M5.5,13 L9.5,13 C10.3284271,13 11,13.6715729 11,14.5 L11,18.5 C11,19.3284271 10.3284271,20 9.5,20 L5.5,20 C4.67157288,20 4,19.3284271 4,18.5 L4,14.5 C4,13.6715729 4.67157288,13 5.5,13 Z M14.5,4 L18.5,4 C19.3284271,4 20,4.67157288 20,5.5 L20,9.5 C20,10.3284271 19.3284271,11 18.5,11 L14.5,11 C13.6715729,11 13,10.3284271 13,9.5 L13,5.5 C13,4.67157288 13.6715729,4 14.5,4 Z M14.5,13 L18.5,13 C19.3284271,13 20,13.6715729 20,14.5 L20,18.5 C20,19.3284271 19.3284271,20 18.5,20 L14.5,20 C13.6715729,20 13,19.3284271 13,18.5 L13,14.5 C13,13.6715729 13.6715729,13 14.5,13 Z" fill="#000000" opacity="0.3"></path>
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>
                            <div class="text-inverse-success font-weight-bolder font-size-h2 mt-3">{{\App\Models\Player::query()->count()}}</div>
                            <a href="{{route('dashboard.players.index', ['locale' => app()->getLocale()])}}" class="text-inverse-success font-weight-bold font-size-lg mt-1">{{__('site.player.all')}}</a>
                        </div>
                    </div>
                    <!--end::Tiles Widget 11-->
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div>
                <div class="bg-danger mt-4 mb-3 p-3" style="display: inline-block; border-radius: 4px;">
                    <span style="color: white">
                        {{__('site.match.today')}}
                    </span>
                </div>
                @php
                    $float = \Illuminate\Support\Facades\Config::get('app.locale') == "ar" ?  'left' : 'right';
                @endphp
                <div class="mt-4 mb-3 py-3" style="float: {{$float}}">
                    <a href="{{route('dashboard.matches.index',['locale' => app()->getLocale()])}}" title="{{__('site.match.page_description')}}">{{__('site.match.all')}}</a>
                </div>
            </div>

            <table class="table table-bordered table-hover table-striped table-condensed flip-content" style="background-color: white; border-radius: 8px;">
                <thead >
                <tr >
                    <th>
                        {{__('site.match.id')}}
                    </th>
                    <th>
                        {{__('site.team.one')}}
                    </th>
                    <th>
                        {{__('site.team.two')}}
                    </th>
                    <th>
                        {{__('site.match.match_date')}}
                    </th>
                </tr>
                </thead>
                <tbody>
                @forelse (\App\Models\Matche::query()->whereDate('match_date', \Carbon\Carbon::now()->format('Y-m-d'))->with(['team1','team2','competition'])->get() as $key=>$value)
                    <tr>
                        <td>
                            {{$key+1}}
                        </td>
                        <td>
                            <img src="{{$value->competition->image_path}}" width="100" height="100" alt="">
                            {{$value->competition->name}}
                        </td>
                        <td>
                            <img src="{{$value->team1->image_path}}" width="100" height="100" alt="">
                            {{$value->team1->name}}
                        </td>
                        <td>
                            <img src="{{$value->team2->image_path}}" width="100" height="100" alt="">
                            {{$value->team2->name}}
                        </td>
                        <td>
                            {{\Carbon\Carbon::parse($value->match_date)->format('Y-m-d')}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            No Matches today
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div>
                <div class="bg-danger mt-4 mb-3 p-3" style="display: inline-block; border-radius: 4px;">
                    <span style="color: white">
                        {{__('site.match.tomorrow')}}
                    </span>
                </div>
                @php
                    $float = \Illuminate\Support\Facades\Config::get('app.locale') == "ar" ?  'left' : 'right';
                @endphp
                <div class="mt-4 mb-3 py-3" style="float: {{$float}}">
                    <a href="{{route('dashboard.matches.index',['locale' => app()->getLocale()])}}" title="{{__('site.match.page_description')}}">{{__('site.match.all')}}</a>
                </div>
            </div>

            <table class="table table-bordered table-hover table-striped table-condensed flip-content" style="background-color: white; border-radius: 8px;">
                <thead >
                <tr >
                    <th>
                        {{__('site.match.id')}}
                    </th>
                    <th>
                        {{__('site.team.one')}}
                    </th>
                    <th>
                        {{__('site.team.two')}}
                    </th>
                    <th>
                        {{__('site.match.match_date')}}
                    </th>
                </tr>
                </thead>
                <tbody>
                @forelse (\App\Models\Matche::query()->whereDate('match_date', \Carbon\Carbon::now()->addDay()->format('Y-m-d'))->with(['team1','team2','competition'])->get() as $key=>$value)
                    <tr>
                        <td>
                            {{$key+1}}
                        </td>
                        <td>
                            <img src="{{$value->competition->image_path}}" width="100" height="100" alt="">
                            {{$value->competition->name}}
                        </td>
                        <td>
                            <img src="{{$value->team1->image_path}}" width="100" height="100" alt="">
                            {{$value->team1->name}}
                        </td>
                        <td>
                            <img src="{{$value->team2->image_path}}" width="100" height="100" alt="">
                            {{$value->team2->name}}
                        </td>
                        <td>
                            {{\Carbon\Carbon::parse($value->match_date)->format('Y-m-d')}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            No Matches tomorrow
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div>
                <div class="bg-danger mt-4 mb-3 p-3" style="display: inline-block; border-radius: 4px;">
                    <span style="color: white">
                        {{__('site.match.yesterday')}}
                    </span>
                </div>
                @php
                    $float = \Illuminate\Support\Facades\Config::get('app.locale') == "ar" ?  'left' : 'right';
                @endphp
                <div class="mt-4 mb-3 py-3" style="float: {{$float}}">
                    <a href="{{route('dashboard.matches.index',['locale' => app()->getLocale()])}}" title="{{__('site.match.page_description')}}">{{__('site.match.all')}}</a>
                </div>
            </div>

            <table class="table table-bordered table-hover table-striped table-condensed flip-content" style="background-color: white; border-radius: 8px;">
                <thead >
                <tr >
                    <th>
                        {{__('site.match.id')}}
                    </th>
                    <th>
                        {{__('site.team.one')}}
                    </th>
                    <th>
                        {{__('site.team.two')}}
                    </th>
                    <th>
                        {{__('site.match.match_date')}}
                    </th>
                </tr>
                </thead>
                <tbody>
                @forelse (\App\Models\Matche::query()->whereDate('match_date', \Carbon\Carbon::now()->subDay()->format('Y-m-d'))->with(['team1','team2','competition'])->get() as $key=>$value)
                    <tr>
                        <td>
                            {{$key+1}}
                        </td>
                        <td>
                            <img src="{{$value->competition->image_path}}" width="100" height="100" alt="">
                            {{$value->competition->name}}
                        </td>
                        <td>
                            <img src="{{$value->team1->image_path}}" width="100" height="100" alt="">
                            {{$value->team1->name}}
                        </td>
                        <td>
                            <img src="{{$value->team2->image_path}}" width="100" height="100" alt="">
                            {{$value->team2->name}}
                        </td>
                        <td>
                            {{\Carbon\Carbon::parse($value->match_date)->format('Y-m-d')}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            No Matches yesterday
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

{{-- Scripts Section --}}
@section('scripts')
{{--    <script src="{{ asset('js/pages/widgets.js') }}?v=7.2.3" type="text/javascript"></script>--}}
@endsection
