<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Post;
use App\Models\Client;
use App\Models\Matche;
use App\Traits\Notify;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Resources\MatchResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\CompetitionResource;
use App\Models\Competition;
use App\Models\Guest;
use App\Models\Team;

class NotificationsController extends Controller
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
        $notify = $this->flattenNotify(['post_id' => $request->input('post_id'), 'type' => 'post']);

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
        $page_title = __('site.notification.match');
        $competitions = \App\Models\Competition::with('translations')->orderBy('id', 'desc')->get();
        return view('dashboard.notifications.match', compact('page_title', 'competitions'));
    }

    public function matchSearch(Request $request)
    {
        $query = Matche::with([
            'team1.translations',
            'team2.translations',
            'competition.translations',
            'translations',
            'events' => fn($q) => $q->whereIn('status', ['goal', 'goal-normal goal', 'goal-penalty', 'goal-own goal'])
                ->select('id', 'match_id', 'team_id', 'status'),
        ]);

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->whereHas('team1.translations', fn($t) => $t->where('name', 'like', '%' . $q . '%'))
                    ->orWhereHas('team2.translations', fn($t) => $t->where('name', 'like', '%' . $q . '%'))
                    ->orWhereHas('competition.translations', fn($t) => $t->where('name', 'like', '%' . $q . '%'));
            });
        }

        if ($request->filled('competition_id')) {
            $query->where('competition_id', $request->competition_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'upcoming') {
                $query->whereIn('status', ['NS', 'TBD'])
                    ->where(function ($q) {
                        $q->whereNull('match_date')
                            ->orWhere('match_date', '>=', Carbon::now());
                    });
            } elseif ($request->status === 'finished') {
                $query->whereIn('status', ['FT', 'AET', 'PEN'])
                    ->where(function ($q) {
                        $q->whereNull('match_date')
                            ->orWhere('match_date', '<=', Carbon::now());
                    });
            } elseif ($request->status === 'live') {
                $query->whereIn('status', ['1H', 'HT', '2H', 'ET', 'P']);
            } elseif ($request->status === 'postponed') {
                $query->whereIn('status', ['PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO']);
            } else {
                $query->whereIn('status', [$request->status]);
            }
        } else {
            $query->where(function ($q) {
                $q->whereIn('status', ['1H', 'HT', '2H', 'ET', 'P', 'PEN'])
                    ->orWhere(function ($q2) {
                        $q2->whereIn('status', ['NS', 'TBD'])
                            ->where(function ($qq) {
                                $qq->whereNull('match_date')->orWhere('match_date', '>=', Carbon::now());
                            });
                    })
                    ->orWhere(function ($q3) {
                        $q3->whereIn('status', ['FT', 'AET', 'PEN'])
                            ->where(function ($qq) {
                                $qq->whereNull('match_date')->orWhere('match_date', '<=', Carbon::now()->subMinutes(5));
                            });
                    })
                    ->orWhereIn('status', ['PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO']);
            });
        }

        if ($request->filled('date_filter')) {
            match ($request->date_filter) {
                'today'     => $query->whereDate('match_date', Carbon::today()),
                'yesterday' => $query->whereDate('match_date', Carbon::yesterday()),
                'last7'     => $query->where('match_date', '>=', Carbon::now()->subDays(7)),
                'last30'    => $query->where('match_date', '>=', Carbon::now()->subDays(30)),
                default     => null,
            };
        }



        $statusFilter = $request->input('status');
        if ($statusFilter === 'upcoming') {
            $query->orderBy('match_date', 'asc');
        } else {
            $query->orderByDesc('match_date');
        }

        $matches = $query->limit(30)->get();

        $results = $matches->map(function ($m) {
            $locale = app()->getLocale();

            $team1Name = $m->team1?->translate($locale)?->name ?? $m->team1?->translate('en')?->name ?? '';
            $team2Name = $m->team2?->translate($locale)?->name ?? $m->team2?->translate('en')?->name ?? '';
            $compName  = $m->competition?->translate($locale)?->name ?? $m->competition?->translate('en')?->name ?? '';

            $group = $m->translate($locale)?->group ?? $m->translate('ar')?->group ?? $m->translate('en')?->group;

            $score = null;
            if (in_array($m->status, ['FT', 'AET', 'PEN'])) {
                $goalStatuses = ['goal', 'goal-normal goal', 'goal-penalty'];
                $homeGoals = 0;
                $awayGoals = 0;
                if ($m->relationLoaded('events')) {
                    foreach ($m->events as $e) {
                        if (in_array($e->status, $goalStatuses) && $e->team_id == $m->team1_id) {
                            $homeGoals++;
                        } elseif ($e->status === 'goal-own goal' && $e->team_id == $m->team2_id) {
                            $homeGoals++;
                        } elseif (in_array($e->status, $goalStatuses) && $e->team_id == $m->team2_id) {
                            $awayGoals++;
                        } elseif ($e->status === 'goal-own goal' && $e->team_id == $m->team1_id) {
                            $awayGoals++;
                        }
                    }
                }
                $score = $homeGoals . '-' . $awayGoals;
            }

            return [
                'id'          => $m->id,
                'text'        => $team1Name . ' vs ' . $team2Name . ($score ? ' (' . $score . ')' : ''),
                'team1'       => $team1Name,
                'team2'       => $team2Name,
                'competition' => $compName,
                'match_date'  => $m->match_date ? Carbon::parse($m->match_date)->format('Y-m-d H:i') : null,
                'week'        => $m->week,
                'group'       => $group,
                'status'      => $m->status,
                'score'       => $score,
                'home_goals'  => $score !== null ? explode('-', $score)[0] : null,
                'away_goals'  => $score !== null ? explode('-', $score)[1] : null,
            ];
        });

        return response()->json(['results' => $results, 'count' => $results->count()]);
    }

    public function match_post(Request $request)
    {
        $request->validate([
            'match_id' => ['required', 'exists:matches,id'],
            'ar.name'  => ['required'],
            'en.name'  => ['required'],
        ]);

        $image = $this->uploadImage($request);
        $match = Matche::with([
            'competition',
            'team1',
            'team2',
        ])->findOrFail($request->input('match_id'));

        $notify = $this->flattenNotify([
            'match_id' => $request->input('match_id'),
            'type'     => 'match_status',
            'match'    => new MatchResource($match),
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
        $notify = $this->flattenNotify(['url' => $request->input('url', ''), 'type' => 'announcement']);

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

        $this->topicNotifyByFirebaseTokens($this->englishTokens(), [
            'title'  => $request->input('en.name'),
            'body'   => strip_tags($request->input('en.description')),
            'image'  => $image,
            'notify' => $this->flattenNotify([
                'team_id' => $team->team_id,
                'type'    => 'team',
                'screen'  => $request->input('screen'),
                'team'    => new TeamResource($team),
            ]),
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $this->flattenNotify([
                'team_id' => $team->team_id,
                'team_ar' => $team->translate('ar')->name,
                'type'    => 'team',
                'screen'  => $request->input('screen'),
                'team'    => new TeamResource($team),
            ]),
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
                'type'           => 'league',
                'screen'         => $request->input('screen'),
                'competition'    => new CompetitionResource($competition),
            ]),
        ]);

        $this->topicNotifyByFirebaseTokens($this->arabicTokens(), [
            'title'  => $request->input('ar.name'),
            'body'   => strip_tags($request->input('ar.description')),
            'image'  => $image,
            'notify' => $this->flattenNotify([
                'competition_id' => $request->input('competition_id'),
                'leg_ar'         => $competition->translate('ar')->name,
                'type'           => 'league',
                'screen'         => $request->input('screen'),
                'competition'    => new CompetitionResource($competition),
            ]),
        ]);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->back();
    }
}
