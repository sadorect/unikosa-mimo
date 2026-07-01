<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    category: '',
    website: '',
    contact_email: '',
    contact_phone: '',
});

const categories = [
    'Technology', 'Healthcare', 'Finance', 'Education', 'Legal',
    'Engineering', 'Media & Entertainment', 'Real Estate', 'Consulting',
    'Retail', 'Hospitality', 'Agriculture', 'Manufacturing', 'Other',
];

const submit = () => {
    form.post(route('business.store'), { onFinish: () => form.reset() });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="List Your Business" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">List Your Business</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Business Name *</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category *</label>
                            <select v-model="form.category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required>
                                <option value="">Select a category</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description *</label>
                            <textarea v-model="form.description" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website</label>
                                <input v-model="form.website" type="url" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Email</label>
                                <input v-model="form.contact_email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Phone</label>
                            <input v-model="form.contact_phone" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                        </div>
                        <button type="submit" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-6 py-2 rounded" :disabled="form.processing">Submit for Review</button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
