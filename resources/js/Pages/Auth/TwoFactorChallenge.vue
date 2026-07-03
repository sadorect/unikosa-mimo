<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const recovery = ref(false);

const form = useForm({ code: '', recovery_code: '' });

const toggleRecovery = () => {
    recovery.value = !recovery.value;
    form.reset();
};

const submit = () => {
    form.transform((data) => (recovery.value ? { recovery_code: data.recovery_code } : { code: data.code }))
        .post(route('two-factor.login.store'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Two-Factor Authentication" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Two-factor authentication</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <template v-if="!recovery">Enter the 6-digit code from your authenticator app.</template>
                    <template v-else>Enter one of your recovery codes.</template>
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div v-if="!recovery">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                    <input v-model="form.code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
                        required autofocus placeholder="123456" />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">{{ form.errors.code }}</p>
                </div>
                <div v-else>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recovery code</label>
                    <input v-model="form.recovery_code" type="text" autocomplete="one-time-code"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
                        required autofocus />
                    <p v-if="form.errors.recovery_code" class="mt-1 text-xs text-red-500">{{ form.errors.recovery_code }}</p>
                </div>

                <button type="submit"
                    class="w-full bg-accent-500 hover:bg-accent-600 text-white font-semibold py-2.5 px-4 rounded-lg transition disabled:opacity-60"
                    :disabled="form.processing">
                    {{ form.processing ? 'Verifying…' : 'Verify' }}
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <button type="button" @click="toggleRecovery" class="text-accent-600 hover:text-accent-700 font-medium">
                    {{ recovery ? 'Use an authentication code instead' : 'Use a recovery code instead' }}
                </button>
            </div>
        </div>
    </GuestLayout>
</template>
