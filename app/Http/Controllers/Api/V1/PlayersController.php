<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\PlayerStatisticResource;
use App\Models\Player;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PlayersController extends Controller
{
    use ApiResponser;
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->showAll(PlayerResource::collection(Player::all())->collection);
    }
    public function show($id): \Illuminate\Http\JsonResponse
    {
         $player=Player::query()->where('player_id',$id)->first();
        if($player == null ){
            $player=Player::query()->findOrFail($id);
        }
        return $this->successResponse(new PlayerResource($player),200);
    }
    public function allpl(Request $request)
    {
        $request->validate([
            'players' => 'required'
        ]);
        $playersIds = explode(',', $request->players);
        // return $teamsIds;
        return $this->successResponse(PlayerResource::collection(Player::whereIn('player_id', $playersIds)->get())->collection, 200);
    }

    public function statistic($id): \Illuminate\Http\JsonResponse
    {
        $player=Player::query()->where('player_id',$id)->first();
        if($player == null ){
            $player=Player::query()->findOrFail($id);
        }
        return $this->successResponse($player->statistic?new PlayerStatisticResource($player->statistic):null,200);
    }
}
