<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ dues: Array });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="My Dues" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">My Dues</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <div v-for="due in dues" :key="due.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 flex items-center justify-between gap-4">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ due.name }}</div>
                        <div class="text-sm text-gray-500 capitalize">{{ due.frequency.replace('_', ' ') }}</div>
                        <p v-if="due.description" class="text-sm text-gray-500 mt-1">{{ due.description }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-lg font-bold text-accent-600">{{ due.currency }} {{ (due.amount / 100).toFixed(2) }}</div>
                        <Link :href="route('payments.dues.show', due.id)"
                            class="inline-block mt-2 bg-accent-500 hover:bg-accent-600 text-white text-sm font-semibold py-1.5 px-4 rounded-lg transition">
                            Pay Now
                        </Link>
                    </div>
                </div>
                <div v-if="!dues.length" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center text-gray-500">
                    You have no outstanding dues.
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
