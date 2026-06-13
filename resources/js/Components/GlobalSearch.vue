<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const query = ref('');
const results = ref(null);
const showResults = ref(false);
const loading = ref(false);

let debounceTimer = null;

watch(query, (val) => {
    clearTimeout(debounceTimer);
    if (val.length < 2) {
        results.value = null;
        showResults.value = false;
        return;
    }
    debounceTimer = setTimeout(search, 300);
});

const search = async () => {
    loading.value = true;
    try {
        const res = await fetch(`/api/v1/search?q=${encodeURIComponent(query.value)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        results.value = await res.json();
        showResults.value = true;
    } catch (e) {
        console.error(e);
    }
    loading.value = false;
};

const close = () => {
    setTimeout(() => { showResults.value = false; }, 200);
};

const totalResults = () => {
    if (!results.value?.results) return 0;
    return Object.values(results.value.results).reduce((sum, arr) => sum + arr.length, 0);
};
</script>

<template>
    <div class="relative" @focusout="close">
        <div class="relative">
            <input v-model="query" type="text" placeholder="Search members, jobs, events, blog..."
                class="w-full border-gray-300 rounded-full shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 pl-10 text-sm" />
            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <div v-if="loading" class="absolute right-3 top-2.5">
                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
        </div>

        <div v-if="showResults && results?.results && totalResults() > 0"
            class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto">
            <div v-for="(items, type) in results.results" :key="type">
                <div v-if="items.length" class="border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50">{{ type }}</div>
                    <Link v-for="item in items" :key="item.id" :href="item.url"
                        class="block px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.title }}</div>
                        <div class="text-xs text-gray-500">{{ item.subtitle }}</div>
                    </Link>
                </div>
            </div>
        </div>

        <div v-if="showResults && totalResults() === 0 && query.length >= 2"
            class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 p-4 text-center text-gray-500 text-sm">
            No results found for "{{ query }}"
        </div>
    </div>
</template>
