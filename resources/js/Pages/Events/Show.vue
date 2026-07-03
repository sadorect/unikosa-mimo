<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    event: Object,
    userRsvp: Object,
    hasPaidTicket: Boolean,
    canManage: Boolean,
    attendees: { type: Array, default: null },
});

const isOwnUnapprovedEvent = computed(() =>
    props.event.status !== 'approved' && props.event.created_by === usePage().props.auth?.user?.id
);

const statusBanner = computed(() => {
    switch (props.event.status) {
        case 'pending':
            return { text: 'This event is awaiting admin review and is not visible to other members yet.', class: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300' };
        case 'changes_requested':
            return { text: `Changes requested: ${props.event.feedback}`, class: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300' };
        case 'rejected':
            return { text: `This event was not approved. Reason: ${props.event.feedback}`, class: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300' };
        default:
            return null;
    }
});

const rsvpForm = useForm({ status: 'going' });
const ticketForm = useForm({ payment_method: 'paystack' });

const rsvp = (status) => {
    rsvpForm.status = status;
    rsvpForm.post(route('events.rsvp', props.event.id));
};

const buyTicket = () => {
    ticketForm.post(route('events.ticket', props.event.id));
};

const goingCount = props.event.rsvps_going_count || 0;
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="event.title" />
        <template #header>
            <Breadcrumb :items="[{ label: 'Events', href: route('events.index') }, { label: event.title }]" />
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div v-if="isOwnUnapprovedEvent && statusBanner" class="mb-6 border px-4 py-3 rounded-lg text-sm flex items-center justify-between gap-4" :class="statusBanner.class">
                    <span>{{ statusBanner.text }}</span>
                    <Link v-if="['pending', 'changes_requested'].includes(event.status)" :href="route('events.edit', event.id)" class="font-medium underline whitespace-nowrap">Edit event</Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <div class="h-48 flex items-center justify-center relative bg-cover bg-center"
                        :class="event.cover_image ? '' : 'bg-gradient-to-br from-accent-400 to-accent-600'"
                        :style="event.cover_image ? { backgroundImage: `url(${event.cover_image})` } : {}">
                        <div v-if="event.cover_image" class="absolute inset-0 bg-black/30"></div>
                        <div class="relative text-center text-white">
                            <div class="text-5xl font-bold">{{ event.start_at ? new Date(event.start_at).getDate() : '' }}</div>
                            <div class="text-xl">{{ event.start_at ? new Date(event.start_at).toLocaleString('default', { month: 'long', year: 'numeric' }) : '' }}</div>
                        </div>
                    </div>
                    <div class="p-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">{{ event.title }}</h1>

                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400 mb-6">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">Date & Time</div>
                                <div>{{ event.start_at ? new Date(event.start_at).toLocaleString() : 'TBA' }}</div>
                                <div v-if="event.end_at">to {{ new Date(event.end_at).toLocaleString() }}</div>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">Location</div>
                                <div>{{ event.location || 'TBA' }}</div>
                                <div v-if="event.is_virtual" class="text-green-600">Virtual Event</div>
                            </div>
                        </div>

                        <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 mb-8" v-html="event.description"></div>

                        <div v-if="event.livestream_url" class="mb-6">
                            <a :href="event.livestream_url" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-4 py-2 rounded inline-block">
                                Join Livestream
                            </a>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-sm text-gray-500">
                                    {{ goingCount }} attending &middot; Organized by {{ event.creator?.name }}
                                </div>
                                <div v-if="event.capacity" class="text-sm text-gray-500">
                                    {{ event.capacity - goingCount }} spots left
                                </div>
                            </div>

                            <div class="flex gap-3 flex-wrap">
                                <button v-for="status in ['going', 'maybe', 'not_going']" :key="status"
                                    @click="rsvp(status)"
                                    class="px-4 py-2 rounded-full text-sm font-medium transition border"
                                    :class="userRsvp?.status === status
                                        ? 'bg-accent-500 text-white border-accent-500'
                                        : 'border-gray-300 text-gray-600 hover:border-accent-500'">
                                    {{ status === 'going' ? 'Going' : status === 'maybe' ? 'Maybe' : 'Can\'t Go' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="event.is_paid && event.ticket_price" class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                            <div v-if="hasPaidTicket" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg text-sm">
                                You have a ticket for this event. See you there!
                            </div>
                            <template v-else>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Purchase Ticket</h3>
                                <div class="text-2xl font-bold text-accent-500 mb-4">{{ event.ticket_currency || 'NGN' }} {{ (event.ticket_price / 100).toFixed(2) }}</div>
                                <div class="flex gap-3">
                                    <label v-for="method in ['paystack', 'stripe']" :key="method"
                                        class="flex items-center gap-2 border rounded-lg px-4 py-2 cursor-pointer hover:border-accent-500"
                                        :class="{ 'border-accent-500 bg-accent-50 dark:bg-accent-900/20': ticketForm.payment_method === method }">
                                        <input type="radio" v-model="ticketForm.payment_method" :value="method" class="text-accent-500" />
                                        <span class="text-sm font-medium capitalize">{{ method }}</span>
                                    </label>
                                </div>
                                <button @click="buyTicket" :disabled="ticketForm.processing"
                                    class="mt-3 bg-accent-500 hover:bg-accent-600 text-white font-medium px-6 py-2 rounded disabled:opacity-50">
                                    {{ ticketForm.processing ? 'Processing...' : 'Buy Ticket' }}
                                </button>
                            </template>
                        </div>

                        <div v-if="canManage && attendees" class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Attendees ({{ attendees.length }})</h3>
                            <ul v-if="attendees.length" class="divide-y divide-gray-100 dark:divide-gray-700">
                                <li v-for="(a, i) in attendees" :key="i" class="flex items-center justify-between py-2 text-sm">
                                    <span class="text-gray-800 dark:text-gray-200">{{ a.name }}</span>
                                    <span v-if="a.paid" class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-800">Paid</span>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-400">No confirmed attendees yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
