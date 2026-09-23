{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.matchEvent.edit')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <form class="form" method="post" action="{{route('dashboard.matches.events.update',[$match->id,$matchEvent->id])}}" enctype="multipart/form-data">
                @csrf
                {{method_field('PUT')}}
                <div class="card-body">
                    <div class="form-group row">
                        <label for="minute" class="col-form-label col-lg-3 col-sm-12">{{__('site.matchEvent.minute')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <input type="text" name="minute" class="form-control @error('minute') is-invalid @enderror" id="minute" value="{{$matchEvent->minute}}" >
                            @error('minute')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
                        <div class="form-group">
                            <label for="{{$locale}}_description">{{__('site.'.$locale.'.description')}}</label>
                            <textarea name="{{$locale}}[description]" class="summernote" id="{{$locale}}_description">
                                {{$matchEvent->translate($locale)->description}}
                            </textarea>
                            @error($locale.'.description')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label for="team_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.team.name')}}</label>
                                <div class=" col-lg-9 col-md-9 col-sm-12">
                                    <select class="form-control select2" id="team_id" name="team_id">
                                        <option value=""></option>
                                        @foreach($teams as $team)
                                            <option value="{{$team->id}}" data-image="{{$team->image_path}}" {{$matchEvent->team_id == $team->id ? 'selected':''}}>{{$team->name}}</option>
                                        @endforeach
                                    </select>
                                    @error('team_id')
                                    <div class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label for="player_id" class="col-form-label col-lg-3 col-sm-12">{{__('site.player.name')}}</label>
                                <div class=" col-lg-9 col-md-9 col-sm-12">
                                    <select class="form-control select2" id="player_id" name="player_id">
                                        <option value=""></option>
                                    </select>
                                    @error('player_id')
                                    <div class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="player_name" class="col-form-label col-lg-3 col-sm-12">{{__('site.matchEvent.player_name')}}</label>
                        <div class="col-lg-4 col-md-9 col-sm-12">
                            <input type="text" name="player_name" class="form-control @error('player_name') is-invalid @enderror" id="player_name" value="{{$matchEvent->player_name}}" >
                            @error('player_name')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="status" class="col-form-label col-lg-3 col-sm-12">{{__('site.matchEvent.status')}}</label>
                        <div class=" col-lg-4 col-md-9 col-sm-12">
                            <select class="form-control select2" id="status" name="status">
                                <option value=""></option>
                                @foreach(\App\Models\MatchEvent::STATUS as $key=>$value)
                                    <option value="{{$value}}" {{$matchEvent->status == $value ? 'selected' : '' }}>{{$value}}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="separator separator-dashed my-8"></div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2">{{__('site.operation.edit')}}</button>
                    <a href="{{route('dashboard.matches.events.index',$match->id)}}" class="btn btn-light-success font-weight-bold">{{__('site.operation.back')}}</a>
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
                $('#player_id,[name=status]').select2({
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
                    return $(`<span> <img src="${image}" width="100" height="100" alt=""> ${state.text} </span>`);
                }
            }
            // Public functions
            return {
                init: function() {
                    demos();
                }
            };
        }();

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
            KTSummernoteDemo.init()
        });

        $(function (){
            var team_id = '{{$matchEvent->team_id}}'
            players(team_id)
            $(document).on('change','#team_id',function (e){
                players(e.target.value)
            })
        })

        function competitions(country){
            var competitions=`<option value=""></option>`;
            $.ajax({
                url:'{{route('dashboard.countries.index')}}/'+country,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        competitions += `<option value="${value.id}" >${value.name}</option>`
                    })
                    $('#competition_id').html(`
                            ${competitions}
                        `);
                }
            })
        }
        function teams(competition){
            var teams='<option value=""></option>';
            $.ajax({
                url:'{{route('dashboard.competitions.index')}}/'+competition,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        teams += `<option value="${value.id}" data-image="${value.image_path}">${value.name}</option>`
                    })
                    $('#team_id,#team2_id').html(`
                                ${teams}
                            `);
                }
            })
        }
        function players(team){
            var players='<option value=""></option>';
            $.ajax({
                url:'{{route('dashboard.teams.index')}}/'+team,
                data:{
                    _token:'{{csrf_token()}}'
                },
                success:function (data){
                    $.each(data,function (key,value){
                        players += `<option value="${value.id}" data-image="${value.image_path}"  ${ '{{$matchEvent->player_id}}' == value.id ? 'selected' : '' }>${value.first_name + ' ' + value.last_name}</option>`
                    })
                    $('#player_id').html(`${players}`);
                }
            })
        }
    </script>
@endsection
