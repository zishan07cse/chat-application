<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class UserController extends Controller
{
    public function updateOrderFiles(Request $request){
        $order_id = request('order_id');
        
        $images = [];
        foreach($request->file('documents') as $image){
            $name=$image->getClientOriginalName();
            $ext = strtolower($image->getClientOriginalExtension());
            $directory = public_path().'/files/images'; 
            $fileName = round(microtime(true)) .rand(2,50). '.' .$ext;
            $image ->move($directory, $fileName);
            $path ="public/files/images/". $fileName;
            array_push($images , $path);
        }
        $record = DB::table('orders')->where('id',$order_id)->update(['attached_files'=>json_encode($images)]);
        return $record;
    }
}
