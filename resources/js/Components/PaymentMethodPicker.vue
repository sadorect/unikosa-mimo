<script setup>
import { computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Lists only the gateways the server has keys for (settings.payment_methods).
// With none configured it says so instead of offering a button that cannot work.
const model = defineModel({ type: String, default: null });
defineProps({ compact: { type: Boolean, default: false }, error: { type: String, default: null } });

const labels = {
    paystack: { name: 'Paystack', hint: 'Pay in NGN (card, bank transfer, USSD)' },
    stripe: { name: 'Stripe', hint: 'International card (USD/GBP/EUR)' },
};

const methods = computed(() => usePage().props.settings?.payment_methods ?? []);
const available = computed(() => methods.value.length > 0);
// The layouts don't render flash messages, so a failed checkout would otherwise be silent.
const flashError = computed(() => usePage().props.flash?.error ?? null);

onMounted(() => {
    if (!methods.value.includes(model.value)) model.value = methods.value[0] ?? null;
});

defineExpose({ available });
</script>

<template>
    <div>
        <div v-if="!available" class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
            Online payment isn't open yet. Please check back soon or contact the association's treasurer.
        </div>
        <template v-else>
            <label v-if="!compact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
            <div :class="compact ? 'flex flex-wrap gap-3' : 'space-y-2'">
                <label v-for="method in methods" :key="method"
                    class="flex items-center gap-3 border rounded-lg cursor-pointer hover:border-accent-500"
                    :class="[compact ? 'px-4 py-2' : 'p-3', { 'border-accent-500 bg-accent-50 dark:bg-accent-900/20': model === method }]">
                    <input type="radio" v-model="model" :value="method" class="text-accent-500" />
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100" :class="{ 'text-sm': compact }">{{ labels[method]?.name ?? method }}</div>
                        <div v-if="!compact" class="text-xs text-gray-500">{{ labels[method]?.hint }}</div>
                    </div>
                </label>
            </div>
        </template>
        <div v-if="error" class="text-red-500 text-sm mt-1">{{ error }}</div>
        <div v-else-if="flashError" class="mt-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ flashError }}</div>
    </div>
</template>
