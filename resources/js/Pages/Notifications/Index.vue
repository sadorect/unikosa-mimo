<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { timeAgo } from '@/lib/date';

defineProps({ notifications: Object, unreadCount: Number });

const markAsRead = (id) => {
    router.post(route('notifications.read', id));
};

const markAllRead = () => {
    router.post(route('notifications.read-all'));
};

const openNotification = (notification) => {
    if (!notification.read_at) {
        markAsRead(notification.id);
    }
    if (notification.data?.url) {
        router.visit(notification.data.url);
    }
};

const iconForType = (type) => {
    const icons = {
        member_approved: '👤',
        event_reminder: '📅',
        dues_reminder: '💰',
        forum_reply: '💬',
        blog_published: '📝',
        direct_message: '📩',
        default: '🔔',
    };
    return icons[type] || icons.default;
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Notifications" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    Notifications
                    <span v-if="unreadCount" class="ml-2 text-sm font-normal text-accent-600">({{ unreadCount }} unread)</span>
                </h2>
                <button v-if="unreadCount" @click="markAllRead" class="text-sm text-accent-600 hover:text-accent-700">Mark all as read</button>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-if="!notifications.data.length" class="p-8 text-center text-gray-500">
                        No notifications yet.
                    </div>
                    <div v-for="notification in notifications.data" :key="notification.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer"
                        :class="{ 'bg-accent-50/50 dark:bg-accent-900/10': !notification.read_at }"
                        @click="openNotification(notification)">
                        <div class="flex items-start gap-3">
                            <span class="text-xl mt-0.5">{{ iconForType(notification.data?.type) }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ notification.data?.message }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ timeAgo(notification.created_at) }}</p>
                            </div>
                            <div v-if="!notification.read_at" class="w-2 h-2 rounded-full bg-accent-500 mt-2 shrink-0"></div>
                        </div>
                    </div>
                </div>
                <Pagination :paginator="notifications" />
            </div>
        </div>
    </AuthLayout>
</template>
