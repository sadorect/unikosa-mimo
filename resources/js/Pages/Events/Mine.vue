<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ events: Object });

const statusBadge = {
    pending: { label: 'Pending review', class: 'bg-yellow-100 text-yellow-800' },
    approved: { label: 'Approved', class: 'bg-green-100 text-green-800' },
    rejected: { label: 'Denied', class: 'bg-red-100 text-red-800' },
    changes_requested: { label: 'Changes requested', class: 'bg-blue-100 text-blue-800' },
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="My Events" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">My Events</h2>
                <Link :href="route('events.create')" class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded">Create Event</Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <div v-if="$page.props.flash?.success" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <div v-for="event in events.data" :key="event.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ event.title }}</h3>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap" :class="statusBadge[event.status]?.class">
                                    {{ statusBadge[event.status]?.label || event.status }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ event.start_at ? new Date(event.start_at).toLocaleString() : 'TBA' }}
                            </div>
                            <p v-if="event.feedback && ['rejected', 'changes_requested'].includes(event.status)"
                                class="text-sm mt-2" :class="event.status === 'rejected' ? 'text-red-600' : 'text-blue-600'">
                                {{ event.status === 'rejected' ? 'Reason: ' : 'Requested changes: ' }}{{ event.feedback }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <Link v-if="['pending', 'changes_requested'].includes(event.status)" :href="route('events.edit', event.id)"
                                class="text-sm font-medium text-accent-600 hover:text-accent-700">Edit</Link>
                            <Link v-if="event.status === 'approved'" :href="route('events.show', event.id)"
                                class="text-sm font-medium text-accent-600 hover:text-accent-700">View</Link>
                        </div>
                    </div>
                </div>

                <div v-if="!events.data.length" class="text-center py-12 text-gray-500">
                    You haven't submitted any events yet.
                </div>

                <Pagination :paginator="events" />
            </div>
        </div>
    </AuthLayout>
</template>
