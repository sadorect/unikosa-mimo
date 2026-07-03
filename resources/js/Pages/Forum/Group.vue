<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { timeAgo } from '@/lib/date';

defineProps({ group: Object, posts: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="group.name" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('forum.index')" class="text-accent-600 hover:text-accent-700 text-sm">Forum</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">{{ group.name }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ group.name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">{{ group.description }}</p>
                </div>
                <div class="space-y-4">
                    <Link v-for="post in posts.data" :key="post.id" :href="route('forum.show', post.id)"
                        class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition">
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ post.author?.name }}</span>
                        </div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ post.title }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ post.body }}</p>
                        <div class="mt-2 text-xs text-gray-400">{{ timeAgo(post.created_at) }}</div>
                    </Link>
                </div>

                <Pagination :paginator="posts" />
            </div>
        </div>
    </AuthLayout>
</template>
