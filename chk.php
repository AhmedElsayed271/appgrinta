<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
echo App\Models\Matche::whereIn('status',['NS','TBD'])->count().PHP_EOL;

