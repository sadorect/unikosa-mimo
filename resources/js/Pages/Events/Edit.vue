<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ event: Object, chapters: Array, sets: Array });

const form = useForm({
    title: props.event.title,
    description: props.event.description,
    cover_image: null,
    location: props.event.location || '',
    chapter_id: props.event.chapter_id || '',
    set_id: props.event.set_id || '',
    is_virtual: props.event.is_virtual,
    livestream_url: props.event.livestream_url || '',
    start_at: props.event.start_at ? props.event.start_at.slice(0, 16) : '',
    end_at: props.event.end_at ? props.event.end_at.slice(0, 16) : '',
    is_paid: props.event.is_paid,
    ticket_price: props.event.ticket_price,
    ticket_currency: props.event.ticket_currency || 'NGN',
    capacity: props.event.capacity,
});

const coverPreview = ref(null);

const handleCoverChange = (e) => {
    const file = e.target.files[0] || null;
    form.cover_image = file;
    coverPreview.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    // PHP cannot parse multipart bodies on PUT/PATCH requests, so file uploads must be sent
    // as POST with a spoofed _method field rather than form.put() (see Inertia's file upload docs).
    form.transform((data) => ({ ...data, _method: 'put' }))
        .post(route('events.update', props.event.id), { forceFormData: true });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Edit Event" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Edit Event</h2></template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div v-if="event.status === 'changes_requested' && event.feedback"
                    class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 px-4 py-3 rounded-lg text-sm">
                    <strong>Changes requested:</strong> {{ event.feedback }}
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:bg-gray-700" required>
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea v-model="form.description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover Image <span class="text-gray-400 font-normal">(optional)</span></label>
                            <img v-if="!coverPreview && event.cover_image" :src="event.cover_image" class="mt-2 mb-3 h-40 w-full object-cover rounded-lg" />
                            <input type="file" accept="image/*" @change="handleCoverChange"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent-50 file:text-accent-700 hover:file:bg-accent-100" />
                            <img v-if="coverPreview" :src="coverPreview" class="mt-3 h-40 w-full object-cover rounded-lg" />
                            <p v-if="form.errors.cover_image" class="mt-1 text-xs text-red-500">{{ form.errors.cover_image }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                            <input v-model="form.location" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Chapter <span class="text-gray-400 font-normal">(optional)</span></label>
                                <select v-model="form.chapter_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                    <option value="">All chapters</option>
                                    <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">{{ chapter.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Set <span class="text-gray-400 font-normal">(optional)</span></label>
                                <select v-model="form.set_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                    <option value="">All sets</option>
                                    <option v-for="set in sets" :key="set.id" :value="set.id">{{ set.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start At</label>
                                <input v-model="form.start_at" type="datetime-local" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700" required>
                                <p v-if="form.errors.start_at" class="mt-1 text-xs text-red-500">{{ form.errors.start_at }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End At <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input v-model="form.end_at" type="datetime-local" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                <p v-if="form.errors.end_at" class="mt-1 text-xs text-red-500">{{ form.errors.end_at }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Capacity <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input v-model="form.capacity" type="number" min="1" class="mt-1 block w-full sm:w-1/2 border-gray-300 rounded-md shadow-sm dark:bg-gray-700" placeholder="Leave blank for unlimited">
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <label class="flex items-center gap-2">
                                <input v-model="form.is_virtual" type="checkbox" class="rounded border-gray-300 text-accent-500 focus:ring-accent-500">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">This is a virtual event</span>
                            </label>
                            <div v-if="form.is_virtual" class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Livestream URL</label>
                                <input v-model="form.livestream_url" type="url" placeholder="https://..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                <p v-if="form.errors.livestream_url" class="mt-1 text-xs text-red-500">{{ form.errors.livestream_url }}</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <label class="flex items-center gap-2">
                                <input v-model="form.is_paid" type="checkbox" class="rounded border-gray-300 text-accent-500 focus:ring-accent-500">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Requires a paid ticket</span>
                            </label>
                            <div v-if="form.is_paid" class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ticket Price</label>
                                    <input v-model="form.ticket_price" type="number" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                    <p class="mt-1 text-xs text-gray-400">In the smallest currency unit (e.g. 5000 = ₦50.00).</p>
                                    <p v-if="form.errors.ticket_price" class="mt-1 text-xs text-red-500">{{ form.errors.ticket_price }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                                    <select v-model="form.ticket_currency" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                                        <option v-for="currency in ['NGN', 'USD', 'GBP', 'EUR']" :key="currency" :value="currency">{{ currency }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-6 py-2 rounded disabled:opacity-50">
                            {{ form.processing ? 'Resubmitting…' : 'Resubmit for Review' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
