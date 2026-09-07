<?php

/*
|--------------------------------------------------------------------------
| Loan module — structural constants
|--------------------------------------------------------------------------
| Only things that are part of the code's shape live here. Anything an
| operator may want to tune (amounts, tenure, guarantor count, penalty
| switches) belongs on a `loan_programs` row instead, and anything global
| and operator-facing belongs in `settings`. Never call env() from here.
*/

return [

    // Every state a loan may occupy. Stored as string(32), never an enum:
    // adding a state must not require a Postgres CHECK-constraint drop.
    'statuses' => [
        'draft',
        'submitted',
        'under_review',
        'changes_requested',
        'approved',
        'guarantors_pending',
        'guarantors_secured',
        'disburser_notified',
        'disburser_acknowledged',
        'disbursed',
        'overdue',
        'in_grace',
        'defaulted',
        'settled',
        'written_off',
        'rejected',
        'withdrawn',
        'cancelled',
    ],

    // States from which no transition is legal.
    'terminal_statuses' => [
        'rejected',
        'withdrawn',
        'cancelled',
        'settled',
        'written_off',
    ],

    // States before money has left the association's hands; a loan here may
    // still be withdrawn by the applicant or cancelled by an officer.
    'pre_disbursement_statuses' => [
        'draft',
        'submitted',
        'under_review',
        'changes_requested',
        'approved',
        'guarantors_pending',
        'guarantors_secured',
        'disburser_notified',
        'disburser_acknowledged',
    ],

    // The state machine. LoanStateMachine::transition() is the only writer of
    // loans.status and refuses anything not listed here.
    'transitions' => [
        'draft' => ['submitted', 'withdrawn', 'cancelled'],
        'submitted' => ['under_review', 'rejected', 'withdrawn', 'cancelled'],
        'under_review' => ['approved', 'rejected', 'changes_requested', 'withdrawn', 'cancelled'],
        'changes_requested' => ['submitted', 'withdrawn', 'cancelled'],
        'approved' => ['guarantors_pending', 'withdrawn', 'cancelled'],
        'guarantors_pending' => ['guarantors_secured', 'withdrawn', 'cancelled'],
        'guarantors_secured' => ['disburser_notified', 'withdrawn', 'cancelled'],
        'disburser_notified' => ['disburser_acknowledged', 'withdrawn', 'cancelled'],
        'disburser_acknowledged' => ['disbursed', 'withdrawn', 'cancelled'],
        'disbursed' => ['settled', 'overdue'],
        'overdue' => ['settled', 'in_grace'],
        'in_grace' => ['settled', 'defaulted'],
        'defaulted' => ['settled', 'written_off'],
        'settled' => [],
        'written_off' => [],
        'rejected' => [],
        'withdrawn' => [],
        'cancelled' => [],
    ],

    'guarantor_statuses' => ['invited', 'accepted', 'declined', 'revoked', 'expired'],

    'repayment_statuses' => ['pending_confirmation', 'confirmed', 'rejected'],

    'program_statuses' => ['draft', 'active', 'paused', 'closed'],

    'officer_roles' => ['reviewer', 'disburser', 'treasurer'],

    'publication_scopes' => ['forum', 'public'],

    // Interest is flat over the tenure and frozen at disbursement. Daily
    // accrual cannot be reconciled against a bank statement, which is all the
    // evidence this ledger ever has.
    'interest_methods' => ['flat_per_tenure'],

    'disbursement_methods' => ['bank_transfer', 'cash', 'mobile_money', 'cheque', 'other'],

    'repayment_methods' => ['bank_transfer', 'cash', 'mobile_money', 'cheque', 'other'],

    // Disbursement and repayment proof never goes on a public disk.
    'private_disk' => 'local',
    'proof_max_kb' => 5120,
    'proof_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],

    // How long a guarantor invitation stays open.
    'guarantor_invite_days' => 7,

    // A defaulted loan cannot be escalated to the public page until the
    // default is this many days old. Nobody is publicly named the same day
    // their grace window closes.
    'public_escalation_min_days' => 14,

    'reference_prefix' => 'LN',
];
