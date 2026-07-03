<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import CaptchaField from '@/Components/CaptchaField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ status: String });

const captcha = ref(null);
const form = useForm({ email: '', captcha_id: '', captcha_answer: '' });

const submit = () => {
    form.post(route('password.email'), {
        onError: () => captcha.value?.refresh(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Forgot your password?</h1>
                <p class="text-sm text-gray-500 mt-1">Enter your email and we'll send you a reset link</p>
            </div>

            <div v-if="status" class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm rounded-lg px-4 py-3">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email address</label>
                    <input v-model="form.email" type="email" autocomplete="email"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
                        required autofocus placeholder="you@example.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <CaptchaField ref="captcha" form="forgot_password" v-model:id="form.captcha_id" v-model:answer="form.captcha_answer" :error="form.errors.captcha_answer" />

                <button type="submit"
                    class="w-full bg-accent-500 hover:bg-accent-600 text-white font-semibold py-2.5 px-4 rounded-lg transition disabled:opacity-60"
                    :disabled="form.processing">
                    {{ form.processing ? 'Sending…' : 'Email Password Reset Link' }}
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <Link :href="route('login')" class="text-accent-600 hover:text-accent-700 font-medium">Back to sign in</Link>
            </div>
        </div>
    </GuestLayout>
</template>
