<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/date';

defineProps({ job: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Applications - {{ job.title }}" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('jobs.show', job.id)" class="text-accent-600 hover:text-accent-700 text-sm">{{ job.title }}</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">Applications</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referral</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CV</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="app in job.applications" :key="app.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ app.applicant?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ app.applicant?.email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(app.created_at) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ app.referrer?.name || 'Direct' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': app.status === 'pending',
                                            'bg-blue-100 text-blue-800': app.status === 'shortlisted',
                                            'bg-green-100 text-green-800': app.status === 'accepted',
                                            'bg-red-100 text-red-800': app.status === 'rejected',
                                        }">{{ app.status }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <a v-if="app.cv_path" :href="app.cv_path" target="_blank" class="text-accent-600 hover:text-accent-700 text-sm">Download</a>
                                    <span v-else class="text-gray-400 text-sm">None</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="!job.applications?.length" class="p-8 text-center text-gray-500">No applications yet.</div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
