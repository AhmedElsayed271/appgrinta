<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchEventPaginationResource;
use App\Http\Resources\MatchPaginationResource;
use App\Http\Resources\MatchResource;
use App\Models\Matche;
use App\Models\Setting;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MatchesController extends Controller
{
    use ApiResponser;

    public function index(): \Illuminate\Http\JsonResponse
    {
        $match_date = request()->input('match_date');
        $timezone   = request()->input('timezone')
            ?? optional(auth()->user())->timezone
            ?? config('app.timezone');

        $order = request()->input('order', 'desc');

        $data = Matche::query()
            ->when($match_date, function ($builder) use ($match_date, $timezone) {
                $array = [15,16,17,18,19,20,21,22,23,24,25,26,27,121,122,123];

                // ── Convert client date to UTC range for DB query ──────
                $startOfDay = Carbon::createFromFormat('Y-m-d', $match_date, $timezone)
                    ->startOfDay()
                    ->setTimezone('UTC');

                $endOfDay = Carbon::createFromFormat('Y-m-d', $match_date, $timezone)
                    ->endOfDay()
                    ->setTimezone('UTC');

                return $builder
                    ->whereBetween('match_date', [$startOfDay, $endOfDay])
                    ->whereIn('competition_id', $array);
            })
            ->with('competition.parent')
            ->orderBy('match_date', strtoupper($order));

        $result = $data->get()->each(function ($builder) use ($timezone) {
            // ── Convert match time to client timezone ──────────────────
            $builder->match_date = Carbon::parse($builder->match_date)
                ->setTimezone($timezone)
                ->toDateTimeString();

            // ── Goal counts ────────────────────────────────────────────
            $builder->home_goals = $builder->events()->getQuery()
                ->where('status', 'goal')
                ->where('team_id', $builder->team1_id)
                ->count();

            $builder->away_goals = $builder->events()->getQuery()
                ->where('status', 'goal')
                ->where('team_id', $builder->team2_id)
                ->count();
        });

        return $this->showAll(MatchResource::collection($result)->collection);
    }

    public function show(Matche $match): \Illuminate\Http\JsonResponse
    {
        $timezone = request()->input('timezone')
            ?? optional(auth()->user())->timezone
            ?? config('app.timezone');

        $match = $match->load('competition');

        // ── Convert match time to client timezone ──────────────────────
        $match->match_date = Carbon::parse($match->match_date)
            ->setTimezone($timezone)
            ->toDateTimeString();

        $match->home_goals = $match->events()->getQuery()
            ->where('status', 'goal')
            ->where('team_id', $match->team1_id)
            ->count();

        $match->away_goals = $match->events()->getQuery()
            ->where('status', 'goal')
            ->where('team_id', $match->team2_id)
            ->count();

        return $this->successResponse(new MatchResource($match), 200);
    }

    public function events(Matche $match): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(
            MatchEventPaginationResource::make(
                $this->showAllPagination(
                    $match->events()->with(['player', 'team', 'enter.player'])->get()
                )
            ),
            200
        );
    }
}