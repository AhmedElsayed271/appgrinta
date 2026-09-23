@extends('layouts.dashboard.auth.app')
@section('content')
    <!--begin::Signin-->
    <div class="login-form login-signin">
        <h1>{{$clientName}}! Your Code is below</h1>
        <h3>{{$token}}</h3>
    </div>
@endsection
