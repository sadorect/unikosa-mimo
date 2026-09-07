<script setup>
import { ref } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { formatMoney } from '@/Pages/Loans/status';

defineProps({ requests: Array });

const openNote = ref(null);
const form = useForm({ response_note: '' });

function accept(request) {
    if (!confirm(`Guarantee ${request.loan.applicant}'s loan of ${request.loan.currency} ${formatMoney(request.loan.amount)}? If it falls into default your name may be published alongside theirs.`)) {
        return;
    }
    form.post(route('loans.guarantorships.accept', request.id), { preserveScroll: true });
}

function decline(request) {
    form.post(route('loans.guarantorships.decline', request.id), {
        preserveScroll: true,
        onSuccess: () => { openNote.value = null; form.reset(); },
    });
}

const statusLabel = {
    invited: 'Awaiting your answer',
    accepted: 'You accepted',
    declined: 'You declined',
    revoked: 'Withdrawn by the applicant',
    expired: 'Expired',
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Guarantor requests" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Guarantor requests</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

                <div v-for="request in requests" :key="request.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ request.loan.applicant }} · {{ request.loan.currency }} {{ formatMoney(request.loan.amount) }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ request.loan.program }} · {{ request.loan.reference }}
                                <span v-if="request.loan.tenure_months"> · {{ request.loan.tenure_months }} months</span>
                            </p>
                            <p v-if="request.loan.purpose" class="text-sm text-gray-600 dark:text-gray-300 mt-2 whitespace-pre-line">{{ request.loan.purpose }}</p>
                        </div>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap"
                            :class="request.status === 'invited' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'">
                            {{ request.expired ? 'Expired' : statusLabel[request.status] }}
                        </span>
                    </div>

                    <div v-if="request.status === 'invited' && !request.expired" class="mt-5 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            If you accept, your name is recorded against this loan. Should it fall into default, you will be asked to prompt
                            {{ request.loan.applicant }}, and your name may appear alongside theirs in a default notice.
                            <span v-if="request.expires_at">This request expires {{ new Date(request.expires_at).toLocaleDateString() }}.</span>
                        </p>

                        <div v-if="openNote === request.id" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anything you want to say? (optional)</label>
                            <textarea v-model="form.response_note" rows="2" maxlength="1000"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm"></textarea>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button type="button" :disabled="form.processing" @click="accept(request)"
                                class="px-4 py-2 rounded-lg bg-accent-500 text-white text-sm font-medium hover:bg-accent-600 disabled:opacity-50 transition">
                                Accept
                            </button>
                            <button type="button" :disabled="form.processing"
                                @click="openNote === request.id ? decline(request) : (openNote = request.id)"
                                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                {{ openNote === request.id ? 'Confirm decline' : 'Decline' }}
                            </button>
                        </div>
                    </div>

                    <div v-else-if="request.status === 'accepted' && request.loan.outstanding > 0"
                        class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            Outstanding: {{ request.loan.currency }} {{ formatMoney(request.loan.outstanding) }}
                            <span v-if="request.loan.due_at"> · due {{ new Date(request.loan.due_at).toLocaleDateString() }}</span>
                        </span>
                        <!-- The way in for a guarantor who has to pay on the borrower's behalf. -->
                        <Link :href="route('loans.show', request.loan.id)" class="text-accent-600 hover:underline whitespace-nowrap">
                            {{ ['overdue', 'in_grace', 'defaulted'].includes(request.loan.status) ? 'View or pay towards this loan' : 'View the loan' }}
                        </Link>
                    </div>
                </div>

                <div v-if="!requests.length" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    Nobody has asked you to guarantee a loan.
                </div>

                <Link :href="route('loans.index')" class="text-sm text-accent-600 hover:underline inline-block">Back to loans</Link>
            </div>
        </div>
    </AuthLayout>
</template>
