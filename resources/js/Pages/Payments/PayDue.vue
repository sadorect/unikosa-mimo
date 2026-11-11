<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PaymentMethodPicker from '@/Components/PaymentMethodPicker.vue';
import { computed } from 'vue';

const props = defineProps({
    due: Object,
});

const form = useForm({
    payment_method: null,
});

const canPay = computed(() => (usePage().props.settings?.payment_methods ?? []).length > 0);

const pay = () => {
    form.post(route('payments.due', props.due.id));
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Pay Dues" />
        <div class="py-12">
            <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Pay Dues</h1>
                    <div class="bg-accent-50 dark:bg-accent-900/20 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ due.name }}</div>
                        <div class="text-3xl font-bold text-accent-600 mt-1">{{ due.currency }} {{ (due.amount / 100).toFixed(2) }}</div>
                        <div v-if="due.description" class="text-sm text-gray-500 mt-2">{{ due.description }}</div>
                    </div>

                    <form @submit.prevent="pay" class="space-y-4">
                        <PaymentMethodPicker v-model="form.payment_method" :error="form.errors.payment_method" />
                        <button v-if="canPay" type="submit" :disabled="form.processing"
                            class="w-full bg-accent-500 hover:bg-accent-600 text-white font-bold py-3 px-4 rounded disabled:opacity-50">
                            {{ form.processing ? 'Processing...' : 'Pay Now' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
