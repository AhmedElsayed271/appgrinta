@extends('layouts.dashboard.auth.app')
@section('title', 'Grinta')
@section('content')
    <!--begin::Signin-->
    <div class="login-form login-signin">
        <h1 {{$clientName}} @if($local == 'ar') align="right" @else align="left" @endif>{{$clientName}} @if($local == 'ar') شكرا لك علي الاشتراك في تطبيق جرينتا. @else Thank you for your subscription . @endif</h1>
        <h1>{{$clientName}} @if($local == 'ar')  فضلا استخدم الكود التالي لتفعيل حسابك: @else Please use the below code to activate your account: @endif</h1>
        <h1 style="color:red">{{$token}}</h1>
        
        <h2>{{$clientName}} @if($local == 'ar') فريق عمل جرينتا. @else Grinta Team. @endif</h2>
    </div>
@endsection
