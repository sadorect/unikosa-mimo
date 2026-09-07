// Shared between the loan pages so a status never reads one way on the list
// and another on the detail page.
export const statusBadge = {
    draft: { label: 'Draft', class: 'bg-gray-100 text-gray-800' },
    submitted: { label: 'Awaiting review', class: 'bg-blue-100 text-blue-800' },
    under_review: { label: 'Under review', class: 'bg-blue-100 text-blue-800' },
    changes_requested: { label: 'Changes requested', class: 'bg-yellow-100 text-yellow-800' },
    approved: { label: 'Approved', class: 'bg-green-100 text-green-800' },
    guarantors_pending: { label: 'Guarantors needed', class: 'bg-yellow-100 text-yellow-800' },
    guarantors_secured: { label: 'Guarantors secured', class: 'bg-green-100 text-green-800' },
    disburser_notified: { label: 'With the disburser', class: 'bg-blue-100 text-blue-800' },
    disburser_acknowledged: { label: 'Disbursement in hand', class: 'bg-blue-100 text-blue-800' },
    disbursed: { label: 'Disbursed', class: 'bg-green-100 text-green-800' },
    overdue: { label: 'Overdue', class: 'bg-orange-100 text-orange-800' },
    in_grace: { label: 'In grace period', class: 'bg-orange-100 text-orange-800' },
    defaulted: { label: 'In default', class: 'bg-red-100 text-red-800' },
    settled: { label: 'Settled', class: 'bg-green-100 text-green-800' },
    written_off: { label: 'Written off', class: 'bg-gray-100 text-gray-800' },
    rejected: { label: 'Not approved', class: 'bg-red-100 text-red-800' },
    withdrawn: { label: 'Withdrawn', class: 'bg-gray-100 text-gray-800' },
    cancelled: { label: 'Cancelled', class: 'bg-gray-100 text-gray-800' },
};

export function formatMoney(amount) {
    return new Intl.NumberFormat().format(amount ?? 0);
}
