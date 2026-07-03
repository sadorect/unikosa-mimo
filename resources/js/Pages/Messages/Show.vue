<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { formatDateTime } from '@/lib/date';
import { nextTick, onMounted, ref } from 'vue';

const props = defineProps({ otherUser: Object, messages: Array });

const page = usePage();
const myId = page.props.auth.user.id;

const form = useForm({ body: '' });
const scrollContainer = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
        }
    });
};

const submit = () => {
    if (!form.body.trim()) return;
    form.post(route('messages.store', props.otherUser.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            scrollToBottom();
        },
    });
};

onMounted(scrollToBottom);
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="`Messages · ${otherUser.name}`" />
        <template #header>
            <Breadcrumb :items="[{ label: 'Messages', href: route('messages.index') }, { label: otherUser.name }]" />
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm flex flex-col h-[65vh]">
                    <div class="flex items-center gap-3 p-4 border-b border-gray-200 dark:border-gray-700">
                        <img v-if="otherUser.avatar" :src="otherUser.avatar" class="w-10 h-10 rounded-full object-cover" />
                        <div v-else class="w-10 h-10 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold">
                            {{ otherUser.name.charAt(0) }}
                        </div>
                        <Link :href="route('members.show', otherUser.id)" class="font-semibold text-gray-900 dark:text-gray-100 hover:text-accent-600 transition">
                            {{ otherUser.name }}
                        </Link>
                    </div>

                    <div ref="scrollContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div v-if="!messages.length" class="text-center text-gray-400 text-sm py-8">
                            Say hello to {{ otherUser.name }} 👋
                        </div>
                        <div v-for="message in messages" :key="message.id" class="flex" :class="message.sender_id === myId ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[75%]">
                                <div class="px-4 py-2 rounded-2xl text-sm whitespace-pre-wrap break-words"
                                    :class="message.sender_id === myId
                                        ? 'bg-accent-500 text-white rounded-br-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-bl-sm'">
                                    {{ message.body }}
                                </div>
                                <div class="text-[11px] text-gray-400 mt-1" :class="message.sender_id === myId ? 'text-right' : 'text-left'">
                                    {{ formatDateTime(message.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-end gap-2">
                        <textarea v-model="form.body" rows="1" placeholder="Write a message…" @keydown.enter.exact.prevent="submit"
                            class="flex-1 resize-none border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"></textarea>
                        <button type="submit" :disabled="form.processing || !form.body.trim()"
                            class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-4 py-2 rounded-lg transition disabled:opacity-50 shrink-0">
                            Send
                        </button>
                    </form>
                    <p v-if="form.errors.body" class="px-4 pb-3 text-xs text-red-500">{{ form.errors.body }}</p>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
