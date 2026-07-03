<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    href: String,
    active: Boolean,
});
</script>

<template>
    <Link :href="href"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition duration-150 ease-in-out focus:outline-none"
        :class="active
            ? 'bg-accent-50 text-accent-700 dark:bg-accent-900/30 dark:text-accent-300'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50'"
    >
        <slot />
    </Link>
</template>
