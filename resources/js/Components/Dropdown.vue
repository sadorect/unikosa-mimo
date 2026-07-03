<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    align: { type: String, default: 'right' },
    width: { type: String, default: '48' },
    contentClasses: { type: Array, default: () => ['py-1'] },
});

// Map the width key to a fixed Tailwind width class (w-48 = 12rem, etc.).
// Previously this was `width + 'rem'`, which made "56" mean 56rem (896px)
// and blew the menu across the screen / triggered horizontal scroll.
const widthClass = computed(() => ({
    '48': 'w-48',
    '56': 'w-56',
    '60': 'w-60',
    '72': 'w-72',
}[props.width] || 'w-48'));

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
            class="absolute z-50 mt-2 rounded-lg shadow-lg"
            :class="[align === 'right' ? 'right-0' : 'left-0', widthClass]"
            @click="open = false"
        >
            <div class="rounded-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 dark:ring-white/10 overflow-hidden" :class="contentClasses">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>
