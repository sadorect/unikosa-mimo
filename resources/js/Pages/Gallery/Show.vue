<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ album: Object });

const showUpload = ref(false);
const uploadForm = useForm({
    files: [],
    captions: [],
});

const selectedLightbox = ref(null);

const handleFileChange = (e) => {
    uploadForm.files = Array.from(e.target.files);
    uploadForm.captions = uploadForm.files.map(() => '');
};

const submitUpload = () => {
    uploadForm.post(route('gallery.upload', { album: album.id }), {
        onSuccess: () => {
            uploadForm.reset();
            showUpload.value = false;
        },
    });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="album.title" />
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Link :href="route('gallery.index')" class="text-accent-600 hover:text-accent-700 text-sm">Gallery</Link>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-700 dark:text-gray-300 text-sm">{{ album.title }}</span>
                </div>
                <button @click="showUpload = !showUpload" class="bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded">
                    Upload Media
                </button>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ album.title }}</h1>
                    <p v-if="album.description" class="text-gray-600 dark:text-gray-400 mt-2">{{ album.description }}</p>
                    <div v-if="album.event" class="mt-2 text-sm text-accent-600">Event: {{ album.event.title }}</div>
                </div>

                <div v-if="showUpload" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Upload Media</h3>
                    <form @submit.prevent="submitUpload">
                        <input type="file" multiple accept="image/*,video/*" @change="handleFileChange"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent-50 file:text-accent-700 hover:file:bg-accent-100" />
                        <div v-if="uploadForm.files.length" class="mt-4 space-y-2">
                            <div v-for="(file, i) in uploadForm.files" :key="i" class="flex items-center gap-3">
                                <span class="text-sm text-gray-600">{{ file.name }}</span>
                                <input v-model="uploadForm.captions[i]" type="text" placeholder="Caption (optional)"
                                    class="flex-1 text-sm border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                        </div>
                        <button type="submit" class="mt-4 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded"
                            :disabled="uploadForm.processing || !uploadForm.files.length">Upload</button>
                    </form>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="media in album.media" :key="media.id" @click="selectedLightbox = media"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition">
                        <div class="aspect-square bg-gray-200 dark:bg-gray-700">
                            <img v-if="media.type === 'image'" :src="media.path" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        <div v-if="media.caption" class="p-2 text-xs text-gray-600 dark:text-gray-400">{{ media.caption }}</div>
                    </div>
                </div>

                <div v-if="!album.media?.length" class="text-center py-12 text-gray-500">
                    No media in this album yet. Click "Upload Media" to add photos or videos.
                </div>
            </div>
        </div>

        <div v-if="selectedLightbox" @click="selectedLightbox = null"
            class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4 cursor-pointer">
            <img v-if="selectedLightbox.type === 'image'" :src="selectedLightbox.path" class="max-w-full max-h-full object-contain" />
            <video v-else :src="selectedLightbox.path" controls class="max-w-full max-h-full" />
        </div>
    </AuthLayout>
</template>
