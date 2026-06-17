<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ sets: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Graduating Sets" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Graduating Sets</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="set in sets" :key="set.id" :href="route('sets.show', set.id)"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition block">
                        <div class="text-3xl font-bold text-accent-500 mb-2">{{ set.name }}</div>
                        <div class="text-sm text-gray-500">{{ set.members_count }} members</div>
                        <p v-if="set.description" class="text-gray-600 dark:text-gray-400 mt-2 text-sm line-clamp-2">{{ set.description }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
