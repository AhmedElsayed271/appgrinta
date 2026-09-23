<?php
namespace App\Classes;
use Illuminate\Pagination\AbstractPaginator;

class CustomResponseToShow{

    public $isSuccess ;
    public $message ;
    public $additionalInfo ;
    public $data ;
    public $error;
    public $code;
    public function __construct($isSuccess, $message, $additionalInfo, $data, $error, $code)
    {
        $this->isSuccess=$isSuccess;
        $this->message=$message;
        $this->additionalInfo=$additionalInfo;
        $this->data=$data;
        $this->error=$error;
        $this->code=$code;
    }
}
