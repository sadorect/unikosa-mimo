<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({ album: Object });

const showUpload = ref(false);
const uploadForm = useForm({
    files: [],
    captions: [],
});

const lightboxIndex = ref(null);
const isLightboxOpen = computed(() => lightboxIndex.value !== null);
const currentMedia = computed(() =>
    isLightboxOpen.value ? props.album.media[lightboxIndex.value] : null
);

const openLightbox = (index) => {
    lightboxIndex.value = index;
};

const closeLightbox = () => {
    lightboxIndex.value = null;
};

const showNext = () => {
    const total = props.album.media.length;
    lightboxIndex.value = (lightboxIndex.value + 1) % total;
};

const showPrev = () => {
    const total = props.album.media.length;
    lightboxIndex.value = (lightboxIndex.value - 1 + total) % total;
};

const handleKeydown = (e) => {
    if (!isLightboxOpen.value) return;
    if (e.key === 'ArrowRight') showNext();
    else if (e.key === 'ArrowLeft') showPrev();
    else if (e.key === 'Escape') closeLightbox();
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

const handleFileChange = (e) => {
    uploadForm.files = Array.from(e.target.files);
    uploadForm.captions = uploadForm.files.map(() => '');
};

const submitUpload = () => {
    uploadForm.post(route('gallery.upload', { album: props.album.id }), {
        forceFormData: true,
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
                    <div class="mt-2 text-sm text-gray-500">{{ album.media?.length || 0 }} item{{ album.media?.length === 1 ? '' : 's' }}</div>
                </div>

                <div v-if="showUpload" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Upload Media</h3>
                    <form @submit.prevent="submitUpload">
                        <input type="file" multiple accept="image/*,video/*" @change="handleFileChange"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent-50 file:text-accent-700 hover:file:bg-accent-100" />
                        <p class="mt-1 text-xs text-gray-400">Images up to 20MB, videos up to 200MB.</p>

                        <div v-if="uploadForm.files.length" class="mt-4 space-y-2">
                            <div v-for="(file, i) in uploadForm.files" :key="i" class="flex items-center gap-3">
                                <span class="text-sm text-gray-600 truncate max-w-[10rem]">{{ file.name }}</span>
                                <input v-model="uploadForm.captions[i]" type="text" placeholder="Caption (optional)"
                                    class="flex-1 text-sm border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                            </div>
                        </div>

                        <div v-if="Object.keys(uploadForm.errors).length" class="mt-4 space-y-1">
                            <p v-for="(error, key) in uploadForm.errors" :key="key" class="text-xs text-red-500">{{ error }}</p>
                        </div>

                        <div v-if="uploadForm.progress" class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-accent-500 h-2 rounded-full transition-all" :style="{ width: uploadForm.progress.percentage + '%' }"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Uploading… {{ uploadForm.progress.percentage }}%</p>
                        </div>

                        <button type="submit" class="mt-4 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded disabled:opacity-50"
                            :disabled="uploadForm.processing || !uploadForm.files.length">
                            {{ uploadForm.processing ? 'Uploading…' : 'Upload' }}
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="(media, index) in album.media" :key="media.id" @click="openLightbox(index)"
                        class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition">
                        <div class="aspect-square bg-gray-200 dark:bg-gray-700">
                            <img v-if="media.type === 'image'" :src="media.path" class="w-full h-full object-cover" loading="lazy" />
                            <div v-else class="relative w-full h-full">
                                <video :src="media.path" class="w-full h-full object-cover" preload="metadata" muted></video>
                                <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/30 transition">
                                    <svg class="w-10 h-10 text-white drop-shadow" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                </div>
                            </div>
                        </div>
                        <div v-if="media.caption" class="p-2 text-xs text-gray-600 dark:text-gray-400 truncate">{{ media.caption }}</div>
                    </div>
                </div>

                <div v-if="!album.media?.length" class="text-center py-12 text-gray-500">
                    No media in this album yet. Click "Upload Media" to add photos or videos.
                </div>
            </div>
        </div>

        <div v-if="isLightboxOpen" @click="closeLightbox"
            class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4">
            <button @click.stop="closeLightbox" class="absolute top-4 right-4 text-white/80 hover:text-white text-3xl leading-none">&times;</button>

            <div class="absolute top-4 left-4 text-white/80 text-sm">{{ lightboxIndex + 1 }} / {{ album.media.length }}</div>

            <button v-if="album.media.length > 1" @click.stop="showPrev"
                class="absolute left-2 md:left-6 top-1/2 -translate-y-1/2 text-white/70 hover:text-white bg-black/30 hover:bg-black/50 rounded-full w-10 h-10 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>

            <div class="max-w-full max-h-full flex flex-col items-center" @click.stop>
                <img v-if="currentMedia.type === 'image'" :src="currentMedia.path" class="max-w-full max-h-[80vh] object-contain" />
                <video v-else :src="currentMedia.path" controls autoplay class="max-w-full max-h-[80vh]" />
                <p v-if="currentMedia.caption" class="text-white/80 text-sm mt-3 text-center max-w-2xl">{{ currentMedia.caption }}</p>
            </div>

            <button v-if="album.media.length > 1" @click.stop="showNext"
                class="absolute right-2 md:right-6 top-1/2 -translate-y-1/2 text-white/70 hover:text-white bg-black/30 hover:bg-black/50 rounded-full w-10 h-10 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>

            <div v-if="album.media.length > 1" @click.stop
                class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5 max-w-[90vw] overflow-x-auto px-2 py-1">
                <button v-for="(media, index) in album.media" :key="media.id" @click="lightboxIndex = index"
                    class="w-12 h-12 shrink-0 rounded overflow-hidden border-2 transition"
                    :class="index === lightboxIndex ? 'border-accent-400' : 'border-transparent opacity-60 hover:opacity-100'">
                    <img v-if="media.type === 'image'" :src="media.path" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full bg-gray-700 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                    </div>
                </button>
            </div>
        </div>
    </AuthLayout>
</template>
