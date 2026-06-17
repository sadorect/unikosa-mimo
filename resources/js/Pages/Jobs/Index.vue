<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ jobs: Object, filters: Object });

const search = ref(filters?.search || '');
const type = ref(filters?.type || '');

const types = [
    { value: '', label: 'All Types' },
    { value: 'full_time', label: 'Full Time' },
    { value: 'part_time', label: 'Part Time' },
    { value: 'contract', label: 'Contract' },
    { value: 'internship', label: 'Internship' },
    { value: 'remote', label: 'Remote' },
    { value: 'hire_alumnus', label: 'Hire an Alumnus' },
];

const filter = () => {
    router.get(route('jobs.index'), { search: search.value, type: type.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Job Board" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Job Board</h2>
                <Link :href="route('jobs.create')" class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded">Post a Job</Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6 flex flex-wrap gap-4 items-center">
                    <input v-model="search" @keyup.enter="filter" type="text" placeholder="Search jobs..."
                        class="flex-1 min-w-[200px] border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                    <select v-model="type" @change="filter" class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>

                <div class="space-y-4">
                    <Link v-for="job in jobs.data" :key="job.id" :href="route('jobs.show', job.id)"
                        class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ job.title }}</h3>
                                <div class="text-sm text-gray-500 mt-1">
                                    {{ job.company }} &middot; {{ job.location || 'Remote' }}
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="job.type === 'hire_alumnus' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'">
                                {{ job.type?.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mt-3 line-clamp-2">{{ job.description }}</p>
                        <div class="mt-3 flex items-center gap-4 text-xs text-gray-400">
                            <span>Posted by {{ job.author?.name }}</span>
                            <span v-if="job.salary_min || job.salary_max">
                                {{ job.salary_currency || 'NGN' }} {{ job.salary_min ? (job.salary_min).toLocaleString() : '' }}{{ job.salary_max ? ' - ' + (job.salary_max).toLocaleString() : '' }}
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-if="!jobs.data.length" class="text-center py-12 text-gray-500">No jobs found.</div>
            </div>
        </div>
    </AuthLayout>
</template>
