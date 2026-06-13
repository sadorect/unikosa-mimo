<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ post: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="post.title" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('blog.index')" class="text-amber-600 hover:text-amber-700 text-sm">Blog</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm truncate">{{ post.title }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <article class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <div v-if="post.featured_image" class="h-64 bg-gray-200 dark:bg-gray-700">
                        <img :src="post.featured_image" class="w-full h-full object-cover" />
                    </div>
                    <div class="p-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">{{ post.title }}</h1>
                        <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 font-bold">
                                {{ post.author?.name?.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ post.author?.name }}</div>
                                <div class="text-xs text-gray-500">{{ post.published_at }}</div>
                            </div>
                        </div>
                        <div class="flex gap-2 mb-6">
                            <span v-for="cat in post.categories" :key="cat.id"
                                class="px-2 py-1 text-xs bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/30 dark:text-amber-300">
                                {{ cat.name }}
                            </span>
                        </div>
                        <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ post.body }}</div>
                    </div>
                </article>
            </div>
        </div>
    </AuthLayout>
</template>
