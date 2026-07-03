<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import CaptchaField from '@/Components/CaptchaField.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ token: String, email: String });

const captcha = ref(null);
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
    captcha_id: '',
    captcha_answer: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: () => captcha.value?.refresh(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Set a new password</h1>
                <p class="text-sm text-gray-500 mt-1">Choose a new password for your account</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email address</label>
                    <input v-model="form.email" type="email" autocomplete="email"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
                        required autofocus />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New password</label>
                    <PasswordInput v-model="form.password" autocomplete="new-password"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
                        required placeholder="••••••••" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm password</label>
                    <PasswordInput v-model="form.password_confirmation" autocomplete="new-password"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
                        required placeholder="••••••••" />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
                </div>

                <CaptchaField ref="captcha" form="reset_password" v-model:id="form.captcha_id" v-model:answer="form.captcha_answer" :error="form.errors.captcha_answer" />

                <button type="submit"
                    class="w-full bg-accent-500 hover:bg-accent-600 text-white font-semibold py-2.5 px-4 rounded-lg transition disabled:opacity-60"
                    :disabled="form.processing">
                    {{ form.processing ? 'Resetting…' : 'Reset Password' }}
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
