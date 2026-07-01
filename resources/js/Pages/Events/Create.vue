<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ events: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Create Event" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Create Event</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="$inertia.post(route('events.store'), form)" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea v-model="form.description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                            <input v-model="form.location" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700"></div>
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start At</label>
                            <input v-model="form.start_at" type="datetime-local" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></div>
                        </div>
                        <button type="submit" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-6 py-2 rounded">Create Event</button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script>
export default {
    data() {
        return {
            form: { title: '', description: '', location: '', start_at: '', is_virtual: false, is_paid: false },
        };
    },
};
</script>
