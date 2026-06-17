<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ posts: Object, groups: Array });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Forum" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Forum</h2>
                <Link :href="route('forum.create')" class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded">New Post</Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-3 space-y-4">
                        <div v-for="post in posts.data" :key="post.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition">
                            <Link :href="route('forum.show', post.id)" class="block">
                                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                                    <div class="w-8 h-8 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold text-xs">
                                        {{ post.author?.name?.charAt(0) }}
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ post.author?.name }}</span>
                                    <span>&middot;</span>
                                    <span class="text-accent-600">{{ post.group?.name }}</span>
                                </div>
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">{{ post.title }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 line-clamp-2">{{ post.body }}</p>
                                <div class="mt-3 flex items-center gap-4 text-xs text-gray-400">
                                    <span>{{ post.replies_count || 0 }} replies</span>
                                    <span>{{ post.views_count || 0 }} views</span>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Groups</h3>
                            <div class="space-y-2">
                                <Link v-for="group in groups" :key="group.id" :href="route('forum.group', group.slug)"
                                    class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm">
                                    {{ group.name }}
                                    <span class="text-xs text-gray-400 ml-1">({{ group.posts_count || 0 }})</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
