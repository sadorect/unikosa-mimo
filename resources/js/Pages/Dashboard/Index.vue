<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    upcomingEvents: Array,
    recentPosts: Array,
    dueSummary: Object,
});

const roleLabels = {
    super_admin: 'Super Admin',
    set_representative: 'Set Representative',
    chapter_head: 'Chapter Head',
    content_moderator: 'Content Moderator',
    finance_admin: 'Finance Admin',
    member: 'Member',
};

const quickLinks = [
    { label: 'Member Directory', route: 'directory', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', color: 'amber' },
    { label: 'Events', route: 'events.index', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'blue' },
    { label: 'Forum', route: 'forum.index', icon: 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z', color: 'green' },
    { label: 'Job Board', route: 'jobs.index', icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', color: 'purple' },
    { label: 'Gallery', route: 'gallery.index', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'rose' },
    { label: 'Payments', route: 'payments.history', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', color: 'teal' },
];

const colorMap = {
    amber:  { bg: 'bg-accent-50 dark:bg-accent-900/20',  icon: 'text-accent-500' },
    blue:   { bg: 'bg-blue-50 dark:bg-blue-900/20',    icon: 'text-blue-500' },
    green:  { bg: 'bg-green-50 dark:bg-green-900/20',  icon: 'text-green-500' },
    purple: { bg: 'bg-purple-50 dark:bg-purple-900/20',icon: 'text-purple-500' },
    rose:   { bg: 'bg-rose-50 dark:bg-rose-900/20',    icon: 'text-rose-500' },
    teal:   { bg: 'bg-teal-50 dark:bg-teal-900/20',    icon: 'text-teal-500' },
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Dashboard" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Welcome banner -->
                <div class="bg-linear-to-r from-accent-500 to-accent-400 rounded-2xl p-6 text-white shadow-md">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-bold mb-1">
                                Welcome back, {{ $page.props.auth.user.name.split(' ')[0] }}!
                            </h3>
                            <p class="text-accent-100 text-sm">
                                You are logged in as
                                <span v-for="role in $page.props.auth.user.roles" :key="role"
                                    class="ml-1 bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">
                                    {{ roleLabels[role] || role }}
                                </span>
                                <span v-if="!$page.props.auth.user.roles?.length" class="ml-1 bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">Member</span>
                            </p>
                        </div>
                        <Link :href="route('profile.edit')"
                            class="text-sm bg-white/20 hover:bg-white/30 text-white font-medium px-4 py-2 rounded-full transition">
                            Edit Profile
                        </Link>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-accent-600">{{ stats?.total_members || 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">Total Members</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-blue-600">{{ stats?.total_sets || 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">Graduating Sets</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-green-600">{{ stats?.total_events || 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">Total Events</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm">
                        <div class="text-3xl font-bold text-purple-600">{{ stats?.pending_members || 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">Pending Approval</div>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 text-sm uppercase tracking-wide text-gray-500">Quick Access</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <Link v-for="ql in quickLinks" :key="ql.label" :href="route(ql.route)"
                            class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-accent-300 hover:shadow-sm transition group">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"
                                :class="colorMap[ql.color].bg">
                                <svg class="w-5 h-5" :class="colorMap[ql.color].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="ql.icon" />
                                </svg>
                            </div>
                            <span class="text-xs text-center font-medium text-gray-700 dark:text-gray-300">{{ ql.label }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Two-column: Upcoming Events + Recent Forum Posts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Upcoming events -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Upcoming Events</h3>
                            <Link :href="route('events.index')" class="text-accent-500 hover:text-accent-600 text-sm font-medium">View all →</Link>
                        </div>
                        <div v-if="upcomingEvents?.length" class="space-y-3">
                            <Link v-for="ev in upcomingEvents" :key="ev.id" :href="route('events.show', ev.id)"
                                class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                                <div class="shrink-0 w-12 h-12 rounded-lg bg-accent-100 dark:bg-accent-900/30 flex flex-col items-center justify-center">
                                    <span class="text-lg font-bold text-accent-600 leading-none">
                                        {{ ev.start_at ? new Date(ev.start_at).getDate() : '?' }}
                                    </span>
                                    <span class="text-xs text-accent-500 uppercase">
                                        {{ ev.start_at ? new Date(ev.start_at).toLocaleString('default', { month: 'short' }) : '' }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate group-hover:text-accent-600">{{ ev.title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ ev.location || 'Virtual' }}</p>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-8 text-sm text-gray-400">
                            No upcoming events yet.
                            <Link :href="route('events.create')" class="block mt-2 text-accent-500 font-medium hover:underline">Create one →</Link>
                        </div>
                    </div>

                    <!-- Recent Forum Posts -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent Discussions</h3>
                            <Link :href="route('forum.index')" class="text-accent-500 hover:text-accent-600 text-sm font-medium">View all →</Link>
                        </div>
                        <div v-if="recentPosts?.length" class="space-y-3">
                            <Link v-for="post in recentPosts" :key="post.id" :href="route('forum.show', post.id)"
                                class="block p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                                <div class="flex items-start gap-2">
                                    <div class="shrink-0 w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300">
                                        {{ post.author?.name?.charAt(0) || '?' }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm text-gray-900 dark:text-gray-100 line-clamp-1 group-hover:text-accent-600">{{ post.title }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ post.author?.name }} &middot; {{ post.replies_count || 0 }} replies</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-8 text-sm text-gray-400">
                            No forum posts yet.
                            <Link :href="route('forum.create')" class="block mt-2 text-accent-500 font-medium hover:underline">Start a discussion →</Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthLayout>
</template>
