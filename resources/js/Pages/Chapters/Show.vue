<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ chapter: Object, members: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="chapter.name" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('chapters.index')" class="text-accent-600 hover:text-accent-700 text-sm">Chapters</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">{{ chapter.name }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ chapter.name }}</h1>
                    <div class="text-gray-500">{{ chapter.city ? chapter.city + ', ' : '' }}{{ chapter.country }}</div>
                    <p v-if="chapter.description" class="text-gray-600 dark:text-gray-400 mt-2">{{ chapter.description }}</p>
                    <div v-if="chapter.head" class="mt-3 text-sm text-gray-500">
                        Head: <span class="font-medium text-gray-700 dark:text-gray-300">{{ chapter.head.name }}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">{{ chapter.members_count }} members</div>
                </div>
                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-4">Members</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="member in members.data" :key="member.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold">
                            {{ member.name.charAt(0) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ member.name }}</div>
                            <div class="text-xs text-gray-500">{{ member.profession || 'Alumni' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
