<?php

namespace Tests\Feature;

use App\Console\Commands\SendNotificationLineupReady;
use App\Console\Commands\TheirdDegreeNotifications;
use App\Models\Competition;
use App\Models\Matche;
use App\Models\Setting;
use App\Models\Team;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * Integration coverage for the *active* scheduled senders that were previously
 * untested: third-degree (no API) match-started pushes, lineup-ready pushes,
 * the "favourite league round starts today" push, the live poller and the
 * end-of-match poller.
 *
 * Real Firebase is never reached: the in-app (database) persistence runs for
 * real, while the FCM dispatch is captured by Bus::fake(). Image compositing
 * (Intervention) is stubbed on the subclasses that call it directly.
 */
class ActiveSendersTest extends TestCase
{
    use DatabaseTransactions;

    private function matchWithTranslatedTeams(): Matche
    {
        $match = Matche::query()
            ->whereHas('team1.translations', fn ($q) => $q->where('locale', 'ar'))
            ->whereHas('team1.translations', fn ($q) => $q->where('locale', 'en'))
            ->whereHas('team2.translations', fn ($q) => $q->where('locale', 'ar'))
            ->whereHas('team2.translations', fn ($q) => $q->where('locale', 'en'))
            ->first();

        if (!$match) {
            $this->markTestSkipped('No match with fully translated teams in DB.');
        }

        return $match;
    }

    private function follower(int $teamId, string $locale = 'en', string $tz = 'Africa/Cairo'): int
    {
        $unique = uniqid('', true);
        $id = DB::table('clients')->insertGetId([
            'full_name'  => 'Follower ' . $locale,
            'email'      => $unique . '@example.test',
            'api_token'  => 'api_' . $unique,
            'fb_token'   => 'fcm_' . $unique,
            'locale'     => $locale,
            'timezone'   => $tz,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('favourite_team')->insert(['team_id' => $teamId, 'client_id' => $id]);

        return $id;
    }

    public function test_theird_send_persists_match_started_and_dedupes(): void
    {
        Bus::fake();

        $match = $this->matchWithTranslatedTeams();
        $match->update(['fixture_id' => null, 'match_date' => now(), 'status' => 'NS']);
        $client = $this->follower($match->team1_id, 'en');

        $cmd = new class extends TheirdDegreeNotifications {
            public function mergeImagesInOneImage($a, $b)
            {
                return '';
            }
        };
        $cmd->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput()));

        $cmd->handle();

        $row = DB::table('notifications')
            ->where('client_id', $client)
            ->where('type', 'match_status')
            ->first();

        $this->assertNotNull($row, 'third-degree match-started notification must be stored');
        $data = json_decode($row->data, true);
        $this->assertSame('match_started', $data['sub_type'] ?? null);

        $cmd->handle();

        $this->assertSame(
            1,
            DB::table('notifications')->where('client_id', $client)->where('type', 'match_status')->count(),
            'a second poll must not duplicate the push'
        );
    }

    public function test_lineup_ready_persists_reminder_and_dedupes(): void
    {
        Bus::fake();

        $match = $this->matchWithTranslatedTeams();
        if (!$match->fixture_id) {
            $match->update(['fixture_id' => random_int(10000000, 99999999)]);
        }
        $client = $this->follower($match->team1_id, 'en');

        Http::fake([
            '*/fixtures/lineups*' => Http::response(['results' => 2, 'response' => []], 200),
        ]);

        $cmd = new class extends SendNotificationLineupReady {
            public function mergeImagesInOneImage($a, $b)
            {
                return '';
            }

            public function callProcessMatch($setting, $match): void
            {
                $this->processMatch($setting, $match);
            }
        };

        $setting = Setting::find(1);

        $match->setAttribute('home_team_name_en', 'Home EN');
        $match->setAttribute('away_team_name_en', 'Away EN');
        $match->setAttribute('home_team_name_ar', 'هوم');
        $match->setAttribute('away_team_name_ar', 'اواي');
        $match->setAttribute('home_image', '');
        $match->setAttribute('away_image', '');

        $cmd->callProcessMatch($setting, $match);

        $row = DB::table('notifications')
            ->where('client_id', $client)
            ->where('type', 'reminder')
            ->first();

        $this->assertNotNull($row, 'lineup-ready reminder must be stored');
        $data = json_decode($row->data, true);
        $this->assertSame('2', $data['screen'] ?? null);
        $this->assertSame('ok', $data['lineup'] ?? null);

        $cmd->callProcessMatch($setting, $match);

        $this->assertSame(
            1,
            DB::table('notifications')->where('client_id', $client)->where('type', 'reminder')->count(),
            'lineup-ready must fire only once per fixture'
        );
    }

