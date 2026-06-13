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
        :class="active ? 'border-amber-400 bg-amber-50 text-amber-700' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50'"
    >
        <slot />
    </Link>
</template>
