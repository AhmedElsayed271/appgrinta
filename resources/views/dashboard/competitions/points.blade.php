{{-- Extends layout --}}
@extends('layouts.dashboard.default')

{{-- Content --}}
@section('content')
    <div class="card card-custom">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">{{__('site.competition.points')}}
                    {{--                    <div class="text-muted pt-2 font-size-sm">Datatable initialized from HTML table</div>--}}
                </h3>
            </div>

        </div>

        <div class="card-body">
            <table class="table table-responsive">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">{{ __('Logo') }}</th>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Win') }}</th>
                    <th scope="col">{{ __('Draw') }}</th>
                    <th scope="col">{{ __('Loss') }}</th>
                    <th scope="col">{{ __('Goals for') }}</th>
                    <th scope="col">{{ __('Against') }}</th>
                    <th scope="col">{{ __('Differance') }}</th>
                    <th scope="col">{{ __('Points') }}</th>
                    <th scope="col">{{ __('Matches') }}</th>
                    <th scope="col">{{ __('Sort') }}</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $key=1;
                @endphp
                @foreach($collection as $value)
                    <tr>
                        <th scope="row">{{$key++}}</th>
                        <th><img src="{{$value['logo']}}" width="50" height="50" alt=""></th>
                        <th>{{$value['name']}}</th>
                        <th>{{$value['sum_win']}}</th>
                        <th>{{$value['sum_draw']}}</th>
                        <th>{{$value['sum_loss']}}</th>
                        <th>{{$value['sum_goals_for']}}</th>
                        <th>{{$value['sum_goals_against']}}</th>
                        <th>{{$value['differance_goals']}}</th>
                        <th>{{$value['points']}}</th>
                        <th>{{ intval($value['sum_win']) + intval($value['sum_draw']) + intval($value['sum_loss']) }}</th>
                        <th><input type="text" name="sort" class="form-control sort" value="{{ $value['sort'] }}" data-id="{{ $value['id'] }}"></th>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        $('.sort').change(function (e) {
            e.preventDefault();

            var id = $(this).data("id");
            var value = this.value;

            $.ajax({
                type: "POST",
                url: "{{ route('dashboard.competitions.changeSort') }}",
                data:{
                    _token:'{{csrf_token()}}',
                    'id' : id,
                    'value' : value,
                },

            });

        });

    </script>
@endsection
