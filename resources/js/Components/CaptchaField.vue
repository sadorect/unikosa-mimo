<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    form: { type: String, required: true },
    answer: { type: String, default: '' },
    id: { type: String, default: '' },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:answer', 'update:id']);

const image = ref(null);
const enabled = ref(false);
const loading = ref(true);

const fetchCaptcha = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/captcha?form=${encodeURIComponent(props.form)}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        enabled.value = !!data.enabled;
        if (data.enabled) {
            image.value = data.image;
            emit('update:id', data.id);
        }
    } finally {
        loading.value = false;
    }
};

const refresh = () => {
    emit('update:answer', '');
    fetchCaptcha();
};

defineExpose({ refresh, enabled });

onMounted(fetchCaptcha);
</script>

<template>
    <div v-if="enabled" class="space-y-1.5">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Security check</label>
        <div class="flex items-center gap-2">
            <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden bg-white shrink-0 flex items-center justify-center min-w-[100px] min-h-[40px]">
                <img v-if="image" :src="image" alt="Captcha image" class="block" />
                <div v-else class="w-full h-10 animate-pulse bg-gray-100 dark:bg-gray-700"></div>
            </div>
            <button type="button" @click="refresh" title="Get a new image"
                class="p-2 rounded-lg text-gray-400 hover:text-accent-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <input
                :value="answer"
                @input="$emit('update:answer', $event.target.value)"
                type="text"
                autocomplete="off"
                placeholder="Enter the code above"
                class="flex-1 min-w-0 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent transition"
            />
        </div>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
