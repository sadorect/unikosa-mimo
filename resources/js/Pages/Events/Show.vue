<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ event: Object, userRsvp: Object });

const rsvpForm = useForm({ status: 'going' });
const ticketForm = useForm({ payment_method: 'paystack' });

const rsvp = (status) => {
    rsvpForm.status = status;
    rsvpForm.post(route('events.rsvp', props.event.id));
};

const buyTicket = () => {
    ticketForm.post(route('events.ticket', props.event.id));
};

const goingCount = props.event.rsvps?.filter(r => r.pivot?.status === 'going').length || 0;
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="event.title" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('events.index')" class="text-accent-600 hover:text-accent-700 text-sm">Events</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">{{ event.title }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <div class="h-48 bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center">
                        <div class="text-center text-white">
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
                                    :class="userRsvp?.pivot?.status === status
                                        ? 'bg-accent-500 text-white border-accent-500'
                                        : 'border-gray-300 text-gray-600 hover:border-accent-500'">
                                    {{ status === 'going' ? 'Going' : status === 'maybe' ? 'Maybe' : 'Can\'t Go' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="event.is_paid && event.ticket_price" class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
