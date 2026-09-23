<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public  function index(){
       
        $page_title = __('site.global.dashboard');
        $page_description = 'Some description for the page';
        return view('dashboard.welcome',compact('page_title','page_description'));
    }
}
