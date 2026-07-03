<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import Footer from '@/Components/Footer.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    settings: Object,
});

const showingNavigationDropdown = ref(false);

const adminPanelRoles = ['super_admin', 'set_representative', 'chapter_head', 'content_moderator', 'finance_admin'];
const canAccessAdminPanel = computed(() => (props.auth?.user?.roles || []).some((role) => adminPanelRoles.includes(role)));

const roleLabels = {
    super_admin: 'Super Admin',
    set_representative: 'Set Representative',
    chapter_head: 'Chapter Head',
    content_moderator: 'Content Moderator',
    finance_admin: 'Finance Admin',
    member: 'Member',
};
const primaryRole = computed(() => {
    const roles = props.auth?.user?.roles || [];
    return roles.map((r) => roleLabels[r]).find(Boolean) || 'Member';
});

// Heroicon (outline) path data keyed for reuse in desktop + mobile nav.
const icons = {
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    directory: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    events: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    forum: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
    blog: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m-6 8h6m-6-4h6m2-5l2 2-6 6H9v-4l6-6z',
    sets: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    chapters: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    gallery: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    jobs: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    business: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-4m-6 0H3m2 0h4M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    campaigns: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    dues: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
};

const primaryNav = [
    { label: 'Dashboard', route: 'dashboard', pattern: 'dashboard', icon: icons.dashboard },
    { label: 'Directory', route: 'directory', pattern: 'directory', icon: icons.directory },
    { label: 'Events', route: 'events.index', pattern: 'events.*', icon: icons.events },
    { label: 'Forum', route: 'forum.index', pattern: 'forum.*', icon: icons.forum },
    { label: 'Blog', route: 'blog.index', pattern: 'blog.*', icon: icons.blog },
];

