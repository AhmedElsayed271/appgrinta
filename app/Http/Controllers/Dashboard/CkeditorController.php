<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

class CkeditorController extends Controller
{
    public function upload(Request $request)
    {
        if($request->hasFile('upload')) {
            $filenamewithextension= $request->file('upload')->getClientOriginalName();


            $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);


            $extension = $request->file('upload')->getClientOriginalExtension();


            $filenametostore = $filename.'_'.time().'.'.$extension;


            $request->file('upload')->storeAs('public/uploads/ckeditor/', $filenametostore);



            echo json_encode([
                'default' => asset('storage/uploads/ckeditor/'.$filenametostore),
                '500' => asset('storage/uploads/ckeditor/'.$filenametostore)
            ]);
        }
    }
    public function delete(Request $request){
        if ($request->input('src')){
            $src=$request->input('src');
            $path=parse_url($src, PHP_URL_PATH);
            if ($path !== null && strpos($path,'/storage') === 0){
                $path=substr($path,strlen('/storage'));
            }
            Storage::disk('public')->delete(ltrim($path,'/'));
            return ltrim($path,'/');
        }
    }
}
