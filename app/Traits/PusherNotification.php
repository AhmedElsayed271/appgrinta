<?php
namespace App\Traits;
trait PusherNotification{
    public  function connect()//: \Pusher\PushNotifications\PushNotifications
    {
//        return new \Pusher\PushNotifications\PushNotifications(array(
//            "instanceId" => env('PUSHER_BEAMS_INSTANCE_ID'),
//            "secretKey" => env('PUSHER_BEAMS_SECRET_KEY'),
//        ));
    }
}
