<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ posts: Object, categories: Array, activeCategory: [String, Number, null] });

const page = usePage();
const isAuthed = computed(() => !!page.props.auth?.user);
const layout = computed(() => isAuthed.value ? AuthLayout : PublicLayout);

const filterCategory = (categoryId) => {
    router.get(route('blog.index'), { category: categoryId }, { preserveState: true, replace: true });
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

// Deterministic accent gradient per post for image-less cards.
const gradients = [
    'from-amber-400 to-orange-500', 'from-sky-400 to-indigo-500', 'from-emerald-400 to-teal-500',
    'from-rose-400 to-pink-500', 'from-violet-400 to-purple-500', 'from-cyan-400 to-blue-500',
];
const gradientFor = (id) => gradients[id % gradients.length];

const featured = computed(() => props.posts.data[0] || null);
const rest = computed(() => props.posts.data.slice(1));
</script>

<template>
    <component :is="layout" :auth="$page.props.auth" :settings="$page.props.settings" current="blog">
        <SeoHead
            title="Blog & News"
            description="Stories, announcements, and updates from the alumni community."
        />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Blog &amp; News</h2>
                <Link v-if="isAuthed" :href="route('blog.create')" class="inline-flex items-center gap-1.5 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Write Post
                </Link>
                <Link v-else :href="route('login')" class="text-sm text-accent-600 hover:text-accent-700 font-medium">
                    Sign in to write a post
                </Link>
            </div>
        </template>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <div class="lg:col-span-3">
                        <div v-if="!posts.data.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-12 text-center text-gray-500">
                            No posts published yet.
                        </div>

                        <!-- Featured post -->
                        <Link v-if="featured" :href="route('blog.show', featured.slug)"
                            class="group block bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition mb-8">
                            <div class="h-64 overflow-hidden">
                                <img v-if="featured.featured_image" :src="featured.featured_image" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                <div v-else :class="['w-full h-full bg-gradient-to-br flex items-center justify-center', gradientFor(featured.id)]">
                                    <span class="text-white/90 text-5xl font-black tracking-tight px-8 text-center line-clamp-2">{{ featured.title }}</span>
                                </div>
                            </div>
                            <div class="p-7">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span v-for="cat in featured.categories" :key="cat.id"
                                        class="px-2.5 py-0.5 text-xs font-medium bg-accent-100 text-accent-800 rounded-full dark:bg-accent-900/30 dark:text-accent-300">{{ cat.name }}</span>
                                </div>
                                <h3 class="font-bold text-2xl text-gray-900 dark:text-gray-100 mb-2 group-hover:text-accent-600 transition">{{ featured.title }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ featured.excerpt }}</p>
                                <div class="flex items-center gap-3 text-sm">
                                    <div class="w-8 h-8 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold text-xs">{{ featured.author?.name?.charAt(0) }}</div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ featured.author?.name }}</span>
                                    <span class="text-gray-400">·</span>
                                    <span class="text-gray-500">{{ formatDate(featured.published_at) }}</span>
                                </div>
                            </div>
                        </Link>

                        <!-- Remaining posts grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <Link v-for="post in rest" :key="post.id" :href="route('blog.show', post.slug)"
                                class="group flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition">
                                <div class="h-40 overflow-hidden">
                                    <img v-if="post.featured_image" :src="post.featured_image" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                    <div v-else :class="['w-full h-full bg-gradient-to-br flex items-center justify-center p-4', gradientFor(post.id)]">
                                        <span class="text-white/90 text-lg font-bold text-center line-clamp-3">{{ post.title }}</span>
                                    </div>
                                </div>
                                <div class="p-5 flex flex-col flex-1">
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        <span v-for="cat in post.categories" :key="cat.id"
                                            class="px-2 py-0.5 text-[11px] font-medium bg-accent-50 text-accent-700 rounded-full dark:bg-accent-900/20 dark:text-accent-300">{{ cat.name }}</span>
                                    </div>
                                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1.5 group-hover:text-accent-600 transition line-clamp-2">{{ post.title }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">{{ post.excerpt }}</p>
                                    <div class="mt-auto flex items-center gap-2 text-xs text-gray-500">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ post.author?.name }}</span>
                                        <span class="text-gray-400">·</span>
                                        <span>{{ formatDate(post.published_at) }}</span>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <Pagination :paginator="posts" />
                    </div>

                    <aside class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 sticky top-20">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Categories</h3>
                            <div class="space-y-1">
                                <button @click="filterCategory(null)"
                                    class="block w-full text-left px-3 py-2 rounded-lg text-sm transition"
                                    :class="!activeCategory ? 'bg-accent-100 text-accent-800 dark:bg-accent-900/30 dark:text-accent-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                    All Posts
                                </button>
                                <button v-for="cat in categories" :key="cat.id" @click="filterCategory(cat.id)"
                                    class="flex items-center justify-between w-full text-left px-3 py-2 rounded-lg text-sm transition"
                                    :class="activeCategory == cat.id ? 'bg-accent-100 text-accent-800 dark:bg-accent-900/30 dark:text-accent-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                    <span>{{ cat.name }}</span>
                                    <span class="text-xs text-gray-400">{{ cat.posts_count }}</span>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </component>
</template>
