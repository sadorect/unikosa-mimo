<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

// Mounted once in app.js rather than per layout, so it shows on every page
// the impersonated member can reach, including ones with no layout at all.
const page = usePage();
const impersonation = computed(() => page.props.impersonation);
const user = computed(() => page.props.auth?.user);

const stop = () => router.post(route('impersonate.stop'));
</script>

<template>
    <div v-if="impersonation"
        class="fixed inset-x-0 bottom-0 z-50 bg-amber-500 text-amber-950 shadow-lg print:hidden"
        role="status">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="font-medium">
                You are signed in as <span class="font-semibold">{{ user?.name }}</span>
                <span class="font-normal opacity-80">({{ impersonation.impersonator }})</span>
            </p>
            <button type="button" @click="stop"
                class="inline-flex items-center rounded-md bg-amber-950 px-3 py-1 text-xs font-semibold text-amber-50 hover:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-950 focus:ring-offset-2 focus:ring-offset-amber-500">
                Stop
            </button>
        </div>
    </div>
</template>
