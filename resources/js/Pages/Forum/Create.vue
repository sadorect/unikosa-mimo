<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ groups: Array });

const form = useForm({
    forum_group_id: '',
    title: '',
    body: '',
});

const submit = () => {
    form.post(route('forum.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="New Forum Post" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">New Forum Post</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Group</label>
                            <select v-model="form.forum_group_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required>
                                <option value="">Select a group</option>
                                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                            </select>
                            <div v-if="form.errors.forum_group_id" class="text-red-500 text-sm mt-1">{{ form.errors.forum_group_id }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required />
                            <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Body</label>
                            <textarea v-model="form.body" rows="8" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></textarea>
                            <div v-if="form.errors.body" class="text-red-500 text-sm mt-1">{{ form.errors.body }}</div>
                        </div>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded" :disabled="form.processing">Submit Post</button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
