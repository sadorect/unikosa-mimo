<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    company: '',
    description: '',
    type: 'full_time',
    location: '',
    is_remote: false,
    salary_min: '',
    salary_max: '',
    salary_currency: 'NGN',
    application_url: '',
    contact_email: '',
});

const types = [
    { value: 'full_time', label: 'Full Time' },
    { value: 'part_time', label: 'Part Time' },
    { value: 'contract', label: 'Contract' },
    { value: 'internship', label: 'Internship' },
    { value: 'remote', label: 'Remote' },
    { value: 'hire_alumnus', label: 'Hire an Alumnus' },
];

const submit = () => {
    form.post(route('jobs.store'), { onFinish: () => form.reset() });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Post a Job" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Post a Job</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Title *</label>
                                <input v-model="form.title" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
                                <input v-model="form.company" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type *</label>
                                <select v-model="form.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required>
                                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description *</label>
                            <textarea v-model="form.description" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <input v-model="form.location" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                            <div class="flex items-end pb-1">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" v-model="form.is_remote" class="rounded border-gray-300 text-amber-500" />
                                    Remote OK
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Salary</label>
                                <input v-model="form.salary_min" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Salary</label>
                                <input v-model="form.salary_max" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                                <select v-model="form.salary_currency" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                    <option>NGN</option><option>USD</option><option>GBP</option><option>EUR</option><option>CAD</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Application URL</label>
                                <input v-model="form.application_url" type="url" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Email</label>
                                <input v-model="form.contact_email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                        </div>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded" :disabled="form.processing">Submit Job Listing</button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
