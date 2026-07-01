<script setup>
import { computed } from 'vue';
import { themePref, setThemePref } from '@/theme';

// Cycle: auto -> light -> dark -> auto
const order = ['auto', 'light', 'dark'];
const current = computed(() => themePref.value || 'auto');

const meta = {
    auto: { label: 'Auto theme', icon: '🌗' },
    light: { label: 'Light theme', icon: '☀️' },
    dark: { label: 'Dark theme', icon: '🌙' },
};

function cycle() {
    const next = order[(order.indexOf(current.value) + 1) % order.length];
    setThemePref(next === 'auto' ? null : next);
}
</script>

<template>
    <button
        type="button"
        @click="cycle"
        :title="meta[current].label"
        :aria-label="meta[current].label"
        class="inline-flex items-center justify-center h-9 w-9 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
    >
        <span class="text-base leading-none">{{ meta[current].icon }}</span>
    </button>
</template>
