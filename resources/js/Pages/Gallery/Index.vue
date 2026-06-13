<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ albums: Object });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Photo Gallery" />
        <template #header><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Photo Gallery</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="album in albums.data" :key="album.id" :href="route('gallery.show', album)"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition block">
                        <div class="h-48 bg-gray-200 dark:bg-gray-700 relative">
                            <img v-if="album.media?.[0]?.path" :src="album.media[0].path" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded">
                                {{ album.media_count || 0 }} items
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ album.title }}</h3>
                            <div v-if="album.event" class="text-xs text-amber-600 mt-1">{{ album.event.title }}</div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