    public function test_favourite_league_persists_league_round_notification(): void
    {
        Bus::fake();

        $match = $this->matchWithTranslatedTeams();
        $competition = Competition::find($match->competition_id);

        if (!$competition || !$competition->translate('en') || !$competition->translate('ar')) {
            $this->markTestSkipped('Match competition lacks en/ar translations.');
        }

        $client = $this->follower($match->team1_id, 'en');

        DB::table('favourite_competition')->insert([
            'competition_id' => $competition->id,
            'client_id'      => $client,
        ]);

        // A round number that no real fixture of this competition uses, so the
        // "first match of the round" falls on today.
        $match->update(['week' => '987', 'status' => 'NS', 'match_date' => now()]);

        $today = now('Africa/Cairo')->format('Y-m-d');
        Artisan::call('notification:favourite_league', ['--date' => $today, '--hour' => 9]);

        $row = DB::table('notifications')
            ->where('client_id', $client)
            ->where('type', 'league')
            ->first();

        $this->assertNotNull($row, 'favourite-league round notification must be stored');
        $data = json_decode($row->data, true);
        $this->assertSame('1', $data['screen'] ?? null);
        $this->assertSame('987', $data['round'] ?? null);
    }

    /**
     * Regression: round numbers are REUSED across seasons/phases (e.g. Europa
     * League week=2 = qualifying round 2 in July and league-phase matchday 2 in
     * October). The "round starts today" guard used to look up the earliest match
     * of the round across ALL history, so a stale match from a previous phase made
     * the round look like it started long ago and the notification was never sent.
     */
    public function test_favourite_league_ignores_stale_same_round_match_from_other_phase(): void
    {
        Bus::fake();

        $match = $this->matchWithTranslatedTeams();
        $competition = Competition::find($match->competition_id);

        if (!$competition || !$competition->translate('en') || !$competition->translate('ar')) {
            $this->markTestSkipped('Match competition lacks en/ar translations.');
        }

        $client = $this->follower($match->team1_id, 'en', 'Africa/Cairo');

        DB::table('favourite_competition')->insert([
            'competition_id' => $competition->id,
            'client_id'      => $client,
        ]);

        // Same round number as today's round, but played 90 days ago — a different
        // phase/season reusing the round number. The 30-day lookback must ignore it.
        DB::table('matches')->insert([
            'fixture_id'     => random_int(10000000, 99999999),
            'team1_id'       => $match->team1_id,
            'team2_id'       => $match->team2_id,
            'competition_id' => $competition->id,
            'match_date'     => now()->subDays(90),
            'week'           => '4242',
            'status'         => 'FT',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Today's match opens the same round number.
        $match->update(['week' => '4242', 'status' => 'NS', 'match_date' => now()]);

        $today = now('Africa/Cairo')->format('Y-m-d');
        Artisan::call('notification:favourite_league', ['--date' => $today, '--hour' => 9]);

        $row = DB::table('notifications')
            ->where('client_id', $client)
            ->where('type', 'league')
            ->first();

        $this->assertNotNull(
            $row,
            'a stale match of the same round number must not block today\'s round notification'
        );
        $data = json_decode($row->data, true);
        $this->assertSame('1', $data['screen'] ?? null);
        $this->assertSame('4242', $data['round'] ?? null);
    }

    public function test_live_get_updates_match_and_persists_goal(): void
    {
        Bus::fake();

        $match = $this->matchWithTranslatedTeams();
        if (!$match->fixture_id) {
            $this->markTestSkipped('Match has no fixture_id.');
        }
        $match->update(['status' => 'NS', 'elapsed' => null]);

        $team = Team::find($match->team1_id);
        $this->follower($match->team1_id, 'en');

        $fixtureId = (int) $match->fixture_id;

        $liveFixture = [
            'fixture' => [
                'id'     => $fixtureId,
                'date'   => now()->toIso8601String(),
                'status' => ['short' => '1H', 'elapsed' => 23],
            ],
            'goals'  => ['home' => 1, 'away' => 0],
            'events' => [[
                'time'   => ['elapsed' => 20, 'extra' => null],
                'team'   => ['id' => $team->team_id, 'name' => 'Team'],
                'player' => ['id' => random_int(900000000, 999999999), 'name' => 'Scorer'],
                'type'   => 'Goal',
                'detail' => 'Normal Goal',
            ]],
        ];

        Http::fake(function ($request) use ($liveFixture) {
            if (str_contains($request->url(), 'live=')) {
                return Http::response(['response' => [$liveFixture]], 200);
            }

            return Http::response(['response' => []], 200);
        });

        Artisan::call('live:get');

        $fresh = Matche::find($match->id);
        $this->assertSame('1H', $fresh->status);
        $this->assertSame('23', (string) $fresh->elapsed);

        $this->assertNotEmpty(
            DB::table('notifications')->where('type', 'goal')->get(),
            'a live goal must produce an in-app goal notification'
        );
    }

    public function test_end_match_updates_finished_match_status(): void
    {
        $match = $this->matchWithTranslatedTeams();
        if (!$match->fixture_id) {
            $this->markTestSkipped('Match has no fixture_id.');
        }
        $match->update(['status' => 'NS', 'elapsed' => null]);

        $leagueId  = (int) explode(',', Setting::find(1)->leagues)[0];
        $fixtureId = (int) $match->fixture_id;

        Http::fake([
            '*' => Http::response(['response' => [[
                'league'  => ['id' => $leagueId],
                'fixture' => [
                    'id'     => $fixtureId,
                    'date'   => now()->toIso8601String(),
                    'status' => ['short' => 'FT', 'elapsed' => 90],
                ],
                'goals'   => ['home' => 2, 'away' => 1],
                'events'  => [],
            ]]], 200),
        ]);

        Artisan::call('end:match');

        $this->assertSame('FT', Matche::find($match->id)->status);
    }

    public function test_match_events_phase_sweep_persists_status_transitions(): void
    {
        Bus::fake();

        $match = $this->matchWithTranslatedTeams();
        if (!$match->fixture_id) {
            $match->update(['fixture_id' => random_int(10000000, 99999999)]);
        }
        $match->update([
            'status'  => 'NS',
            'elapsed' => null,
            'extra'   => null,
        ]);
        // `notificationSent` is not mass-assignable, so clear it via the query builder.
        DB::table('matches')->where('id', $match->id)->update(['notificationSent' => null]);

        $client   = $this->follower($match->team1_id, 'en');
        $leagueId = (int) explode(',', Setting::find(1)->leagues)[0];
        $fixtureId = (int) $match->fixture_id;

        $state = ['short' => '1H', 'elapsed' => 1, 'extra' => null, 'home' => 0, 'away' => 0];

        Http::fake(function () use (&$state, $fixtureId, $leagueId) {
            return Http::response(['response' => [[
                'league'  => ['id' => $leagueId],
                'fixture' => [
                    'id'     => $fixtureId,
                    'date'   => now()->toIso8601String(),
                    'status' => [
                        'short'   => $state['short'],
                        'elapsed' => $state['elapsed'],
                        'extra'   => $state['extra'],
                    ],
                ],
                'goals'   => ['home' => $state['home'], 'away' => $state['away']],
            ]]], 200);
        });

        $latest = function () use ($client): array {
            $row = DB::table('notifications')
                ->where('client_id', $client)
                ->orderByDesc('id')
                ->first();

            return $row ? [json_decode($row->data, true), $row->id] : [null, null];
        };

        Artisan::call('match:events');
        [$data] = $latest();
        $this->assertSame('match_started', $data['sub_type'] ?? null);

        // Half-time with stoppage time and a live score straight from the API.
        $state = ['short' => 'HT', 'elapsed' => 45, 'extra' => 3, 'home' => 2, 'away' => 1];
        Artisan::call('match:events');

        [$data, $id] = $latest();
        $this->assertSame('half_time', $data['sub_type'] ?? null);
        $body = (string) DB::table('notification_translations')
            ->where('notification_id', $id)->where('locale', 'en')->value('body');
        $this->assertStringContainsString('45+3', $body);
        $this->assertStringContainsString('2-1', $body);
        $this->assertNull(Matche::find($match->id)->extra, 'stoppage time must reset after the half ends');

        // Full-time with added time.
        $state = ['short' => 'FT', 'elapsed' => 90, 'extra' => 5, 'home' => 2, 'away' => 1];
        Artisan::call('match:events');

        [$data, $id] = $latest();
        $this->assertSame('match_ended', $data['sub_type'] ?? null);
        $body = (string) DB::table('notification_translations')
            ->where('notification_id', $id)->where('locale', 'en')->value('body');
        $this->assertStringContainsString('90+5', $body);

        // Re-polling the same status must not re-notify.
        $countBefore = DB::table('notifications')->where('client_id', $client)->count();
        Artisan::call('match:events');
        $this->assertSame(
            $countBefore,
            DB::table('notifications')->where('client_id', $client)->count(),
            'a repeated status must not produce a duplicate notification'
        );
    }
}
