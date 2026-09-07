<script setup>
import { ref } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { statusBadge, formatMoney } from '@/Pages/Loans/status';

const props = defineProps({ loan: Object, can: Object });

const editing = ref(false);

const form = useForm({
    amount_requested: props.loan.amount_requested,
    tenure_months: props.loan.tenure_months,
    purpose: props.loan.purpose,
});

const withdrawForm = useForm({});

// Guarantor nominations. Candidates are fetched rather than shipped with the
// page: a graduating set can run to hundreds of members.
const inviteForm = useForm({ user_id: null });
const search = ref('');
const candidates = ref([]);
const searching = ref(false);
let searchTimer = null;

function findCandidates() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        searching.value = true;
        try {
            const response = await fetch(route('loans.guarantors.candidates', props.loan.id) + '?q=' + encodeURIComponent(search.value), {
                headers: { Accept: 'application/json' },
            });
            candidates.value = response.ok ? (await response.json()).candidates : [];
        } finally {
            searching.value = false;
        }
    }, 250);
}

function invite(candidate) {
    inviteForm.user_id = candidate.id;
    inviteForm.post(route('loans.guarantors.store', props.loan.id), {
        preserveScroll: true,
        onSuccess: () => { search.value = ''; candidates.value = []; },
    });
}

const revokeForm = useForm({});

function revoke(guarantor) {
    revokeForm.delete(route('loans.guarantors.destroy', [props.loan.id, guarantor.id]), { preserveScroll: true });
}

const guarantorStatus = {
    invited: { label: 'Asked', class: 'bg-yellow-100 text-yellow-800' },
    accepted: { label: 'Accepted', class: 'bg-green-100 text-green-800' },
    declined: { label: 'Declined', class: 'bg-red-100 text-red-800' },
    revoked: { label: 'Withdrawn', class: 'bg-gray-100 text-gray-800' },
    expired: { label: 'Expired', class: 'bg-gray-100 text-gray-800' },
};

function resubmit() {
    form.patch(route('loans.update', props.loan.id), {
        onSuccess: () => { editing.value = false; },
    });
}

