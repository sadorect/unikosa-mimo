<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    href: String,
    active: Boolean,
});
</script>

<template>
    <Link :href="href"
        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
        :class="active ? 'border-accent-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
    >
        <slot />
    </Link>
</template>
