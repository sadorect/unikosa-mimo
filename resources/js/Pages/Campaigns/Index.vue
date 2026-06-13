<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ campaigns: Object });

const progressPercent = (campaign) => {
    if (!campaign.target_amount) return 0;
    return Math.min(100, Math.round((campaign.raised_amount / campaign.target_amount) * 100));
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Campaigns" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Fundraising Campaigns</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="campaign in campaigns.data" :key="campaign.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">{{ campaign.title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">{{ campaign.description }}</p>
                            <div class="mb-3">
                                <div class="flex justify-between text-sm text-gray-500 mb-1">
                                    <span>{{ campaign.currency }} {{ (campaign.raised_amount / 100).toFixed(0) }}</span>
                                    <span>{{ campaign.currency }} {{ (campaign.target_amount / 100).toFixed(0) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="bg-amber-500 h-2 rounded-full" :style="{ width: progressPercent(campaign) + '%' }"></div>
                                </div>
                            </div>
                            <Link :href="route('campaigns.show', campaign)" class="block text-center bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-4 rounded">
                                Contribute
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
