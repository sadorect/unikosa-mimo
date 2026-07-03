<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/date';

defineProps({ payments: Object });

const statusBadge = (status) => {
    const classes = {
        successful: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return classes[status] || classes.pending;
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Payment History" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Payment History</h2></template>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(payment.paid_at || payment.created_at) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ payment.payable?.name || payment.payable?.title || 'Payment' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ payment.currency }} {{ (payment.amount / 100).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 capitalize">{{ payment.payment_method }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[statusBadge(payment.status), 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <Link v-if="payment.status === 'successful'" :href="route('payments.receipt', payment.id)"
                                        class="text-accent-600 hover:text-accent-700 text-sm">
                                        Download
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="!payments.data.length" class="p-8 text-center text-gray-500">No payments found.</div>
                </div>

                <Pagination :paginator="payments" />
            </div>
        </div>
    </AuthLayout>
</template>
