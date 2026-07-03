<script setup>
import { Link } from '@inertiajs/vue3';

// items: [{ label, href }] — the last item is the current page (rendered plain).
defineProps({ items: { type: Array, default: () => [] } });
</script>

<template>
    <div class="flex items-center gap-2 text-sm">
        <Link v-if="items.length > 1" :href="items[items.length - 2].href"
            class="inline-flex items-center gap-1 text-accent-600 hover:text-accent-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Back
        </Link>
        <span v-if="items.length > 1" class="text-gray-300 dark:text-gray-600">|</span>
        <nav class="flex items-center gap-1.5 min-w-0">
            <template v-for="(item, i) in items" :key="i">
                <Link v-if="item.href && i < items.length - 1" :href="item.href"
                    class="text-gray-500 dark:text-gray-400 hover:text-accent-600 truncate">{{ item.label }}</Link>
                <span v-else class="text-gray-700 dark:text-gray-300 truncate">{{ item.label }}</span>
                <svg v-if="i < items.length - 1" class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </template>
        </nav>
    </div>
</template>
