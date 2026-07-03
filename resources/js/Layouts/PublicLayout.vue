<script setup>
import PublicPageNav from '@/Components/PublicPageNav.vue';
import Footer from '@/Components/Footer.vue';

defineProps({ current: { type: String, default: '' } });
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <PublicPageNav :current="current" />
        <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>
        <main><slot /></main>
        <Footer />
    </div>
</template>
