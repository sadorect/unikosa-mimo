<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    href: String,
    active: Boolean,
});
</script>

<template>
    <Link :href="href"
        class="block ps-3 pe-4 py-2 border-l-4 text-sm font-medium transition duration-150 ease-in-out"
        :class="active
            ? 'border-accent-400 bg-accent-50 text-accent-700 dark:bg-accent-900/30 dark:text-accent-300'
            : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700'"
    >
        <slot />
    </Link>
</template>
