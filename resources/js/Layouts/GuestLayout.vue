<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
        <!-- Public nav -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="text-xl font-bold text-amber-500 tracking-wide">
                    {{ $page.props.settings?.site_name || 'UNIKOSA' }}
                </Link>
                <div class="flex items-center gap-6 text-sm font-medium text-gray-600 dark:text-gray-400">
                    <Link href="/" class="hover:text-amber-500 transition">Home</Link>
                    <Link :href="route('transparency.index')" class="hover:text-amber-500 transition">Transparency</Link>
                    <Link :href="route('login')" class="hover:text-amber-500 transition">Sign In</Link>
                    <Link :href="route('register')"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-1.5 rounded-full transition text-sm font-semibold">
                        Join Now
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Form content centered -->
        <div class="flex-1 flex items-center justify-center py-12 px-4">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </div>

        <!-- Minimal footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 py-4 text-center text-xs text-gray-400">
            &copy; {{ new Date().getFullYear() }} {{ $page.props.settings?.site_name || 'UNIKOSA' }} Alumni Association
        </footer>
    </div>
</template>
