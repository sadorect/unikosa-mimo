<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Celebrations from '@/Components/Celebrations.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ chapter: Object, members: Object, celebrations: { type: Array, default: () => [] } });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="chapter.name" />
        <template #header>
            <Breadcrumb :items="[{ label: 'Chapters', href: route('chapters.index') }, { label: chapter.name }]" />
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
                <Celebrations :items="celebrations" title="Celebrations in this chapter" class="mb-6" />
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

                <Pagination :paginator="members" />
            </div>
        </div>
    </AuthLayout>
</template>
