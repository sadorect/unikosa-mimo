<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ elections: Object });

const statusBadge = {
    nominations_open: { label: 'Nominations open', class: 'bg-blue-100 text-blue-800' },
    voting_open: { label: 'Voting open', class: 'bg-green-100 text-green-800' },
    closed: { label: 'Voting closed', class: 'bg-yellow-100 text-yellow-800' },
    results_published: { label: 'Results published', class: 'bg-green-100 text-green-800' },
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Elections" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Elections</h2>
        </template>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <Link v-for="election in elections.data" :key="election.id" :href="route('elections.show', election)"
                    class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">{{ election.title }}</h3>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap" :class="statusBadge[election.status]?.class">
                                    {{ statusBadge[election.status]?.label || election.status }}
                                </span>
                            </div>
                            <div v-if="election.status === 'nominations_open'" class="text-sm text-gray-500">
                                Nominations close {{ election.nominations_end_at ? new Date(election.nominations_end_at).toLocaleString() : 'TBA' }}
                            </div>
                            <div v-else-if="election.status === 'voting_open'" class="text-sm text-gray-500">
                                Voting closes {{ election.voting_end_at ? new Date(election.voting_end_at).toLocaleString() : 'TBA' }}
                            </div>
                        </div>
                    </div>
                </Link>

                <div v-if="!elections.data.length" class="text-center py-12 text-gray-500">
                    No elections are open at the moment.
                </div>

                <Pagination :paginator="elections" />
            </div>
        </div>
    </AuthLayout>
</template>
