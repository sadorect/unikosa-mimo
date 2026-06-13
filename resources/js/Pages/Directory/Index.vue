<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    members: Object,
    sets: Array,
    chapters: Array,
    filters: Object,
});

const search = ref('');
const selectedSet = ref('');
const selectedChapter = ref('');
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
                    <form class="mb-6 flex flex-wrap gap-4">
                        <input v-model="search" type="text" placeholder="Search by name, profession, skills..."
                            class="flex-1 min-w-[200px] border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600">
                        <select v-model="selectedSet" class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                            <option value="">All Sets</option>
                            <option v-for="set in sets" :key="set.id" :value="set.id">{{ set.name }}</option>
                        </select>
                        <select v-model="selectedChapter" class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                            <option value="">All Chapters</option>
                            <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">{{ chapter.name }}</option>
                        </select>
                    </form>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="member in members.data" :key="member.id"
                            class="border dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 font-bold">
                                    {{ member.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ member.name }}</div>
                                    <div class="text-sm text-gray-500">{{ member.profession || 'Alumni' }}</div>
                                </div>
                            </div>
                            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                <div v-if="member.graduating_set" class="flex items-center gap-1">
                                    <span class="font-medium">Set:</span> {{ member.graduating_set.name }}
                                </div>
                                <div v-if="member.chapter" class="flex items-center gap-1">
                                    <span class="font-medium">Chapter:</span> {{ member.chapter.name }}
                                </div>
                                <div v-if="member.country" class="flex items-center gap-1">
                                    <span class="font-medium">Location:</span> {{ member.city ? member.city + ', ' : '' }}{{ member.country }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
