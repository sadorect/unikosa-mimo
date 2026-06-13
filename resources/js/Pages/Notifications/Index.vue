<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ notifications: Object, unreadCount: Number });

const markAsRead = (id) => {
    router.post(route('notifications.read', id));
};

const markAllRead = () => {
    router.post(route('notifications.read-all'));
};

const iconForType = (type) => {
    const icons = {
        member_approved: '👤',
        event_reminder: '📅',
        dues_reminder: '💰',
        forum_reply: '💬',
        blog_published: '📝',
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
                    <span v-if="unreadCount" class="ml-2 text-sm font-normal text-amber-600">({{ unreadCount }} unread)</span>
                </h2>
                <button v-if="unreadCount" @click="markAllRead" class="text-sm text-amber-600 hover:text-amber-700">Mark all as read</button>
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
                        :class="{ 'bg-amber-50/50 dark:bg-amber-900/10': !notification.read_at }"
                        @click="!notification.read_at && markAsRead(notification.id)">
                        <div class="flex items-start gap-3">
                            <span class="text-xl mt-0.5">{{ iconForType(notification.data?.type) }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ notification.data?.message }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ notification.created_at }}</p>
                            </div>
                            <div v-if="!notification.read_at" class="w-2 h-2 rounded-full bg-amber-500 mt-2 shrink-0"></div>
                        </div>
                    </div>
                </div>
                <div v-if="notifications.last_page > 1" class="mt-4 flex justify-center gap-2">
                    <Link v-if="notifications.prev_page_url" :href="notifications.prev_page_url"
                        class="px-3 py-1 text-sm bg-white dark:bg-gray-800 rounded border hover:bg-gray-50">Previous</Link>
                    <Link v-if="notifications.next_page_url" :href="notifications.next_page_url"
                        class="px-3 py-1 text-sm bg-white dark:bg-gray-800 rounded border hover:bg-gray-50">Next</Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
