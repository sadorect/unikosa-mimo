<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    settings: Object,
});

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 min-w-0">

                    <!-- Logo + primary nav (lg+) -->
                    <div class="flex items-center min-w-0">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-accent-500" />
                            </Link>
                        </div>
                        <div class="hidden lg:flex lg:items-center lg:ms-8 lg:space-x-1">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                            <NavLink :href="route('directory')" :active="route().current('directory')">Directory</NavLink>
                            <NavLink :href="route('events.index')" :active="route().current('events.*')">Events</NavLink>
                            <NavLink :href="route('forum.index')" :active="route().current('forum.*')">Forum</NavLink>
                            <NavLink :href="route('blog.index')" :active="route().current('blog.*')">Blog</NavLink>
                            <!-- More dropdown -->
                            <div class="relative flex items-center">
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button type="button" class="inline-flex items-center px-3 pt-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 border-b-2 border-transparent hover:border-gray-300 dark:hover:border-gray-600 transition h-16">
                                            More
                                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('sets.index')">Sets</DropdownLink>
                                        <DropdownLink :href="route('chapters.index')">Chapters</DropdownLink>
                                        <DropdownLink :href="route('jobs.index')">Job Board</DropdownLink>
                                        <DropdownLink :href="route('gallery.index')">Gallery</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>

                    <!-- Right: bell + Sign Out (sm+) + hamburger (lg-) -->
                    <div class="flex items-center gap-3 shrink-0">
                        <ThemeToggle />
                        <!-- Bell + Sign Out visible from sm -->
                        <div class="hidden sm:flex items-center gap-3">
                            <Link :href="route('notifications.index')" class="relative text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                <span v-if="$page.props.notifications?.unread_count"
                                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">
                                    {{ $page.props.notifications.unread_count > 9 ? '9+' : $page.props.notifications.unread_count }}
                                </span>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium text-white bg-accent-500 hover:bg-accent-600 transition whitespace-nowrap">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                Sign Out
                            </Link>
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
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('directory')" :active="route().current('directory')">Directory</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('events.index')" :active="route().current('events.*')">Events</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('forum.index')" :active="route().current('forum.*')">Forum</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('blog.index')" :active="route().current('blog.*')">Blog</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('sets.index')" :active="route().current('sets.*')">Sets</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('chapters.index')" :active="route().current('chapters.*')">Chapters</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('jobs.index')" :active="route().current('jobs.*')">Job Board</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('gallery.index')" :active="route().current('gallery.*')">Gallery</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('notifications.index')" :active="route().current('notifications.*')">
                        Notifications
                        <span v-if="$page.props.notifications?.unread_count" class="ml-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                            {{ $page.props.notifications.unread_count }}
                        </span>
                    </ResponsiveNavLink>
                </div>
                <div class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-600">
                    <div class="px-4 font-medium text-gray-800 dark:text-gray-200">{{ auth.user.name }}</div>
                    <div class="px-4 text-sm text-gray-500 dark:text-gray-400">{{ auth.user.email }}</div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
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
    </div>
</template>
