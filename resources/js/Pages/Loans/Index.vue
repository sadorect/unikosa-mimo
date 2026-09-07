<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { statusBadge, formatMoney } from '@/Pages/Loans/status';

defineProps({
    program: Object,
    loans: Array,
    canApply: Boolean,
    blockers: Array,
});
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Loans" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Loans</h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div v-if="program" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ program.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ program.currency }} {{ formatMoney(program.min_loan_amount) }}
                                <span v-if="program.max_loan_amount">– {{ formatMoney(program.max_loan_amount) }}</span>
                                · {{ program.interest_rate_percent }}% flat over {{ program.default_tenure_months }} months
                                · {{ program.required_guarantors }} guarantors
                            </p>
                            <Link :href="route('loans.terms')" class="text-sm text-accent-600 hover:underline mt-2 inline-block">
                                Read the terms of operation
                            </Link>
                        </div>
                        <Link v-if="canApply" :href="route('loans.create')"
                            class="px-4 py-2 rounded-lg bg-accent-500 text-white text-sm font-medium hover:bg-accent-600 transition whitespace-nowrap">
                            Apply for a loan
                        </Link>
                    </div>

                    <ul v-if="!canApply && blockers.length" class="mt-4 space-y-1 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <li v-for="blocker in blockers" :key="blocker">• {{ blocker }}</li>
                    </ul>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 text-gray-500 dark:text-gray-400">
                    Your set does not run a loan fund at the moment.
                </div>

                <div class="space-y-3">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Your applications</h3>

                    <Link v-for="loan in loans" :key="loan.id" :href="route('loans.show', loan.id)"
                        class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ loan.reference }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap"
                                        :class="statusBadge[loan.status]?.class">
                                        {{ statusBadge[loan.status]?.label || loan.status }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ loan.currency }} {{ formatMoney(loan.amount_approved ?? loan.amount_requested) }}
                                    <span v-if="loan.due_at"> · due {{ new Date(loan.due_at).toLocaleDateString() }}</span>
                                </p>
                            </div>
                            <div v-if="loan.outstanding > 0" class="text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                Outstanding: {{ loan.currency }} {{ formatMoney(loan.outstanding) }}
                            </div>
                        </div>
                    </Link>

                    <div v-if="!loans.length" class="text-center py-10 text-gray-500 dark:text-gray-400">
                        You have not applied for a loan yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
