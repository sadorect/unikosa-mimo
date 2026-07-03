<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ status: Number });

const content = computed(() => ({
    403: {
        title: "You don't have access to this page",
        message: "You're signed in, but your account doesn't have permission to view this page. If you think this is a mistake, reach out to an admin.",
    },
    404: {
        title: 'Page not found',
        message: "We couldn't find what you're looking for. It may have been moved, deleted, or the link might be incorrect.",
    },
    419: {
        title: 'Your session expired',
        message: 'For your security, this page timed out. Please go back and try again.',
    },
    429: {
        title: 'Too many requests',
        message: "You've made too many requests in a short time. Please wait a moment and try again.",
    },
    500: {
        title: 'Something went wrong',
        message: "An unexpected error occurred on our end. We've been notified and are looking into it — please try again shortly.",
    },
    503: {
        title: 'Down for maintenance',
        message: "We're carrying out some scheduled maintenance right now. Please check back in a few minutes.",
    },
}[props.status] || {
    title: 'Unexpected error',
    message: 'Something went wrong. Please try again.',
}));
</script>

<template>
    <Head :title="content.title" />
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="text-xl font-bold text-accent-500 mb-8">{{ $page.props.settings?.site_name || 'UNIKOSA' }}</div>

            <div class="text-6xl font-bold text-gray-200 dark:text-gray-700 mb-4">{{ status }}</div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ content.title }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ content.message }}</p>

            <div class="flex items-center justify-center gap-4 text-sm">
                <Link href="/" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-5 py-2 rounded-lg transition">
                    Go home
                </Link>
                <Link :href="route('legal.contact')" class="text-accent-600 hover:text-accent-700 font-medium">
                    Contact support
                </Link>
            </div>
        </div>
    </div>
</template>
