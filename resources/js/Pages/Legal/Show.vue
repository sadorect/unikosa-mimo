<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { computed } from 'vue';

const props = defineProps({ title: String, body: String });

const currentMap = { 'legal.privacy': 'privacy', 'legal.terms': 'terms' };
const current = computed(() => currentMap[route().current()] || '');

const descriptions = {
    'Privacy Policy': "How the UNIKOSA alumni association collects, uses, and protects your personal data.",
    'Terms of Service': 'The terms and conditions governing use of the UNIKOSA alumni network.',
};
</script>

<template>
    <PublicLayout :current="current">
        <SeoHead :title="title" :description="descriptions[title] || `${title} — UNIKOSA Alumni Association`" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ title }}</h1>
            <div v-if="body" class="prose dark:prose-invert max-w-none" v-html="body"></div>
            <p v-else class="text-gray-500">Content for this page hasn't been added yet.</p>
        </div>
    </PublicLayout>
</template>
