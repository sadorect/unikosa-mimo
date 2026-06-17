<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ events: Object, filters: Object });

const type = ref(filters?.type || '');

const filter = (val) => {
    type.value = val;
    router.get(route('events.index'), { type: val }, { preserveState: true, replace: true });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Events" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Events</h2>
                <Link :href="route('events.create')" class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded">Create Event</Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex gap-4 mb-6">
                    <button @click="filter('')" class="px-4 py-2 rounded-full text-sm font-medium transition"
                        :class="type === '' ? 'bg-accent-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 hover:bg-gray-100'">Upcoming</button>
                    <button @click="filter('past')" class="px-4 py-2 rounded-full text-sm font-medium transition"
                        :class="type === 'past' ? 'bg-accent-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 hover:bg-gray-100'">Past Events</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="event in events.data" :key="event.id" :href="route('events.show', event)"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition block">
                        <div class="h-40 bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center">
                            <div class="text-center text-white">
                                <div class="text-3xl font-bold">{{ event.start_at ? new Date(event.start_at).getDate() : '' }}</div>
                                <div class="text-sm">{{ event.start_at ? new Date(event.start_at).toLocaleString('default', { month: 'short' }) : '' }}</div>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">{{ event.title }}</h3>
                            <div class="text-sm text-gray-500 mb-2">
                                {{ event.start_at ? new Date(event.start_at).toLocaleString('default', { weekday: 'long', hour: '2-digit', minute: '2-digit' }) : 'TBA' }}
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-400">
                                <span v-if="event.location">{{ event.location }}</span>
                                <span v-if="event.is_virtual" class="text-green-600">Virtual</span>
                                <span v-if="event.is_paid" class="bg-accent-100 text-accent-800 px-2 py-0.5 rounded-full">Paid</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-if="!events.data.length" class="text-center py-12 text-gray-500">No events found.</div>
            </div>
        </div>
    </AuthLayout>
</template>
