<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ election: Object, results: Array });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="`Results — ${election.title}`" />
        <template #header>
            <Breadcrumb :items="[{ label: 'Elections', href: route('elections.index') }, { label: election.title, href: route('elections.show', election.id) }, { label: 'Results' }]" />
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Results — {{ election.title }}</h1>

                <div v-for="result in results" :key="result.position.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">{{ result.position.title }}</h2>
                    <p class="text-xs text-gray-500 mb-4">{{ result.total }} ballots decided this position &middot; {{ result.abstentions }} abstained</p>

                    <div class="space-y-3">
                        <div v-for="row in result.tallies" :key="row.candidate?.id">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                    {{ row.candidate?.user?.name || 'Unknown candidate' }}
                                    <span v-if="result.winner && result.winner.candidate?.id === row.candidate?.id"
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-800">Winner</span>
                                </span>
                                <span class="text-gray-500">{{ row.votes }} votes ({{ row.percent }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-accent-500" :style="{ width: row.percent + '%' }"></div>
                            </div>
                        </div>
                        <p v-if="!result.tallies.length" class="text-sm text-gray-400">No votes were cast for this position.</p>
                    </div>

                    <p v-if="!result.winner && result.tallies.length" class="text-sm text-red-600 dark:text-red-400 font-medium mt-4">
                        No winner — threshold not met.
                    </p>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
