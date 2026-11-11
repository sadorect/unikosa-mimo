<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({ payment: Object });

const state = computed(() => {
    switch (props.payment?.status) {
        case 'successful':
            return { title: 'Payment Successful', text: 'Thank you for your payment.', tone: 'green' };
        case 'failed':
            return { title: 'Payment Not Completed', text: 'The payment did not go through and you have not been charged. You can try again.', tone: 'red' };
        case 'pending':
            return { title: 'Confirming Your Payment', text: 'We are waiting for the payment provider to confirm. This page will update by itself.', tone: 'amber' };
        default:
            return { title: 'Payment Not Found', text: 'We could not find that payment on your account. Check your payment history.', tone: 'red' };
    }
});

const tones = {
    green: 'bg-green-100 dark:bg-green-900/30 text-green-500',
    amber: 'bg-amber-100 dark:bg-amber-900/30 text-amber-500',
    red: 'bg-red-100 dark:bg-red-900/30 text-red-500',
};

// A Stripe payment is confirmed by webhook, usually seconds after the redirect.
let timer = null;
let polls = 0;
onMounted(() => {
    if (props.payment?.status !== 'pending') return;
    timer = setInterval(() => {
        if (++polls > 20 || props.payment?.status !== 'pending') return clearInterval(timer);
        router.reload({ only: ['payment'] });
    }, 3000);
});
onBeforeUnmount(() => clearInterval(timer));

const downloadReceipt = () => {
    window.location.href = route('payments.receipt', props.payment.id);
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="state.title" />
        <div class="py-12">
            <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4" :class="tones[state.tone]">
                        <svg v-if="state.tone === 'green'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else-if="state.tone === 'amber'" class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ state.title }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ state.text }}</p>

                    <div v-if="payment" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 text-left">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Reference:</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100 break-all">{{ payment.payment_reference }}</span>
                            <span class="text-gray-500">Amount:</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ payment.currency }} {{ (payment.amount / 100).toFixed(2) }}</span>
                            <span class="text-gray-500">Method:</span>
                            <span class="text-gray-900 dark:text-gray-100 capitalize">{{ payment.payment_method }}</span>
                            <span class="text-gray-500">Status:</span>
                            <span class="font-medium capitalize" :class="tones[state.tone].split(' ').pop()">{{ payment.status }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 justify-center">
                        <button v-if="payment?.status === 'successful'" @click="downloadReceipt" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-4 py-2 rounded">
                            Download Receipt
                        </button>
                        <Link :href="route('payments.history')" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium px-4 py-2 rounded">
                            Payment History
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
