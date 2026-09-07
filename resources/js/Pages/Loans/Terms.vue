<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ program: Object, term: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="`${program.name} — terms`" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ program.name }} — terms of operation</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <p class="text-xs text-gray-500 mb-4">
                        Version {{ term.version }}<span v-if="term.published_at"> · published {{ new Date(term.published_at).toLocaleDateString() }}</span>
                    </p>
                    <div class="prose prose-sm dark:prose-invert max-w-none" v-html="term.body"></div>
                </div>
                <Link :href="route('loans.index')" class="text-sm text-accent-600 hover:underline">Back to loans</Link>
            </div>
        </div>
    </AuthLayout>
</template>
