<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({ payment: Object });

const downloadReceipt = () => {
    window.location.href = route('payments.receipt', payment.id);
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Payment Successful" />
        <div class="py-12">
            <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
                    <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Payment Successful!</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Thank you for your payment.</p>

                    <div v-if="payment" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 text-left">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Reference:</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100">{{ payment.payment_reference }}</span>
                            <span class="text-gray-500">Amount:</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ payment.currency }} {{ (payment.amount / 100).toFixed(2) }}</span>
                            <span class="text-gray-500">Method:</span>
                            <span class="text-gray-900 dark:text-gray-100 capitalize">{{ payment.payment_method }}</span>
                            <span class="text-gray-500">Status:</span>
                            <span class="text-green-600 font-medium capitalize">{{ payment.status }}</span>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-center">
                        <button @click="downloadReceipt" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2 rounded">
                            Download Receipt
                        </button>
                        <button @click="router.visit(route('dashboard'))" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium px-4 py-2 rounded">
                            Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
