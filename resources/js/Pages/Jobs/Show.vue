<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ job: Object, hasApplied: Boolean });

const showApply = ref(false);
const applyForm = useForm({
    cover_letter: '',
    cv: null,
});

const submitApplication = () => {
    applyForm.post(route('jobs.apply', props.job.id), {
        onSuccess: () => {
            showApply.value = false;
            applyForm.reset();
        },
    });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="job.title" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('jobs.index')" class="text-amber-600 hover:text-amber-700 text-sm">Jobs</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm truncate">{{ job.title }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ job.title }}</h1>
                            <div class="text-gray-500 mt-1">{{ job.company }} &middot; {{ job.location || 'Remote' }}</div>
                        </div>
                        <span class="px-3 py-1 text-sm font-medium rounded-full"
                            :class="job.type === 'hire_alumnus' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                            {{ job.type?.replace('_', ' ') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
                        <span v-if="job.salary_min || job.salary_max">
                            {{ job.salary_currency || 'NGN' }} {{ job.salary_min?.toLocaleString() }}{{ job.salary_max ? ' - ' + job.salary_max.toLocaleString() : '' }}
                        </span>
                        <span v-if="job.is_remote" class="text-green-600">Remote OK</span>
                        <span>{{ job.views_count || 0 }} views</span>
                    </div>

                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-8">{{ job.description }}</div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="text-sm text-gray-500 mb-4">
                            Posted by <span class="font-medium text-gray-700 dark:text-gray-300">{{ job.author?.name }}</span>
                        </div>

                        <div v-if="job.contact_email" class="text-sm text-gray-500 mb-4">
                            Contact: <a :href="'mailto:' + job.contact_email" class="text-amber-600 hover:text-amber-700">{{ job.contact_email }}</a>
                        </div>

                        <div v-if="job.application_url" class="mb-4">
                            <a :href="job.application_url" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-4 py-2 rounded inline-block">
                                Apply Externally
                            </a>
                        </div>

                        <div v-if="!hasApplied && !job.application_url" class="mb-4">
                            <button @click="showApply = !showApply" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2 rounded">
                                Apply Now
                            </button>
                        </div>

                        <div v-if="hasApplied" class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 px-4 py-3 rounded text-sm">
                            You have already applied to this position.
                        </div>
                    </div>
                </div>

                <div v-if="showApply" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Apply for this position</h3>
                    <form @submit.prevent="submitApplication" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover Letter (optional)</label>
                            <textarea v-model="applyForm.cover_letter" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CV/Resume (PDF, DOC, DOCX)</label>
                            <input type="file" @change="e => applyForm.cv = e.target.files[0]" accept=".pdf,.doc,.docx"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700" />
                        </div>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded" :disabled="applyForm.processing">
                            Submit Application
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
