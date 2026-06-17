<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ posts: Object, categories: Array, activeCategory: [String, Number, null] });

const filterCategory = (categoryId) => {
    router.get(route('blog.index'), { category: categoryId }, { preserveState: true, replace: true });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Blog" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Blog & News</h2>
                <Link :href="route('blog.create')" class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded">Write Post</Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-3 space-y-6">
                        <div v-for="post in posts.data" :key="post.id"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                            <div v-if="post.featured_image" class="h-48 bg-gray-200 dark:bg-gray-700">
                                <img :src="post.featured_image" class="w-full h-full object-cover" />
                            </div>
                            <div class="p-6">
                                <Link :href="route('blog.show', post.slug)" class="block">
                                    <h3 class="font-semibold text-xl text-gray-900 dark:text-gray-100 mb-2">{{ post.title }}</h3>
                                </Link>
                                <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ post.author?.name }}</span>
                                    <span>&middot;</span>
                                    <span>{{ post.published_at }}</span>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">{{ post.excerpt }}</p>
                                <div class="mt-3 flex gap-2">
                                    <span v-for="cat in post.categories" :key="cat.id"
                                        class="px-2 py-1 text-xs bg-accent-100 text-accent-800 rounded-full dark:bg-accent-900/30 dark:text-accent-300">
                                        {{ cat.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Categories</h3>
                            <div class="space-y-1">
                                <button @click="filterCategory(null)"
                                    class="block w-full text-left px-3 py-2 rounded text-sm transition"
                                    :class="!activeCategory ? 'bg-accent-100 text-accent-800' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                                    All Posts
                                </button>
                                <button v-for="cat in categories" :key="cat.id" @click="filterCategory(cat.id)"
                                    class="block w-full text-left px-3 py-2 rounded text-sm transition"
                                    :class="activeCategory == cat.id ? 'bg-accent-100 text-accent-800' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                                    {{ cat.name }}
                                    <span class="text-xs text-gray-400 ml-1">({{ cat.posts_count }})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
