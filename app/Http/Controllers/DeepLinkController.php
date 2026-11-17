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
        $target = $service->target('post', $post);

        abort_if(!$target, 404);

        return view('deeplinks.landing', $service->viewDataForTarget('post', $target));
    }

    public function league(int $league, DeepLinkService $service): View
    {
        $target = $service->target('league', $league);

        abort_if(!$target, 404);

        return view('deeplinks.landing', $service->viewDataForTarget('league', $target));
    }
}
