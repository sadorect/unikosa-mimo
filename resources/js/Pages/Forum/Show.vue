<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    post: Object,
});

const replyForm = useForm({ body: '' });

const submitReply = () => {
    replyForm.post(route('forum.reply', props.post.id), {
        onSuccess: () => replyForm.reset('body'),
    });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="post.title" />
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('forum.index')" class="text-amber-600 hover:text-amber-700 text-sm">Forum</Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm">{{ post.group?.name }}</span>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 font-bold">
                            {{ post.author?.name?.charAt(0) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ post.author?.name }}</div>
                            <div class="text-xs text-gray-500">{{ post.created_at }}</div>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">{{ post.title }}</h1>
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ post.body }}</div>
                    <div class="mt-4 flex items-center gap-4 text-xs text-gray-400">
                        <span>{{ post.replies_count || 0 }} replies</span>
                        <span>{{ post.views_count || 0 }} views</span>
                    </div>
                </div>

                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Replies ({{ post.replies?.length || 0 }})</h3>
                <div class="space-y-4 mb-6">
                    <div v-for="reply in post.replies" :key="reply.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 font-bold text-xs">
                                {{ reply.author?.name?.charAt(0) }}
                            </div>
                            <span class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ reply.author?.name }}</span>
                            <span class="text-xs text-gray-500">{{ reply.created_at }}</span>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ reply.body }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Post a Reply</h4>
                    <form @submit.prevent="submitReply">
                        <textarea v-model="replyForm.body" rows="3" placeholder="Write your reply..."
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600" required></textarea>
                        <div v-if="replyForm.errors.body" class="text-red-500 text-sm mt-1">{{ replyForm.errors.body }}</div>
                        <button type="submit" class="mt-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded" :disabled="replyForm.processing">
                            Post Reply
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
