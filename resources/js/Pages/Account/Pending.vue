<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({ status: String });
</script>

<template>
    <Head title="Account Status" />
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 text-center">
            <div class="text-xl font-bold text-accent-500 mb-6">{{ $page.props.settings?.site_name || 'UNIKOSA' }}</div>

            <template v-if="status === 'rejected'">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Account not approved</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Your membership application wasn't approved. If you think this is a mistake, please contact us.
                </p>
            </template>
            <template v-else>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Your account is pending approval</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Thanks for signing up! An administrator needs to review and approve your membership before you can
                    access the site. You'll be able to sign in as usual once that's done.
                </p>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    We've emailed you a confirmation of this, and we'll email you again as soon as your account has been
                    reviewed &mdash; whether it is approved or declined.
                </p>
            </template>

            <div class="mt-8 flex items-center justify-center gap-4 text-sm">
                <Link :href="route('legal.contact')" class="text-accent-600 hover:text-accent-700 font-medium">Contact us</Link>
                <span class="text-gray-300">|</span>
                <Link :href="route('logout')" method="post" as="button" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium">
                    Sign out
                </Link>
            </div>
        </div>
    </div>
</template>
