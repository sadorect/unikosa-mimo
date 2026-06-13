<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    href: String,
    method: { type: String, default: 'get' },
    as: { type: String, default: 'a' },
});
</script>

<template>
    <button v-if="as === 'button'" :type="as === 'button' ? 'button' : 'submit'"
        class="block w-full text-start px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition duration-150 ease-in-out">
        <slot />
    </button>
    <Link v-else :href="href" :method="method" as="button"
        class="block w-full text-start px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition duration-150 ease-in-out">
        <slot />
    </Link>
</template>
