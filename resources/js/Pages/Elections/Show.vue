<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    election: Object,
    hasVoted: Boolean,
    canVote: Boolean,
    nominablePositions: Object,
    myCandidacies: Object,
});

const candidacyBadge = {
    pending: { label: 'Pending review', class: 'bg-yellow-100 text-yellow-800' },
    approved: { label: 'Approved — on the ballot', class: 'bg-green-100 text-green-800' },
    rejected: { label: 'Not approved', class: 'bg-red-100 text-red-800' },
    withdrawn: { label: 'Withdrawn', class: 'bg-gray-100 text-gray-600' },
};

const openNominationForm = ref(null);
const nominateForm = useForm({ manifesto: '', photo: null });

const startNomination = (positionId) => {
    nominateForm.reset();
    openNominationForm.value = openNominationForm.value === positionId ? null : positionId;
};

const submitNomination = (position) => {
    nominateForm.post(route('elections.nominate', [props.election.id, position.id]), {
        forceFormData: true,
        onSuccess: () => { nominateForm.reset(); openNominationForm.value = null; },
    });
};

const withdrawForm = useForm({});
const withdrawCandidacy = (candidateId) => {
    if (!confirm('Withdraw your nomination for this position?')) return;
    withdrawForm.delete(route('elections.candidates.withdraw', candidateId));
};

const ballotForm = useForm({ selections: reactive({}) });
const submitBallot = () => {
    if (!confirm('Submit your ballot? Votes cannot be changed once cast.')) return;
    ballotForm.post(route('elections.vote', props.election.id));
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="election.title" />
        <template #header>
            <Breadcrumb :items="[{ label: 'Elections', href: route('elections.index') }, { label: election.title }]" />
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ election.title }}</h1>
                    <div v-if="election.description" class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-400" v-html="election.description"></div>

                    <Link v-if="election.status === 'results_published'" :href="route('elections.results', election.id)"
                        class="inline-block mt-4 text-accent-600 hover:text-accent-700 font-medium text-sm">
                        View results →
                    </Link>
                </div>

                <div v-if="hasVoted" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg text-sm">
                    You have voted in this election. Thank you for participating.
                </div>

                <div v-for="position in election.positions" :key="position.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">{{ position.title }}</h2>
                    <p v-if="position.description" class="text-sm text-gray-500 mb-4">{{ position.description }}</p>

                    <div class="space-y-3 mb-4">
                        <div v-for="candidate in position.approved_candidates" :key="candidate.id" class="flex items-center gap-3">
                            <img v-if="candidate.photo" :src="candidate.photo" class="w-10 h-10 rounded-full object-cover" />
                            <div v-else class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ candidate.user?.name }}</div>
                                <div v-if="candidate.manifesto" class="text-xs text-gray-500 line-clamp-2">{{ candidate.manifesto }}</div>
                            </div>
                        </div>
                        <p v-if="!position.approved_candidates?.length" class="text-sm text-gray-400">No approved candidates yet.</p>
                    </div>

                    <!-- Ballot -->
                    <div v-if="canVote && !hasVoted" class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <label v-for="candidate in position.approved_candidates" :key="candidate.id"
                            class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:border-accent-500"
                            :class="{ 'border-accent-500 bg-accent-50 dark:bg-accent-900/20': ballotForm.selections[position.id] === candidate.id }">
                            <input type="radio" :name="`position-${position.id}`" :value="candidate.id"
                                v-model="ballotForm.selections[position.id]" class="text-accent-500" />
                            <span class="text-sm">{{ candidate.user?.name }}</span>
                        </label>
                        <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:border-accent-500"
                            :class="{ 'border-accent-500 bg-accent-50 dark:bg-accent-900/20': ballotForm.selections[position.id] === 'abstain' }">
                            <input type="radio" :name="`position-${position.id}`" value="abstain"
                                v-model="ballotForm.selections[position.id]" class="text-accent-500" />
                            <span class="text-sm text-gray-500">Abstain</span>
                        </label>
                    </div>

                    <!-- My candidacy status / apply -->
                    <div v-if="myCandidacies[position.id]" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="candidacyBadge[myCandidacies[position.id].status]?.class">
                                Your nomination: {{ candidacyBadge[myCandidacies[position.id].status]?.label }}
                            </span>
                        </div>
                        <p v-if="myCandidacies[position.id].feedback" class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                            Feedback: {{ myCandidacies[position.id].feedback }}
                        </p>
                        <button v-if="['pending', 'approved'].includes(myCandidacies[position.id].status) && election.status === 'nominations_open'"
                            @click="withdrawCandidacy(myCandidacies[position.id].id)"
                            class="text-xs text-red-500 hover:text-red-600 mt-2">Withdraw nomination</button>
                    </div>

                    <div v-if="nominablePositions[position.id]" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <button v-if="openNominationForm !== position.id" @click="startNomination(position.id)"
                            class="text-sm font-medium text-accent-600 hover:text-accent-700">
                            {{ myCandidacies[position.id] ? 'Resubmit your nomination' : 'Run for this position' }}
                        </button>
                        <form v-else @submit.prevent="submitNomination(position)" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Manifesto</label>
                                <textarea v-model="nominateForm.manifesto" rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Photo <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="file" accept="image/*" @change="nominateForm.photo = $event.target.files[0]"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent-50 file:text-accent-700 hover:file:bg-accent-100" />
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" :disabled="nominateForm.processing"
                                    class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded disabled:opacity-50">
                                    {{ nominateForm.processing ? 'Submitting…' : 'Submit nomination' }}
                                </button>
                                <button type="button" @click="openNominationForm = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <button v-if="canVote && !hasVoted" @click="submitBallot" :disabled="ballotForm.processing"
                    class="w-full bg-accent-500 hover:bg-accent-600 text-white font-semibold py-3 rounded-lg disabled:opacity-50">
                    {{ ballotForm.processing ? 'Submitting…' : 'Submit Ballot' }}
                </button>
                <p v-if="ballotForm.errors.selections" class="text-sm text-red-500 text-center">{{ ballotForm.errors.selections }}</p>
            </div>
        </div>
    </AuthLayout>
</template>
