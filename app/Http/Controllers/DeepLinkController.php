<?php

namespace App\Http\Controllers;

use App\Services\DeepLinkService;
use Illuminate\Contracts\View\View;

class DeepLinkController extends Controller
{
    public function landing(string $code, DeepLinkService $service): View
    {
        $payload = $service->resolve($code);

        abort_if(!$payload, 404);

        return view('deeplinks.landing', $service->viewDataForLink($payload));
    }

    public function post(int $post, DeepLinkService $service): View
    {
        return $this->share('post', $post, $service);
    }

    public function league(int $league, DeepLinkService $service): View
    {
        return $this->share('league', $league, $service);
    }

    public function match(int $match, DeepLinkService $service): View
    {
        return $this->share('match', $match, $service);
    }

    public function team(int $team, DeepLinkService $service): View
    {
        return $this->share('team', $team, $service);
    }

    public function screen(int $screen, DeepLinkService $service): View
    {
        $data = $service->viewDataForTarget(DeepLinkService::SCREEN_TYPE, $screen);

        abort_if(!$data, 404);

        return view('deeplinks.landing', $data);
    }

    private function share(string $type, int $id, DeepLinkService $service): View
    {
        $target = $service->target($type, $id);

        abort_if(!$target, 404);

        return view('deeplinks.landing', $service->viewDataForTarget($type, $id, $target));
    }
}
