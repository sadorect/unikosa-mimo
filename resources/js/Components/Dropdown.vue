<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    align: { type: String, default: 'left' },
    width: { type: String, default: '48' },
    contentClasses: { type: Array, default: () => ['py-1', 'bg-white'] },
});

const open = ref(false);
const dropdown = ref(null);

const close = (event) => {
    if (dropdown.value && !dropdown.value.contains(event.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('click', close));
onUnmounted(() => document.removeEventListener('click', close));
</script>

<template>
    <div class="relative" ref="dropdown">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>
        <div v-show="open"
            class="absolute z-50 mt-2 rounded-md shadow-lg"
            :class="[align === 'right' ? 'right-0' : 'left-0']"
            :style="{ width: width + 'rem' }"
            @click="open = false"
        >
            <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>
