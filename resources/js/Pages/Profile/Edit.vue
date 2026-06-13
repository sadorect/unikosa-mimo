<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Edit Profile" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Edit Profile</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <p class="text-gray-600 dark:text-gray-400">Profile editing will be available through the admin panel or a future update.</p>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
