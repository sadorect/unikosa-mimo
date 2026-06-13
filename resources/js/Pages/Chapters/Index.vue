<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ chapters: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Chapters" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Diaspora Chapters</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="chapter in chapters" :key="chapter.id" :href="route('chapters.show', chapter.id)"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition block">
                        <div class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">{{ chapter.name }}</div>
                        <div class="text-sm text-gray-500">{{ chapter.city ? chapter.city + ', ' : '' }}{{ chapter.country }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ chapter.members_count }} members</div>
                        <p v-if="chapter.description" class="text-gray-600 dark:text-gray-400 mt-2 text-sm line-clamp-2">{{ chapter.description }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
