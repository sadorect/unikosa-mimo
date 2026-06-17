<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const exportData = () => {
    window.location.href = route('profile.export.download');
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Export My Data" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Export My Data</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Download Your Data</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        You can download a copy of all your data stored on this platform, in compliance with GDPR and data protection regulations.
                        This export includes your profile, posts, events, payments, and more.
                    </p>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Data included in export:</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>- Profile information (name, email, phone, bio, skills)</li>
                            <li>- Graduating set and chapter membership</li>
                            <li>- Forum posts and replies</li>
                            <li>- Blog posts</li>
                            <li>- Event RSVPs</li>
                            <li>- Payment history</li>
                            <li>- Job listings</li>
                            <li>- Business listings</li>
                        </ul>
                    </div>

                    <button @click="exportData"
                        class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-6 py-3 rounded">
                        Download My Data (JSON)
                    </button>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
