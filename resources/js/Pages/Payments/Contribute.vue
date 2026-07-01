<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    campaign: Object,
});

const form = useForm({
    amount: 5000,
    payment_method: 'paystack',
});

const donate = () => {
    form.post(route('payments.campaign', props.campaign.id));
};

const progressPercent = props.campaign.target_amount > 0
    ? Math.min(100, Math.round((props.campaign.raised_amount / props.campaign.target_amount) * 100))
    : 0;
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head :title="campaign.title" />
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">{{ campaign.title }}</h1>
                    <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-400 mb-6" v-html="campaign.description"></div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 mb-8">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>{{ campaign.currency }} {{ (campaign.raised_amount / 100).toFixed(2) }} raised</span>
                            <span>{{ campaign.currency }} {{ (campaign.target_amount / 100).toFixed(2) }} goal</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                            <div class="bg-accent-500 h-3 rounded-full transition-all" :style="{ width: progressPercent + '%' }"></div>
                        </div>
                        <div class="text-center text-sm text-gray-500 mt-2">{{ progressPercent }}% funded</div>
                    </div>

                    <form @submit.prevent="donate" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Donation Amount</label>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 font-medium">{{ campaign.currency }}</span>
                                <input v-model.number="form.amount" type="number" min="100" step="100"
                                    class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:bg-gray-700" required />
                            </div>
                            <div v-if="form.errors.amount" class="text-red-500 text-sm mt-1">{{ form.errors.amount }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:border-accent-500"
                                    :class="{ 'border-accent-500 bg-accent-50 dark:bg-accent-900/20': form.payment_method === 'paystack' }">
                                    <input type="radio" v-model="form.payment_method" value="paystack" class="text-accent-500" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">Paystack</div>
                                        <div class="text-xs text-gray-500">NGN payments</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:border-accent-500"
                                    :class="{ 'border-accent-500 bg-accent-50 dark:bg-accent-900/20': form.payment_method === 'stripe' }">
                                    <input type="radio" v-model="form.payment_method" value="stripe" class="text-accent-500" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">Stripe</div>
                                        <div class="text-xs text-gray-500">International card</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="w-full bg-accent-500 hover:bg-accent-600 text-white font-bold py-3 px-4 rounded disabled:opacity-50">
                            {{ form.processing ? 'Processing...' : 'Donate' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
