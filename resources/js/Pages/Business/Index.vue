<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ businesses: Object, categories: Array, filters: Object });

const search = ref(filters?.search || '');
const selectedCategory = ref(filters?.category || '');

const filter = () => {
    router.get(route('business.index'), { search: search.value, category: selectedCategory.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <AuthLayout :auth="$page.props.auth" :settings="$page.props.settings">
        <Head title="Business Directory" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Business Directory</h2>
                <Link :href="route('business.create')" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded">List Your Business</Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6 flex flex-wrap gap-4 items-center">
                    <input v-model="search" @keyup.enter="filter" type="text" placeholder="Search businesses..."
                        class="flex-1 min-w-[200px] border-gray-300 rounded-md shadow-sm dark:bg-gray-700" />
                    <select v-model="selectedCategory" @change="filter" class="border-gray-300 rounded-md shadow-sm dark:bg-gray-700">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.category" :value="cat.category">{{ cat.category }} ({{ cat.count }})</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="biz in businesses.data" :key="biz.id" :href="route('business.show', biz)"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition block">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ biz.name }}</h3>
                            <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">{{ biz.category }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 mb-3">{{ biz.description }}</p>
                        <div class="text-xs text-gray-500">Listed by {{ biz.owner?.name }}</div>
                        <div v-if="biz.website" class="text-xs text-amber-600 mt-1">{{ biz.website }}</div>
                    </Link>
                </div>

                <div v-if="!businesses.data.length" class="text-center py-12 text-gray-500">No businesses found.</div>
            </div>
        </div>
    </AuthLayout>
</template>
