<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanStateTransition;
use App\Models\User;
use App\Services\LoanStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LoanStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function machine(): LoanStateMachine
    {
        return app(LoanStateMachine::class);
    }

    public function test_a_legal_transition_writes_exactly_one_history_row(): void
    {
        $loan = Loan::factory()->create(['status' => 'draft']);
        $actor = User::factory()->create();

        $this->machine()->transition($loan, 'submitted', $actor, ['silent' => true, 'reason' => 'Applied']);

        $this->assertSame('submitted', $loan->fresh()->status);

        $transitions = LoanStateTransition::where('loan_id', $loan->id)->get();
        $this->assertCount(1, $transitions);
        $this->assertSame('draft', $transitions[0]->from_status);
        $this->assertSame('submitted', $transitions[0]->to_status);
        $this->assertSame($actor->id, $transitions[0]->actor_id);
        $this->assertSame('user', $transitions[0]->actor_type);
        $this->assertSame('Applied', $transitions[0]->reason);
    }

    public function test_a_transition_with_no_actor_is_recorded_as_the_system(): void
    {
        $loan = Loan::factory()->disbursed(95)->create();

        $this->machine()->transition($loan, 'overdue', null, ['silent' => true]);

        $this->assertSame('system', LoanStateTransition::where('loan_id', $loan->id)->first()->actor_type);
    }

    public function test_illegal_transitions_are_refused(): void
    {
        $loan = Loan::factory()->create(['status' => 'draft']);

        $this->expectException(RuntimeException::class);
        $this->machine()->transition($loan, 'disbursed', null, ['silent' => true]);
    }

    public function test_a_rejected_application_cannot_be_resubmitted(): void
    {
        $loan = Loan::factory()->create(['status' => 'rejected']);

        $this->expectException(RuntimeException::class);
        $this->machine()->transition($loan, 'submitted', null, ['silent' => true]);
    }

    public function test_a_settled_loan_cannot_go_overdue(): void
    {
        $loan = Loan::factory()->create(['status' => 'settled']);

        $this->expectException(RuntimeException::class);
        $this->machine()->transition($loan, 'overdue', null, ['silent' => true]);
    }

    public function test_an_illegal_transition_leaves_the_loan_and_its_history_untouched(): void
    {
        $loan = Loan::factory()->create(['status' => 'draft']);

        try {
            $this->machine()->transition($loan, 'settled', null, ['silent' => true]);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame('draft', $loan->fresh()->status);
        $this->assertSame(0, LoanStateTransition::where('loan_id', $loan->id)->count());
    }

    public function test_the_whole_happy_path_is_walkable(): void
    {
        $loan = Loan::factory()->create(['status' => 'draft']);

        $path = [
            'submitted', 'under_review', 'approved', 'guarantors_pending', 'guarantors_secured',
            'disburser_notified', 'disburser_acknowledged', 'disbursed', 'overdue', 'in_grace',
            'defaulted', 'settled',
        ];

        foreach ($path as $status) {
            $this->machine()->transition($loan, $status, null, ['silent' => true]);
        }

        $this->assertSame('settled', $loan->fresh()->status);
        $this->assertSame(count($path), LoanStateTransition::where('loan_id', $loan->id)->count());
    }

    public function test_attributes_are_written_in_the_same_update_as_the_status(): void
    {
        $loan = Loan::factory()->create(['status' => 'submitted']);
        $reviewer = User::factory()->create();

        $this->machine()->transition($loan, 'under_review', $reviewer, [
            'silent' => true,
            'attributes' => ['reviewed_by' => $reviewer->id, 'reviewed_at' => now()],
        ]);

        $fresh = $loan->fresh();
        $this->assertSame('under_review', $fresh->status);
        $this->assertSame($reviewer->id, $fresh->reviewed_by);
    }
}
