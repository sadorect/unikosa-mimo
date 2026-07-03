<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ current: { type: String, default: '' } });
</script>

<template>
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <Link href="/" class="text-xl font-bold text-accent-500 tracking-wide shrink-0">
                {{ $page.props.settings?.site_name || 'UNIKOSA' }}
            </Link>

            <div class="hidden sm:flex items-center gap-6 text-sm font-medium text-gray-600 dark:text-gray-300">
                <Link href="/" class="hover:text-accent-500 transition" :class="{ 'text-accent-500': current === 'home' }">Home</Link>
                <Link :href="route('blog.index')" class="hover:text-accent-500 transition" :class="{ 'text-accent-500': current === 'blog' }">Blog</Link>
                <Link :href="route('transparency.index')" class="hover:text-accent-500 transition" :class="{ 'text-accent-500': current === 'transparency' }">Transparency</Link>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <template v-if="$page.props.auth?.user">
                    <Link :href="route('dashboard')" class="bg-accent-500 hover:bg-accent-600 text-white px-4 py-2 rounded-full text-sm font-semibold transition">
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="hidden sm:inline text-sm text-gray-500 hover:text-accent-500 transition">Sign In</Link>
                    <Link :href="route('register')" class="bg-accent-500 hover:bg-accent-600 text-white px-4 py-2 rounded-full text-sm font-semibold transition">
                        Join
                    </Link>
                </template>
            </div>
        </div>
    </nav>
</template>
