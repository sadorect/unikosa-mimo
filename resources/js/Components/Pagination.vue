<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ paginator: { type: Object, required: true } });
</script>

<template>
    <div v-if="paginator.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Showing <span class="font-medium text-gray-700 dark:text-gray-300">{{ paginator.from }}</span>
            – <span class="font-medium text-gray-700 dark:text-gray-300">{{ paginator.to }}</span>
            of <span class="font-medium text-gray-700 dark:text-gray-300">{{ paginator.total }}</span>
        </p>
        <nav class="flex items-center gap-1 flex-wrap justify-center">
            <template v-for="(link, i) in paginator.links" :key="i">
                <span v-if="!link.url"
                    class="px-3 py-1.5 text-sm rounded-lg text-gray-300 dark:text-gray-600 cursor-default select-none"
                    v-html="link.label" />
                <Link v-else :href="link.url" preserve-scroll preserve-state
                    class="px-3 py-1.5 text-sm rounded-lg transition"
                    :class="link.active
                        ? 'bg-accent-500 text-white font-semibold'
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    v-html="link.label" />
            </template>
        </nav>
    </div>
</template>
