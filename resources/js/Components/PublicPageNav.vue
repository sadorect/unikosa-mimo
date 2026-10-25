<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({ current: { type: String, default: '' } });

// Below sm the links have nowhere to sit on the bar, so they move into a
// drawer instead of disappearing.
const mobileOpen = ref(false);
const close = () => { mobileOpen.value = false; };

let stopNavigationListener;
const onKeydown = (event) => {
    if (event.key === 'Escape') close();
};

onMounted(() => {
    stopNavigationListener = router.on('navigate', close);
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    stopNavigationListener?.();
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <Link href="/" class="text-xl font-bold text-accent-500 tracking-wide shrink-0 truncate">
                {{ $page.props.settings?.site_name || 'UNIKOSA' }}
            </Link>

            <div class="hidden sm:flex items-center gap-6 text-sm font-medium text-gray-600 dark:text-gray-300">
                <Link href="/" class="hover:text-accent-500 transition" :class="{ 'text-accent-500': current === 'home' }">Home</Link>
                <Link :href="route('blog.index')" class="hover:text-accent-500 transition" :class="{ 'text-accent-500': current === 'blog' }">Blog</Link>
                <Link :href="route('transparency.index')" class="hover:text-accent-500 transition" :class="{ 'text-accent-500': current === 'transparency' }">Transparency</Link>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
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

                <button type="button" @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen" aria-label="Toggle navigation menu"
                    class="sm:hidden p-2 -mr-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="mobileOpen"
            class="sm:hidden border-t border-gray-100 dark:border-gray-700 px-4 py-3 space-y-1 overflow-y-auto overscroll-contain max-h-[calc(100vh-4rem)]"
            style="max-height: calc(100dvh - 4rem)">
            <Link href="/" class="block py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500" :class="{ 'text-accent-500': current === 'home' }">Home</Link>
            <Link :href="route('blog.index')" class="block py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500" :class="{ 'text-accent-500': current === 'blog' }">Blog</Link>
            <Link :href="route('transparency.index')" class="block py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500" :class="{ 'text-accent-500': current === 'transparency' }">Transparency</Link>
            <Link v-if="!$page.props.auth?.user" :href="route('login')" class="block py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-accent-500">Sign In</Link>
        </div>
    </nav>
</template>
