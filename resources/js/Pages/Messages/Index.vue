<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { timeAgo } from '@/lib/date';

defineProps({ conversations: Array });
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Messages" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Messages</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-if="!conversations.length" class="p-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        No conversations yet. Visit a member's profile to send them a message.
                    </div>
                    <Link v-for="conversation in conversations" :key="conversation.user.id"
                        :href="route('messages.show', conversation.user.id)"
                        class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <img v-if="conversation.user.avatar" :src="conversation.user.avatar" class="w-12 h-12 rounded-full object-cover shrink-0" />
                        <div v-else class="w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold shrink-0">
                            {{ conversation.user.name.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100 truncate"
                                    :class="{ 'font-semibold': conversation.unread_count > 0 }">
                                    {{ conversation.user.name }}
                                </span>
                                <span class="text-xs text-gray-400 shrink-0">{{ timeAgo(conversation.last_message?.created_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate"
                                :class="{ 'text-gray-800 dark:text-gray-200 font-medium': conversation.unread_count > 0 }">
                                {{ conversation.last_message?.body }}
                            </p>
                        </div>
                        <span v-if="conversation.unread_count" class="shrink-0 bg-accent-500 text-white text-xs font-semibold rounded-full min-w-5 h-5 px-1.5 flex items-center justify-center">
                            {{ conversation.unread_count }}
                        </span>
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
