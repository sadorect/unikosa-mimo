<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Services\LoanEligibilityService;
use App\Services\LoanStateMachine;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function __construct(
        protected LoanEligibilityService $eligibility,
        protected LoanStateMachine $stateMachine,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $program = $this->programFor($request);

        return Inertia::render('Loans/Index', [
            'program' => $program ? $this->programPayload($program) : null,
            'canApply' => $program ? $this->eligibility->passes($user, $program) : false,
            'blockers' => $program ? $this->eligibility->reasons($user, $program) : ['Your set does not run a loan fund yet.'],
            'loans' => $user->loans()->with('program')->latest()->get()->map(fn (Loan $loan) => $this->loanSummary($loan)),
        ]);
    }

    public function create(Request $request)
    {
        $program = $this->programFor($request);
        abort_unless($program, 404, 'Your set does not run a loan fund.');

        $blockers = $this->eligibility->reasons($request->user(), $program);
        $term = $program->currentTerm();

        return Inertia::render('Loans/Create', [
            'program' => $this->programPayload($program),
            'blockers' => $blockers,
            'term' => $term ? ['id' => $term->id, 'version' => $term->version, 'body' => $term->body] : null,
        ]);
    }

    public function store(Request $request)
    {
        $program = $this->programFor($request);
        abort_unless($program, 404, 'Your set does not run a loan fund.');

        $validated = $request->validate([
            'amount_requested' => 'required|integer|min:1',
            'tenure_months' => ['required', 'integer', 'min:1', 'max:'.$program->max_tenure_months],
            'purpose' => 'required|string|max:2000',
            // Not a formality: this is the consent the publication penalty
            // rests on, so it is captured with the loan and never inferred.
            'agree_terms' => 'accepted',
        ]);

        $user = $request->user();
        $blockers = $this->eligibility->reasons($user, $program, (int) $validated['amount_requested']);

        if ($blockers !== []) {
            return back()->with('error', $blockers[0])->withErrors(['amount_requested' => $blockers[0]]);
        }

        $term = $program->currentTerm();
        abort_unless($term, 422, 'This fund has not published its terms of operation.');

        $loan = $this->createWithReference([
            'loan_program_id' => $program->id,
            'set_id' => $program->set_id,
            'user_id' => $user->id,
            'amount_requested' => (int) $validated['amount_requested'],
            'currency' => $program->currency,
            'tenure_months' => (int) $validated['tenure_months'],
            'interest_rate_bps' => $program->interest_rate_bps,
            'purpose' => $validated['purpose'],
            'guarantors_required' => $program->required_guarantors,
            'loan_term_id' => $term->id,
            'terms_agreed_at' => now(),
            'terms_agreed_ip' => $request->ip(),
            'terms_agreed_user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'status' => 'draft',
        ]);

        $this->stateMachine->transition($loan, 'submitted', $user, [
            'meta' => ['terms_version' => $term->version],
        ]);

        return redirect()->route('loans.show', $loan)
            ->with('success', "Application {$loan->reference} submitted. The welfare committee will review it.");
    }

    public function show(Request $request, Loan $loan)
    {
        Gate::authorize('view', $loan);

        $loan->load(['program.set', 'applicant', 'term', 'reviewer', 'transitions.actor']);

        return Inertia::render('Loans/Show', [
            'loan' => array_merge($this->loanSummary($loan), [
                'purpose' => $loan->purpose,
                'review_notes' => $loan->review_notes,
                'terms_agreed_at' => $loan->terms_agreed_at,
                'terms_version' => $loan->term?->version,
                'guarantors_required' => $loan->guarantors_required,
                'applicant' => ['id' => $loan->applicant->id, 'name' => $loan->applicant->name],
                'timeline' => $loan->transitions->map(fn ($transition) => [
                    'id' => $transition->id,
                    'from' => $transition->from_status,
                    'to' => $transition->to_status,
                    'actor' => $transition->actor?->name,
                    'reason' => $transition->reason,
                    'at' => $transition->created_at,
                ]),
            ]),
            'can' => [
                'update' => $request->user()->can('update', $loan),
                'withdraw' => $request->user()->can('withdraw', $loan),
                'review' => $request->user()->can('review', $loan),
            ],
        ]);
    }

    /**
     * The resubmission path. Only a draft or an application the committee sent
     * back may be edited — everything else is either under review or decided.
     */
    public function update(Request $request, Loan $loan)
    {
        Gate::authorize('update', $loan);

        $program = $loan->program;

        $validated = $request->validate([
            'amount_requested' => 'required|integer|min:1',
            'tenure_months' => ['required', 'integer', 'min:1', 'max:'.$program->max_tenure_months],
            'purpose' => 'required|string|max:2000',
        ]);

        $blockers = $this->eligibility->amountBlockersFor($loan, (int) $validated['amount_requested']);

        if ($blockers !== []) {
            return back()->withErrors(['amount_requested' => $blockers[0]]);
        }

        $this->stateMachine->transition($loan, 'submitted', $request->user(), [
            'attributes' => array_merge($validated, ['review_notes' => null]),
        ]);

        return back()->with('success', 'Your application has been resubmitted for review.');
    }

    public function withdraw(Request $request, Loan $loan)
    {
        Gate::authorize('withdraw', $loan);

        $this->stateMachine->transition($loan, 'withdrawn', $request->user(), [
            'attributes' => ['withdrawn_at' => now()],
        ]);

        return redirect()->route('loans.index')->with('success', 'Your application has been withdrawn.');
    }

    public function terms(Request $request)
    {
        $program = $this->programFor($request);
        abort_unless($program, 404);

        $term = $program->currentTerm();
        abort_unless($term, 404, 'This fund has not published its terms of operation.');

        return Inertia::render('Loans/Terms', [
            'program' => $this->programPayload($program),
            'term' => ['version' => $term->version, 'body' => $term->body, 'published_at' => $term->published_at],
        ]);
    }

    /** The fund open to this member: the one active program for their set. */
    protected function programFor(Request $request): ?LoanProgram
    {
        $setId = $request->user()->graduating_set_id;

        if (! $setId) {
            return null;
        }

        return LoanProgram::where('set_id', $setId)
            ->whereIn('status', ['active', 'paused'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Postgres has sequences and sqlite does not, so the reference is built in
     * PHP and retried on the unique violation rather than derived from max(id).
     */
    protected function createWithReference(array $attributes): Loan
    {
        foreach (range(1, 5) as $attempt) {
            try {
                return Loan::create(array_merge($attributes, [
                    'reference' => config('loans.reference_prefix').'-'.now()->year.'-'.Str::upper(Str::random(8)),
                ]));
            } catch (QueryException $exception) {
                if ($attempt === 5 || ! str_contains(strtolower($exception->getMessage()), 'unique')) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a loan reference.');
    }

    protected function programPayload(LoanProgram $program): array
    {
        return [
            'id' => $program->id,
            'name' => $program->name,
            'set' => $program->set?->name,
            'currency' => $program->currency,
            'status' => $program->status,
            'min_loan_amount' => $program->min_loan_amount,
            'max_loan_amount' => $program->max_loan_amount,
            'default_tenure_months' => $program->default_tenure_months,
            'max_tenure_months' => $program->max_tenure_months,
            'interest_rate_percent' => $program->interest_rate_bps / 100,
            'required_guarantors' => $program->required_guarantors,
            'grace_days' => $program->grace_days,
        ];
    }

    protected function loanSummary(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'reference' => $loan->reference,
            'status' => $loan->status,
            'currency' => $loan->currency,
            'amount_requested' => $loan->amount_requested,
            'amount_approved' => $loan->amount_approved,
            'tenure_months' => $loan->tenure_months,
            'interest_amount' => $loan->interest_amount,
            'total_repayable' => $loan->total_repayable,
            'outstanding' => $loan->outstanding_amount,
            'due_at' => $loan->due_at,
            'program' => $loan->program?->name,
            'created_at' => $loan->created_at,
        ];
    }
}
