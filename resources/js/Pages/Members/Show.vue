<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/date';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({ member: Object, relatedMembers: { type: Array, default: () => [] } });

const gradients = [
    'from-amber-400 to-orange-500', 'from-sky-400 to-indigo-500', 'from-emerald-400 to-teal-500',
    'from-rose-400 to-pink-500', 'from-violet-400 to-purple-500', 'from-cyan-400 to-blue-500',
];
const gradientFor = (id) => gradients[id % gradients.length];

// Auto-scroll the "similar members" strip. Duplicate the list so the loop
// wraps seamlessly, but only bother when there are enough cards to actually scroll.
const canAutoScroll = props.relatedMembers.length >= 4;
const marqueeItems = computed(() => (canAutoScroll ? [...props.relatedMembers, ...props.relatedMembers] : props.relatedMembers));

const track = ref(null);
const paused = ref(false);
let rafId = null;
let resumeTimer = null;
const SPEED = 0.4; // px per frame, ≈24px/s

const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

function tick() {
    const el = track.value;
    if (el && !paused.value) {
        const half = el.scrollWidth / 2;
        el.scrollLeft += SPEED;
        if (el.scrollLeft >= half) {
            el.scrollLeft -= half;
        }
    }
    rafId = requestAnimationFrame(tick);
}

const pause = () => {
    paused.value = true;
    clearTimeout(resumeTimer);
};

const scheduleResume = () => {
    clearTimeout(resumeTimer);
    resumeTimer = setTimeout(() => { paused.value = false; }, 1500);
};

onMounted(() => {
    if (canAutoScroll && !prefersReducedMotion) {
        rafId = requestAnimationFrame(tick);
    }
});

onUnmounted(() => {
    cancelAnimationFrame(rafId);
    clearTimeout(resumeTimer);
});
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="member.name" />
        <template #header>
            <Breadcrumb :items="[{ label: 'Directory', href: route('directory') }, { label: member.name }]" />
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-4">
                            <img v-if="member.avatar" :src="member.avatar" class="w-16 h-16 rounded-full object-cover" />
                            <div v-else class="w-16 h-16 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold text-2xl">
                                {{ member.name.charAt(0) }}
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ member.name }}</h1>
                                <p v-if="member.profession" class="text-gray-500">{{ member.profession }}</p>
                            </div>
                        </div>
                        <Link v-if="$page.props.auth.user.id !== member.id" :href="route('messages.show', member.id)"
                            class="inline-flex items-center gap-1.5 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            Send Message
                        </Link>
                    </div>

                    <p v-if="member.bio" class="mt-6 text-gray-700 dark:text-gray-300">{{ member.bio }}</p>

                    <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div v-if="member.graduating_set">
                            <dt class="font-medium text-gray-500">Set</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ member.graduating_set.name }}</dd>
                        </div>
                        <div v-if="member.chapter">
                            <dt class="font-medium text-gray-500">Chapter</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ member.chapter.name }}</dd>
                        </div>
                        <div v-if="member.city || member.country">
                            <dt class="font-medium text-gray-500">Location</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ member.city ? member.city + ', ' : '' }}{{ member.country }}</dd>
                        </div>
                        <div v-if="member.email">
                            <dt class="font-medium text-gray-500">Email</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ member.email }}</dd>
                        </div>
                        <div v-if="member.phone">
                            <dt class="font-medium text-gray-500">Phone</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ member.phone }}</dd>
                        </div>
                        <div v-if="member.date_of_birth">
                            <dt class="font-medium text-gray-500">Date of Birth</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ formatDate(member.date_of_birth) }}</dd>
                        </div>
                    </dl>

                    <div v-if="member.skills?.length" class="mt-6">
                        <dt class="font-medium text-gray-500 text-sm mb-2">Skills</dt>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="skill in member.skills" :key="skill"
                                class="bg-accent-50 dark:bg-accent-900/20 text-accent-700 dark:text-accent-400 text-xs px-2.5 py-1 rounded-full">
                                {{ skill }}
                            </span>
                        </div>
                    </div>

                    <div v-if="member.social_links && Object.keys(member.social_links).length" class="mt-6">
                        <dt class="font-medium text-gray-500 text-sm mb-2">Social Links</dt>
                        <div class="flex flex-wrap gap-3">
                            <a v-for="(url, platform) in member.social_links" :key="platform" :href="url" target="_blank" rel="noopener noreferrer"
                                class="text-accent-600 hover:text-accent-700 text-sm capitalize">{{ platform }}</a>
                        </div>
                    </div>
                </div>

                <div v-if="relatedMembers.length" class="mt-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Similar Members
                    </h2>
                    <div ref="track" class="flex gap-4 overflow-x-auto pb-3"
                        @mouseenter="pause" @mouseleave="scheduleResume"
                        @touchstart.passive="pause" @touchend.passive="scheduleResume"
                        @focusin="pause" @focusout="scheduleResume"
                        @wheel.passive="pause" @pointerdown="pause" @pointerup="scheduleResume">
                        <Link v-for="(rel, index) in marqueeItems" :key="`${rel.id}-${index}`" :href="route('members.show', rel.id)"
                            class="group shrink-0 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg hover:border-accent-300 dark:hover:border-accent-700 hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <div class="relative shrink-0">
                                    <img v-if="rel.avatar" :src="rel.avatar" :alt="rel.name"
                                        class="w-12 h-12 rounded-full object-cover ring-2 ring-white dark:ring-gray-800 shadow-sm" />
                                    <div v-else :class="['w-12 h-12 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold ring-2 ring-white dark:ring-gray-800 shadow-sm', gradientFor(rel.id)]">
                                        {{ rel.name.charAt(0) }}
                                    </div>
                                    <span v-if="rel.account_claimed" title="Verified member"
                                        class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-accent-500 rounded-full flex items-center justify-center ring-2 ring-white dark:ring-gray-800">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-sm text-gray-900 dark:text-gray-100 truncate">{{ rel.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ rel.profession || 'Alumni' }}</div>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1">
                                <span v-if="rel.graduating_set" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-accent-50 text-accent-700 dark:bg-accent-900/20 dark:text-accent-300 truncate max-w-full">
                                    {{ rel.graduating_set.name }}
                                </span>
                                <span v-if="rel.chapter" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 truncate max-w-full">
                                    {{ rel.chapter.name }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
