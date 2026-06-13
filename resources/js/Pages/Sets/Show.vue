<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ set: Object, members: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="set.name" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('sets.index')" class="text-amber-600 hover:text-amber-700 text-sm">Sets</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">{{ set.name }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h1 class="text-3xl font-bold text-amber-500 mb-2">{{ set.name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ set.description }}</p>
                    <div v-if="set.rep" class="mt-3 text-sm text-gray-500">
                        Representative: <span class="font-medium text-gray-700 dark:text-gray-300">{{ set.rep.name }}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">{{ set.members_count }} members</div>
                </div>
                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-4">Members</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="member in members.data" :key="member.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 font-bold">
                            {{ member.name.charAt(0) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ member.name }}</div>
                            <div class="text-xs text-gray-500">{{ member.profession || 'Alumni' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
