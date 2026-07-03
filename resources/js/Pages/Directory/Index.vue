<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    members: Object,
    sets: Array,
    chapters: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedSet = ref(props.filters?.set_id || '');
const selectedChapter = ref(props.filters?.chapter_id || '');

const applyFilters = () => {
    router.get(route('directory'), {
        search: search.value || undefined,
        set_id: selectedSet.value || undefined,
        chapter_id: selectedChapter.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

let searchDebounce = null;
watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 350);
});
watch([selectedSet, selectedChapter], applyFilters);

const gradients = [
    'from-amber-400 to-orange-500', 'from-sky-400 to-indigo-500', 'from-emerald-400 to-teal-500',
    'from-rose-400 to-pink-500', 'from-violet-400 to-purple-500', 'from-cyan-400 to-blue-500',
];
const gradientFor = (id) => gradients[id % gradients.length];
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Member Directory" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Member Directory</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="applyFilters" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <input v-model="search" type="search" placeholder="Search by name, profession, skills..."
                            class="w-full sm:col-span-1 border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:bg-gray-700 dark:border-gray-600">
                        <select v-model="selectedSet" class="w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                            <option value="">All Sets</option>
                            <option v-for="set in sets" :key="set.id" :value="set.id">{{ set.name }}</option>
                        </select>
                        <select v-model="selectedChapter" class="w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                            <option value="">All Chapters</option>
                            <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">{{ chapter.name }}</option>
                        </select>
                    </form>

                    <div v-if="!members.data.length" class="text-center py-16 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        No members match your search yet.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link v-for="member in members.data" :key="member.id" :href="route('members.show', member.id)"
                            class="group relative block bg-white dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg hover:border-accent-300 dark:hover:border-accent-700 hover:-translate-y-0.5 transition-all duration-200">
                            <svg class="absolute top-4 right-4 w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-accent-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>

                            <div class="flex items-center gap-3 pr-5">
                                <div class="relative shrink-0">
                                    <img v-if="member.avatar" :src="member.avatar" :alt="member.name"
                                        class="w-14 h-14 rounded-full object-cover ring-2 ring-white dark:ring-gray-800 shadow-sm" />
                                    <div v-else :class="['w-14 h-14 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-lg ring-2 ring-white dark:ring-gray-800 shadow-sm', gradientFor(member.id)]">
                                        {{ member.name.charAt(0) }}
                                    </div>
                                    <span v-if="member.account_claimed" title="Verified member"
                                        class="absolute -bottom-0.5 -right-0.5 w-5 h-5 bg-accent-500 rounded-full flex items-center justify-center ring-2 ring-white dark:ring-gray-800">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ member.name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.profession || 'Alumni' }}</div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-1.5">
                                <span v-if="member.graduating_set" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-accent-50 text-accent-700 dark:bg-accent-900/20 dark:text-accent-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0112 20.055 12.083 12.083 0 015.84 10.578L12 14z" /></svg>
                                    {{ member.graduating_set.name }}
                                </span>
                                <span v-if="member.chapter" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21h2m0 0h10M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8v-4a1 1 0 011-1h0a1 1 0 011 1v4" /></svg>
                                    {{ member.chapter.name }}
                                </span>
                                <span v-if="member.country" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700/60 dark:text-gray-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ member.city ? member.city + ', ' : '' }}{{ member.country }}
                                </span>
                            </div>

                            <div v-if="member.skills?.length" class="mt-3 flex flex-wrap gap-1.5">
                                <span v-for="skill in member.skills.slice(0, 3)" :key="skill"
                                    class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-50 text-gray-500 border border-gray-200 dark:bg-gray-700/40 dark:text-gray-400 dark:border-gray-600">
                                    {{ skill }}
                                </span>
                                <span v-if="member.skills.length > 3" class="px-2 py-0.5 rounded-md text-[11px] font-medium text-gray-400">
                                    +{{ member.skills.length - 3 }} more
                                </span>
                            </div>
                        </Link>
                    </div>

                    <Pagination :paginator="members" />
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
