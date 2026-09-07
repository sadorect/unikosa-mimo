<script setup>
import { ref, computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { formatMoney } from '@/Pages/Loans/status';

const props = defineProps({
    program: Object,
    term: Object,
    blockers: Array,
});

const form = useForm({
    amount_requested: props.program.min_loan_amount || null,
    tenure_months: props.program.default_tenure_months,
    purpose: '',
    agree_terms: false,
});

// The terms must actually be scrolled through before they can be accepted:
// the publication penalty is the whole basis of the scheme's consent.
const termsRead = ref(false);
const termsBox = ref(null);

function onTermsScroll(event) {
    const el = event.target;
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 24) {
        termsRead.value = true;
    }
}

const interest = computed(() => Math.floor(((form.amount_requested || 0) * props.program.interest_rate_percent) / 100));
const totalRepayable = computed(() => (form.amount_requested || 0) + interest.value);

const blocked = computed(() => props.blockers.length > 0);

function submit() {
    form.post(route('loans.store'));
}
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Apply for a loan" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Apply for a loan</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div v-if="blocked" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-5">
                    <h3 class="font-semibold text-yellow-900 dark:text-yellow-200">You cannot apply right now</h3>
                    <ul class="mt-2 space-y-1 text-sm text-yellow-800 dark:text-yellow-300">
                        <li v-for="blocker in blockers" :key="blocker">• {{ blocker }}</li>
                    </ul>
                    <Link :href="route('loans.index')" class="text-sm text-accent-600 hover:underline mt-3 inline-block">Back to loans</Link>
                </div>

                <form v-else class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6" @submit.prevent="submit">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ program.name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ program.interest_rate_percent }}% flat over the whole period · {{ program.required_guarantors }} guarantors required
                            · {{ program.grace_days }}-day grace period
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">How much do you need? ({{ program.currency }})</label>
                        <input v-model.number="form.amount_requested" type="number" min="1" step="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm" />
                        <p class="text-xs text-gray-500 mt-1">
                            Between {{ program.currency }} {{ formatMoney(program.min_loan_amount) }}
                            <span v-if="program.max_loan_amount">and {{ program.currency }} {{ formatMoney(program.max_loan_amount) }}</span>.
                        </p>
                        <p v-if="form.errors.amount_requested" class="text-sm text-red-600 mt-1">{{ form.errors.amount_requested }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repayment period (months)</label>
                        <input v-model.number="form.tenure_months" type="number" min="1" :max="program.max_tenure_months" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm" />
                        <p v-if="form.errors.tenure_months" class="text-sm text-red-600 mt-1">{{ form.errors.tenure_months }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 text-sm text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between"><span>Interest ({{ program.interest_rate_percent }}% flat)</span><span>{{ program.currency }} {{ formatMoney(interest) }}</span></div>
                        <div class="flex justify-between font-semibold mt-1"><span>Total repayable</span><span>{{ program.currency }} {{ formatMoney(totalRepayable) }}</span></div>
                        <p class="text-xs text-gray-500 mt-2">Indicative only. The committee may approve a smaller amount, and the final figures are fixed when the money is released.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">What is the loan for?</label>
                        <textarea v-model="form.purpose" rows="4" required maxlength="2000"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm"></textarea>
                        <p v-if="form.errors.purpose" class="text-sm text-red-600 mt-1">{{ form.errors.purpose }}</p>
                    </div>

                    <div v-if="term">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Terms of operation ({{ term.version }})</h4>
                        <div ref="termsBox" class="prose prose-sm dark:prose-invert max-w-none h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/50"
                            @scroll="onTermsScroll" v-html="term.body"></div>

                        <label class="flex items-start gap-2 mt-3" :class="termsRead ? '' : 'opacity-60'">
                            <input v-model="form.agree_terms" type="checkbox" :disabled="!termsRead" class="mt-1 rounded border-gray-300 dark:border-gray-600" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                I have read and accept the terms of operation, including the clause on publication of defaults.
                                <span v-if="!termsRead" class="block text-xs text-gray-500">Scroll to the end of the terms to enable this.</span>
                            </span>
                        </label>
                        <p v-if="form.errors.agree_terms" class="text-sm text-red-600 mt-1">{{ form.errors.agree_terms }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="form.processing || !form.agree_terms"
                            class="px-4 py-2 rounded-lg bg-accent-500 text-white text-sm font-medium hover:bg-accent-600 disabled:opacity-50 transition">
                            Submit application
                        </button>
                        <Link :href="route('loans.index')" class="text-sm text-gray-500 hover:underline">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthLayout>
</template>
