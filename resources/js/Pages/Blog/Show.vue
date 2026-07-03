<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ post: Object, prev: Object, next: Object });

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

const gradients = [
    'from-amber-400 to-orange-500', 'from-sky-400 to-indigo-500', 'from-emerald-400 to-teal-500',
    'from-rose-400 to-pink-500', 'from-violet-400 to-purple-500', 'from-cyan-400 to-blue-500',
];
const heroGradient = gradients[props.post.id % gradients.length];

const page = usePage();
const layout = computed(() => page.props.auth?.user ? AuthLayout : PublicLayout);
const seoDescription = computed(() => props.post.excerpt || `Read "${props.post.title}" on the ${page.props.settings?.site_name || 'UNIKOSA'} alumni blog.`);
const articleJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: props.post.title,
    description: seoDescription.value,
    ...(props.post.featured_image ? { image: [props.post.featured_image] } : {}),
    datePublished: props.post.published_at,
    ...(props.post.author?.name ? { author: { '@type': 'Person', name: props.post.author.name } } : {}),
}));
</script>

<template>
    <component :is="layout" :auth="$page.props.auth" :settings="$page.props.settings" current="blog">
        <SeoHead
            :title="post.title"
            :description="seoDescription"
            :image="post.featured_image"
            type="article"
            :published-time="post.published_at"
            :author="post.author?.name"
            :json-ld="articleJsonLd"
        />
        <template #header>
            <Breadcrumb :items="[{ label: 'Blog', href: route('blog.index') }, { label: post.title }]" />
        </template>
        <div class="py-10">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <article class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="h-72 overflow-hidden">
                        <img v-if="post.featured_image" :src="post.featured_image" class="w-full h-full object-cover" />
                        <div v-else :class="['w-full h-full bg-gradient-to-br flex items-center justify-center p-8', heroGradient]">
                            <span class="text-white text-4xl font-black text-center tracking-tight line-clamp-3">{{ post.title }}</span>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span v-for="cat in post.categories" :key="cat.id"
                                class="px-2.5 py-0.5 text-xs font-medium bg-accent-100 text-accent-800 rounded-full dark:bg-accent-900/30 dark:text-accent-300">{{ cat.name }}</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-5 leading-tight">{{ post.title }}</h1>
                        <div class="flex items-center gap-3 mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="w-11 h-11 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold">
                                {{ post.author?.name?.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ post.author?.name }}</div>
                                <div class="text-xs text-gray-500">{{ formatDate(post.published_at) }} · {{ post.views_count || 0 }} views</div>
                            </div>
                        </div>
                        <div class="prose prose-lg dark:prose-invert max-w-none text-gray-700 dark:text-gray-300" v-html="post.body"></div>
                    </div>
                </article>

                <!-- Prev / Next navigation -->
                <nav v-if="prev || next" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                    <Link v-if="prev" :href="route('blog.show', prev.slug)"
                        class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 hover:shadow-md transition">
                        <div class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            Previous
                        </div>
                        <div class="font-medium text-gray-800 dark:text-gray-200 group-hover:text-accent-600 line-clamp-1">{{ prev.title }}</div>
                    </Link>
                    <span v-else></span>
                    <Link v-if="next" :href="route('blog.show', next.slug)"
                        class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 hover:shadow-md transition text-right">
                        <div class="text-xs text-gray-400 mb-1 flex items-center justify-end gap-1">
                            Next
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                        <div class="font-medium text-gray-800 dark:text-gray-200 group-hover:text-accent-600 line-clamp-1">{{ next.title }}</div>
                    </Link>
                </nav>

                <div class="mt-6 text-center">
                    <Link :href="route('blog.index')" class="text-accent-600 hover:text-accent-700 text-sm font-medium">← Back to all posts</Link>
                </div>
            </div>
        </div>
    </component>
</template>
