<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const summary = ref(null);
const loading = ref(true);
const dateFrom = ref('');
const dateTo = ref('');

const fetchSummary = async () => {
    loading.value = true;
    const params = new URLSearchParams();
    if (dateFrom.value) params.append('date_from', dateFrom.value);
    if (dateTo.value) params.append('date_to', dateTo.value);

    try {
        const res = await fetch(`/admin/financial-reports/summary?${params}`);
        summary.value = await res.json();
    } catch (e) {
        console.error(e);
    }
    loading.value = false;
};

const exportCsv = () => {
    const params = new URLSearchParams({ format: 'csv' });
    if (dateFrom.value) params.append('date_from', dateFrom.value);
    if (dateTo.value) params.append('date_to', dateTo.value);
    window.location.href = `/admin/financial-reports/export?${params}`;
};

onMounted(fetchSummary);
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Financial Reports" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Financial Reports</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                            <input v-model="dateFrom" type="date" class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                            <input v-model="dateTo" type="date" class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                        </div>
                        <button @click="fetchSummary" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-4 py-2 rounded">Filter</button>
                        <button @click="exportCsv" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-4 py-2 rounded">Export CSV</button>
                    </div>
                </div>

                <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

                <div v-else-if="summary" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <div class="text-sm text-gray-500">Total Raised</div>
                            <div class="text-3xl font-bold text-accent-500">{{ summary.total_transactions > 0 ? (summary.total_raised / 100).toFixed(2) : '0.00' }}</div>
                            <div class="text-xs text-gray-400">{{ summary.total_transactions }} transactions</div>
                        </div>
                        <div v-for="method in summary.by_method" :key="method.payment_method"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <div class="text-sm text-gray-500 capitalize">{{ method.payment_method }}</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ (method.total / 100).toFixed(2) }}</div>
                            <div class="text-xs text-gray-400">{{ method.count }} transactions</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">By Currency</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="curr in summary.by_currency" :key="curr.currency" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <div class="text-sm text-gray-500">{{ curr.currency }}</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ curr.currency }} {{ (curr.total / 100).toFixed(2) }}</div>
                                <div class="text-xs text-gray-400">{{ curr.count }} transactions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
