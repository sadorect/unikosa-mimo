<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ categories: Array });

const form = useForm({
    title: '',
    excerpt: '',
    body: '',
    featured_image: '',
    categories: [],
});

const submit = () => {
    form.post(route('blog.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Write Blog Post" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Write Blog Post</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Excerpt</label>
                            <textarea v-model="form.excerpt" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categories</label>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <label v-for="cat in categories" :key="cat.id" class="inline-flex items-center gap-1 text-sm">
                                    <input type="checkbox" :value="cat.id" v-model="form.categories" class="rounded border-gray-300 text-amber-500" />
                                    {{ cat.name }}
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Body</label>
                            <textarea v-model="form.body" rows="15" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></textarea>
                        </div>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded" :disabled="form.processing">Submit for Review</button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