const moreGroups = [
    {
        label: 'Community',
        links: [
            { label: 'Sets', route: 'sets.index', pattern: 'sets.*', icon: icons.sets },
            { label: 'Chapters', route: 'chapters.index', pattern: 'chapters.*', icon: icons.chapters },
            { label: 'Gallery', route: 'gallery.index', pattern: 'gallery.*', icon: icons.gallery },
        ],
    },
    {
        label: 'Opportunities',
        links: [
            { label: 'Job Board', route: 'jobs.index', pattern: 'jobs.*', icon: icons.jobs },
            { label: 'Business Directory', route: 'business.index', pattern: 'business.*', icon: icons.business },
        ],
    },
    {
        label: 'Giving',
        links: [
            { label: 'Campaigns', route: 'campaigns.index', pattern: 'campaigns.*', icon: icons.campaigns },
            { label: 'My Dues', route: 'payments.dues.index', pattern: 'payments.dues.*', icon: icons.dues },
        ],
    },
];
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <nav class="bg-white/95 dark:bg-gray-800/95 backdrop-blur border-b border-gray-100 dark:border-gray-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 min-w-0">

                    <!-- Logo + primary nav (lg+) -->
                    <div class="flex items-center min-w-0">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-accent-500" />
                            </Link>
                        </div>
                        <div class="hidden lg:flex lg:items-center lg:ms-6 lg:gap-1">
                            <NavLink v-for="item in primaryNav" :key="item.route" :href="route(item.route)" :active="route().current(item.pattern)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" /></svg>
                                {{ item.label }}
                            </NavLink>

                            <!-- More: grouped, with icons -->
                            <Dropdown align="left" width="60">
                                <template #trigger>
                                    <button type="button" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        More
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </template>
                                <template #content>
                                    <template v-for="(group, index) in moreGroups" :key="group.label">
                                        <div v-if="index > 0" class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                        <div class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                            {{ group.label }}
                                        </div>
                                        <Link v-for="link in group.links" :key="link.route" :href="route(link.route)"
                                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 transition">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" /></svg>
                                            {{ link.label }}
                                        </Link>
                                    </template>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Right: bell + theme + user menu (sm+) + hamburger (lg-) -->
                    <div class="flex items-center gap-2 shrink-0">
                        <ThemeToggle />

                        <div class="hidden sm:flex items-center gap-2">
                            <Link :href="route('messages.index')" class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700/50 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                <span v-if="$page.props.messages?.unread_count"
                                    class="absolute top-0.5 right-0.5 bg-red-500 text-white text-[10px] leading-none min-w-4 h-4 px-1 rounded-full flex items-center justify-center">
                                    {{ $page.props.messages.unread_count > 9 ? '9+' : $page.props.messages.unread_count }}
                                </span>
                            </Link>
                            <Link :href="route('notifications.index')" class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700/50 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                <span v-if="$page.props.notifications?.unread_count"
                                    class="absolute top-0.5 right-0.5 bg-red-500 text-white text-[10px] leading-none min-w-4 h-4 px-1 rounded-full flex items-center justify-center">
                                    {{ $page.props.notifications.unread_count > 9 ? '9+' : $page.props.notifications.unread_count }}
                                </span>
                            </Link>

                            <!-- User avatar menu -->
                            <Dropdown align="right" width="56">
                                <template #trigger>
                                    <button type="button" class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700/50 transition focus:outline-none focus:ring-2 focus:ring-accent-500">
                                        <img v-if="auth.user.avatar" :src="auth.user.avatar" class="w-8 h-8 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-600" />
                                        <div v-else class="w-8 h-8 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold text-sm">
                                            {{ auth.user.name.charAt(0) }}
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center gap-3">
                                            <img v-if="auth.user.avatar" :src="auth.user.avatar" class="w-10 h-10 rounded-full object-cover" />
                                            <div v-else class="w-10 h-10 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold">
                                                {{ auth.user.name.charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ auth.user.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth.user.email }}</div>
                                            </div>
                                        </div>
                                        <span class="inline-block mt-2 text-[10px] font-medium uppercase tracking-wide bg-accent-50 dark:bg-accent-900/30 text-accent-700 dark:text-accent-300 px-2 py-0.5 rounded-full">
                                            {{ primaryRole }}
                                        </span>
                                    </div>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('payments.history')">Payment History</DropdownLink>
                                    <a v-if="canAccessAdminPanel" href="/admin"
                                        class="block w-full text-start px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition duration-150 ease-in-out">
                                        Admin Panel
                                    </a>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Sign Out</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Hamburger (below lg) -->
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ 'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ 'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile / tablet nav drawer -->
            <div :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }" class="lg:hidden border-t border-gray-100 dark:border-gray-700">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink v-for="item in primaryNav" :key="item.route" :href="route(item.route)" :active="route().current(item.pattern)">
                        {{ item.label }}
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('messages.index')" :active="route().current('messages.*')">
                        Messages
                        <span v-if="$page.props.messages?.unread_count" class="ml-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                            {{ $page.props.messages.unread_count }}
                        </span>
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('notifications.index')" :active="route().current('notifications.*')">
                        Notifications
                        <span v-if="$page.props.notifications?.unread_count" class="ml-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                            {{ $page.props.notifications.unread_count }}
                        </span>
                    </ResponsiveNavLink>

                    <template v-for="group in moreGroups" :key="group.label">
                        <div class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                            {{ group.label }}
                        </div>
                        <ResponsiveNavLink v-for="link in group.links" :key="link.route" :href="route(link.route)" :active="route().current(link.pattern)">
                            {{ link.label }}
                        </ResponsiveNavLink>
                    </template>
                </div>
                <div class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center px-4 gap-3">
                        <img v-if="auth.user.avatar" :src="auth.user.avatar" class="w-10 h-10 rounded-full object-cover" />
                        <div v-else class="w-10 h-10 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold">
                            {{ auth.user.name.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ auth.user.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ auth.user.email }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('payments.history')">Payment History</ResponsiveNavLink>
                        <a v-if="canAccessAdminPanel" href="/admin"
                            class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out">
                            Admin Panel
                        </a>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Sign Out</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>
        <main><slot /></main>
        <Footer />
    </div>
</template>
