<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    use ApiResponser;

    public function storeGuest(Request $request)
    {
        $data = $request->validate([
            'fb_token' => 'required|unique:guests,fb_token',
            'locale' => 'required',
            'name' => 'nullable',
        ]);

        $guest = Guest::create($data);

        return $this->successResponse($guest, 200);
    }
}
