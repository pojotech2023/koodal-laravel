<?php

namespace Tests\Concerns;

use App\Models\KoodalSyncQuestion;
use App\Models\User;

/**
 * Two members, a way to switch between them mid-test, and the seed rows the
 * games need. Sanctum caches the resolved user on the guard for the life of the
 * app instance, so "act as the other player" has to forget it first.
 */
trait PlaysGames
{
    use SeedsCoreData;

    protected function bootGames(): void
    {
        $this->seedRoles();
        $this->seedPlans();
    }

    /** @return array{0: User, 1: User} */
    protected function pair(): array
    {
        return [$this->member(), $this->member()];
    }

    protected function actAs(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($user->createToken('test', ['user'])->plainTextToken);
    }

    protected function seedSyncQuestions(int $count = 12): void
    {
        for ($i = 1; $i <= $count; $i++) {
            KoodalSyncQuestion::create([
                'category' => 'Lifestyle',
                'question_text_en' => "Question {$i}?",
                'question_text_ta' => "கேள்வி {$i}?",
                'options' => [
                    ['key' => 'A', 'label_en' => 'Option A', 'label_ta' => 'விருப்பம் A'],
                    ['key' => 'B', 'label_en' => 'Option B', 'label_ta' => 'விருப்பம் B'],
                ],
                'is_active' => true,
            ]);
        }
    }

    /** A paid plan unlocks free-text game chat. */
    protected function goPaid(User $user): void
    {
        $this->subscribe($user, 'silver');
    }
}