function withdraw() {
    if (confirm('Withdraw this application? This cannot be undone.')) {
        withdrawForm.delete(route('loans.withdraw', props.loan.id));
    }
}
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="loan.reference" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Loan {{ loan.reference }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="statusBadge[loan.status]?.class">
                                {{ statusBadge[loan.status]?.label || loan.status }}
                            </span>
                            <h3 class="font-semibold text-2xl text-gray-900 dark:text-gray-100 mt-2">
                                {{ loan.currency }} {{ formatMoney(loan.amount_approved ?? loan.amount_requested) }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ loan.program }}<span v-if="loan.tenure_months"> · {{ loan.tenure_months }} months</span>
                            </p>
                        </div>
                        <dl class="text-sm text-right text-gray-600 dark:text-gray-300 space-y-1">
                            <div v-if="loan.total_repayable"><dt class="inline text-gray-500">Total repayable: </dt><dd class="inline">{{ loan.currency }} {{ formatMoney(loan.total_repayable) }}</dd></div>
                            <div v-if="loan.outstanding > 0"><dt class="inline text-gray-500">Outstanding: </dt><dd class="inline font-semibold">{{ loan.currency }} {{ formatMoney(loan.outstanding) }}</dd></div>
                            <div v-if="loan.due_at"><dt class="inline text-gray-500">Due: </dt><dd class="inline">{{ new Date(loan.due_at).toLocaleDateString() }}</dd></div>
                        </dl>
                    </div>

                    <div v-if="loan.review_notes" class="mt-5 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-200">Note from the welfare committee</h4>
                        <p class="text-sm text-yellow-800 dark:text-yellow-300 mt-1 whitespace-pre-line">{{ loan.review_notes }}</p>
                    </div>

                    <div class="mt-5 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Purpose</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-line">{{ loan.purpose }}</p>
                    </div>

                    <p v-if="loan.terms_agreed_at" class="text-xs text-gray-500 mt-4">
                        Terms {{ loan.terms_version }} accepted {{ new Date(loan.terms_agreed_at).toLocaleString() }}.
                        <Link :href="route('loans.terms')" class="text-accent-600 hover:underline">Read them again</Link>
                    </p>

                    <div v-if="can.update || can.withdraw" class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <button v-if="can.update && !editing" type="button" @click="editing = true"
                            class="px-4 py-2 rounded-lg bg-accent-500 text-white text-sm font-medium hover:bg-accent-600 transition">
                            Update and resubmit
                        </button>
                        <button v-if="can.withdraw" type="button" @click="withdraw"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Withdraw
                        </button>
                    </div>
                </div>

                <form v-if="editing" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-4" @submit.prevent="resubmit">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Update your application</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount ({{ loan.currency }})</label>
                        <input v-model.number="form.amount_requested" type="number" min="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm" />
                        <p v-if="form.errors.amount_requested" class="text-sm text-red-600 mt-1">{{ form.errors.amount_requested }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repayment period (months)</label>
                        <input v-model.number="form.tenure_months" type="number" min="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm" />
                        <p v-if="form.errors.tenure_months" class="text-sm text-red-600 mt-1">{{ form.errors.tenure_months }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purpose</label>
                        <textarea v-model="form.purpose" rows="4" required maxlength="2000"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm"></textarea>
                        <p v-if="form.errors.purpose" class="text-sm text-red-600 mt-1">{{ form.errors.purpose }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 rounded-lg bg-accent-500 text-white text-sm font-medium hover:bg-accent-600 disabled:opacity-50 transition">
                            Resubmit
                        </button>
                        <button type="button" class="text-sm text-gray-500 hover:underline" @click="editing = false">Cancel</button>
                    </div>
                </form>

                <div v-if="loan.disbursed_at" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Disbursement</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-gray-500">Amount released</dt><dd class="text-gray-800 dark:text-gray-200">{{ loan.currency }} {{ formatMoney(loan.disbursed_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Date</dt><dd class="text-gray-800 dark:text-gray-200">{{ new Date(loan.disbursed_at).toLocaleDateString() }}</dd></div>
                        <div v-if="loan.disbursement_reference"><dt class="text-gray-500">Reference</dt><dd class="text-gray-800 dark:text-gray-200 font-mono">{{ loan.disbursement_reference }}</dd></div>
                        <div v-if="loan.grace_ends_at"><dt class="text-gray-500">Grace period ends</dt><dd class="text-gray-800 dark:text-gray-200">{{ new Date(loan.grace_ends_at).toLocaleDateString() }}</dd></div>
                    </dl>
                    <a v-if="loan.has_disbursement_proof" :href="route('loans.proof.disbursement', loan.id)" target="_blank"
                        class="text-sm text-accent-600 hover:underline mt-4 inline-block">View proof of transfer</a>
                </div>

                <div v-if="loan.guarantors?.length || can.inviteGuarantors" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Guarantors</h3>
                        <span class="text-sm text-gray-500">{{ loan.guarantors_accepted }} of {{ loan.guarantors_required }} accepted</span>
                    </div>

                    <ul class="mt-4 space-y-2">
                        <li v-for="guarantor in loan.guarantors" :key="guarantor.id"
                            class="flex flex-wrap items-center justify-between gap-2 text-sm border-b border-gray-100 dark:border-gray-700 pb-2 last:border-0">
                            <span class="text-gray-800 dark:text-gray-200">{{ guarantor.name }}</span>
                            <span class="flex items-center gap-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="guarantorStatus[guarantor.status]?.class">
                                    {{ guarantorStatus[guarantor.status]?.label || guarantor.status }}
                                </span>
                                <button v-if="can.inviteGuarantors && guarantor.status === 'invited'" type="button"
                                    class="text-xs text-gray-500 hover:underline" @click="revoke(guarantor)">
                                    Withdraw request
                                </button>
                            </span>
                        </li>
                    </ul>

                    <div v-if="can.inviteGuarantors && loan.guarantors_accepted < loan.guarantors_required" class="mt-5 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ask a member of your set</label>
                        <input v-model="search" type="search" placeholder="Search by name" @input="findCandidates"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm" />
                        <p v-if="inviteForm.errors.user_id" class="text-sm text-red-600 mt-1">{{ inviteForm.errors.user_id }}</p>

                        <ul v-if="candidates.length" class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                            <li v-for="candidate in candidates" :key="candidate.id" class="flex items-center justify-between gap-3 p-3">
                                <span class="min-w-0">
                                    <span class="text-sm text-gray-800 dark:text-gray-200">{{ candidate.name }}</span>
                                    <span v-if="candidate.profession" class="block text-xs text-gray-500">{{ candidate.profession }}</span>
                                </span>
                                <button type="button" :disabled="inviteForm.processing" @click="invite(candidate)"
                                    class="text-sm text-accent-600 hover:underline disabled:opacity-50 whitespace-nowrap">Ask</button>
                            </li>
                        </ul>
                        <p v-else-if="search && !searching" class="text-sm text-gray-500 mt-2">No members of your set match that.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">History</h3>
                    <ol class="space-y-3">
                        <li v-for="entry in loan.timeline" :key="entry.id" class="flex gap-3 text-sm">
                            <span class="w-2 h-2 mt-1.5 rounded-full bg-accent-500 shrink-0"></span>
                            <div class="min-w-0">
                                <p class="text-gray-800 dark:text-gray-200">
                                    {{ statusBadge[entry.to]?.label || entry.to }}
                                    <span v-if="entry.actor" class="text-gray-500">· {{ entry.actor }}</span>
                                </p>
                                <p v-if="entry.reason" class="text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ entry.reason }}</p>
                                <p class="text-xs text-gray-400">{{ new Date(entry.at).toLocaleString() }}</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <Link :href="route('loans.index')" class="text-sm text-accent-600 hover:underline inline-block">Back to loans</Link>
            </div>
        </div>
    </AuthLayout>
</template>
