<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: '', password: '' });

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign In" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h1>
                <p class="text-sm text-gray-500 mt-1">Sign in to your alumni account</p>
            </div>

            <div v-if="status" class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm rounded-lg px-4 py-3">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email address</label>
                    <input v-model="form.email" type="email" autocomplete="email"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                        required autofocus placeholder="you@example.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <input v-model="form.password" type="password" autocomplete="current-password"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                        required placeholder="••••••••" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>

                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 px-4 rounded-lg transition disabled:opacity-60"
                    :disabled="form.processing">
                    {{ form.processing ? 'Signing in…' : 'Sign In' }}
                </button>
            </form>

            <div class="mt-6 space-y-2 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>
                    Don't have an account?
                    <Link :href="route('register')" class="text-amber-600 hover:text-amber-700 font-medium">Join the network</Link>
                </p>
                <p>
                    Have a pre-loaded profile?
                    <Link :href="route('claim-profile.show')" class="text-amber-600 hover:text-amber-700 font-medium">Claim it here</Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
