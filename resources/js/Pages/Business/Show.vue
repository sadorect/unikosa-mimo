<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ business: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="business.name" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('business.index')" class="text-amber-600 hover:text-amber-700 text-sm">Business Directory</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">{{ business.name }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <div class="flex items-start justify-between mb-4">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ business.name }}</h1>
                        <span class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">{{ business.category }}</span>
                    </div>
                    <div class="text-sm text-gray-500 mb-4">Listed by {{ business.owner?.name }}</div>
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-6">{{ business.description }}</div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <div v-if="business.website" class="text-sm">
                            <span class="text-gray-500">Website:</span>
                            <a :href="business.website" target="_blank" class="text-amber-600 hover:text-amber-700 ml-2">{{ business.website }}</a>
                        </div>
                        <div v-if="business.contact_email" class="text-sm">
                            <span class="text-gray-500">Email:</span>
                            <a :href="'mailto:' + business.contact_email" class="text-amber-600 hover:text-amber-700 ml-2">{{ business.contact_email }}</a>
                        </div>
                        <div v-if="business.contact_phone" class="text-sm">
                            <span class="text-gray-500">Phone:</span>
                            <span class="text-gray-900 dark:text-gray-100 ml-2">{{ business.contact_phone }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
