<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Post;
use App\Models\Client;
use App\Models\Matche;
use App\Traits\Notify;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Resources\MatchResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\CompetitionResource;
use App\Models\Competition;
use App\Models\Guest;
use App\Models\Team;

class NotificationController extends Controller
{
    use Notify;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Get all unique English + guest tokens.
     */
    private function englishTokens(): array
    {
        return array_filter(array_unique(array_merge(
            Client::where('locale', 'en')->pluck('fb_token')->toArray(),
            Guest::tokensEn()
        )));
    }

    /**
     * Get all unique Arabic + guest tokens.
     */
    private function arabicTokens(): array
    {
        return array_filter(array_unique(array_merge(
            Client::where('locale', 'ar')->pluck('fb_token')->toArray(),
            Guest::tokensAr()
        )));
    }

    /**
     * Handle image upload and return public URL or empty string.
     */
    private function uploadImage(Request $request): string
    {
        if (!$request->hasFile('image')) {
            return '';
        }

        $image    = $request->file('image');
        $filename = time() . '_' . $image->hashName();
        Storage::disk('public')->makeDirectory('uploads/manual_notifications_images');
        $image->storeAs('public/uploads/manual_notifications_images/', $filename);

        return asset('storage/uploads/manual_notifications_images/' . $filename);
    }

    /**
     * Flatten a notify array — convert nested resources/objects to strings,
     * keeping only scalar values as FCM data payload requires string values.
     */
    private function flattenNotify(array $notify): array
    {
        $flat = [];
        foreach ($notify as $key => $value) {
            if (is_string($value) || is_int($value) || is_float($value)) {
                $flat[$key] = (string) $value;
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                // Flatten resource objects one level deep as JSON string
                $flat[$key] = json_encode($value->toArray(request()));
            } elseif (is_array($value)) {
                $flat[$key] = json_encode($value);
            }
        }
        return $flat;
    }

    // ─── Post Notification ──────────────────────────────────────────────────────

    public function post()
    {
        $posts      = Post::all()->pluck('name', 'id');
        $page_title = __('site.notification.post');
        return view('dashboard.notifications.post', compact('page_title', 'posts'));
    }

    public function post_post(Request $request)
    {
        $request->validate([
            'post_id'  => ['required', 'exists:posts,id'],
            'ar.name'  => ['required'],
            'en.name'  => ['required'],
        ]);

        $image  = $this->uploadImage($request);
        $notify = $this->flattenNotify(['post_id' => $request->input('post_id')]);

        $this->topicNotifyByFirebaseTokens($this->englishTokens(), [
            'title'  => $request->input('en.name'),
            'body'   => strip_tags($request->input('en.description')),
            'image'  => $image,
            'notify' => $notify,
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $notify,
        ]);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->back();
    }

    // ─── Match Notification ─────────────────────────────────────────────────────

    public function match()
    {
        $matches    = Matche::with(['team1', 'team2', 'competition'])->get();
        $page_title = __('site.notification.match');
        return view('dashboard.notifications.match', compact('page_title', 'matches'));
    }

    public function match_post(Request $request)
    {
        $request->validate([
            'match_id' => ['required', 'exists:matches,id'],
            'ar.name'  => ['required'],
            'en.name'  => ['required'],
        ]);

        $image = $this->uploadImage($request);
        $match = Matche::findOrFail($request->input('match_id'));

        $notify = $this->flattenNotify([
            'match_id' => $request->input('match_id'),
        ]);

        $this->topicNotifyByFirebaseTokens($this->englishTokens(), [
            'title'  => $request->input('en.name'),
            'body'   => strip_tags($request->input('en.description')),
            'image'  => $image,
            'notify' => $notify,
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $notify,
        ]);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->back();
    }

    // ─── URL Notification ───────────────────────────────────────────────────────

    public function url()
    {
        $page_title = __('site.notification.url');
        return view('dashboard.notifications.url', compact('page_title'));
    }

    public function url_post(Request $request)
    {
        $request->validate([
            'ar.name' => ['required'],
            'en.name' => ['required'],
        ]);

        $image  = $this->uploadImage($request);
        $notify = $this->flattenNotify(['url' => $request->input('url', '')]);

        $this->topicNotifyByFirebaseTokens($this->englishTokens(), [
            'title'  => $request->input('en.name'),
            'body'   => strip_tags($request->input('en.description')),
            'image'  => $image,
            'notify' => $notify,
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $notify,
        ]);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->back();
    }

    // ─── Team Notification ──────────────────────────────────────────────────────

    public function team()
    {
        $countries  = Country::all()->pluck('name', 'id')->toArray();
        $page_title = __('site.notification.team');
        return view('dashboard.notifications.team', compact('page_title', 'countries'));
    }

    public function team_post(Request $request)
    {
        $request->validate([
            'team_id' => ['required'],
            'screen'  => ['required'],
            'ar.name' => ['required'],
            'en.name' => ['required'],
        ]);

        $image = $this->uploadImage($request);
        $team  = Team::findOrFail($request->input('team_id'));

        $notifyPayload = $this->flattenNotify([
            'team_id' => (string) $team->team_id, 
            'screen'  => $request->input('screen'),
        ]);

        $notifyPayloadAr = $this->flattenNotify([
            'team_id' => (string) $team->team_id,
            'team_ar' => $team->translate('ar')->name,
            'screen'  => $request->input('screen'),
        ]);

        $this->topicNotifyByFirebaseTokens($this->englishTokens(), [
            'title'  => $request->input('en.name'),
            'body'   => strip_tags($request->input('en.description')),
            'image'  => $image,
            'notify' => $notifyPayload,
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $notifyPayloadAr,
        ]);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->back();
    }

    // ─── Competition Notification ───────────────────────────────────────────────

    public function competition()
    {
        $countries  = Country::all()->pluck('name', 'id')->toArray();
        $page_title = __('site.notification.competition');
        return view('dashboard.notifications.competition', compact('page_title', 'countries'));
    }

    public function competition_post(Request $request)
    {
        $request->validate([
            'competition_id' => ['required'],
            'screen'         => ['required'],
            'ar.name'        => ['required'],
            'en.name'        => ['required'],
        ]);

        $image       = $this->uploadImage($request);
        $competition = Competition::findOrFail($request->input('competition_id'));

        $this->topicNotifyByFirebaseTokens($this->englishTokens(), [
            'title'  => $request->input('en.name'),
            'body'   => strip_tags($request->input('en.description')),
            'image'  => $image,
            'notify' => $this->flattenNotify([
                'competition_id' => $request->input('competition_id'),
                'screen'         => $request->input('screen'),
            ]),
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $this->flattenNotify([
                'competition_id' => $request->input('competition_id'),
                'leg_ar'         => $competition->translate('ar')->name,
                'screen'         => $request->input('screen'),
            ]),
        ]);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->back();
    }
}
