<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] },
    title: { type: String, default: 'Upcoming Celebrations' },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div v-if="items.length" class="bg-gradient-to-br from-accent-50 to-white dark:from-accent-900/20 dark:to-gray-800 border border-accent-100 dark:border-accent-900/40 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xl">🎉</span>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
        </div>
        <ul :class="compact ? 'flex flex-wrap gap-3' : 'space-y-3'">
            <li v-for="item in items" :key="item.type + item.user_id" :class="compact ? '' : 'flex items-center gap-3'">
                <Link :href="route('members.show', item.user_id)" class="flex items-center gap-3 group">
                    <div class="relative shrink-0">
                        <img v-if="item.avatar" :src="item.avatar" class="w-10 h-10 rounded-full object-cover" />
                        <div v-else class="w-10 h-10 rounded-full bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center text-accent-600 font-bold">
                            {{ item.name.charAt(0) }}
                        </div>
                        <span class="absolute -bottom-1 -right-1 text-sm">{{ item.type === 'birthday' ? '🎂' : '💍' }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate group-hover:text-accent-600 transition">{{ item.name }}</div>
                        <div class="text-xs text-gray-500">
                            {{ item.type === 'birthday' ? 'Birthday' : 'Anniversary' }} · {{ item.label }}
                        </div>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
