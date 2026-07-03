<?php

namespace App\Support\Eligibility;

use App\Models\EligibilityRule;
use App\Models\User;
use App\Support\Eligibility\Rules\AccountClaimedRule;
use App\Support\Eligibility\Rules\ChapterRule;
use App\Support\Eligibility\Rules\DuesCurrentRule;
use App\Support\Eligibility\Rules\ManualListRule;
use App\Support\Eligibility\Rules\RoleRule;
use App\Support\Eligibility\Rules\SetListRule;
use App\Support\Eligibility\Rules\SetRangeRule;
use Illuminate\Database\Eloquent\Model;

class EligibilityService
{
    /**
     * @var array<string, class-string<RuleInterface>>
     */
    public const RULE_TYPES = [
        'set_range' => SetRangeRule::class,
        'set_list' => SetListRule::class,
        'chapter' => ChapterRule::class,
        'dues_current' => DuesCurrentRule::class,
        'role' => RoleRule::class,
        'account_claimed' => AccountClaimedRule::class,
        'manual_list' => ManualListRule::class,
    ];

    /** @var array<string, \Illuminate\Support\Collection<int, EligibilityRule>> */
    protected array $ruleCache = [];

    /**
     * A user passes if every rule attached to the given rulable+context passes
     * (logical AND). No rules attached at all means everyone is eligible.
     *
     * The rules for a given rulable+context are identical across users, so they're
     * memoized on the service instance — a bulk caller (e.g. the results-notification
     * job) that shares one instance across thousands of users pays a single query for
     * the rule set, not one per user.
     */
    public function passes(User $user, Model $rulable, string $context): bool
    {
        foreach ($this->rulesFor($rulable, $context) as $rule) {
            $ruleClass = self::RULE_TYPES[$rule->type] ?? null;

            if ($ruleClass === null || ! app($ruleClass)->passes($user, $rule->config ?? [])) {
                return false;
            }
        }

        return true;
    }

    protected function rulesFor(Model $rulable, string $context): \Illuminate\Support\Collection
    {
        $key = $rulable->getMorphClass() . '|' . $rulable->getKey() . '|' . $context;

        return $this->ruleCache[$key] ??= EligibilityRule::where('rulable_type', $rulable->getMorphClass())
            ->where('rulable_id', $rulable->getKey())
            ->where('context', $context)
            ->get();
    }
}
