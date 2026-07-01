<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-10 text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Financial Transparency</h1>
        <p class="mt-2 text-gray-500 dark:text-gray-400">
          Public summary of community finances · Published {{ publishedAt }}
        </p>
      </div>

      <!-- Summary cards -->
      <div class="mb-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Raised</p>
          <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
            {{ formatAmount(totalRaised) }}
          </p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Contributors</p>
          <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ totalDonors }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Campaigns</p>
          <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
            {{ campaigns.filter(c => c.is_active).length }}
          </p>
        </div>
      </div>

      <!-- By currency -->
      <div class="mb-8 rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">By Currency</h2>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b text-left text-gray-500">
              <th class="pb-2">Currency</th>
              <th class="pb-2 text-right">Total</th>
              <th class="pb-2 text-right">Transactions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in byCurrency" :key="row.currency" class="border-b last:border-0">
              <td class="py-2 font-medium">{{ row.currency }}</td>
              <td class="py-2 text-right">{{ Number(row.total).toLocaleString() }}</td>
              <td class="py-2 text-right">{{ row.transactions }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Campaigns -->
      <div class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Campaigns</h2>
        <div class="space-y-4">
          <div v-for="c in campaigns" :key="c.id">
            <div class="flex justify-between text-sm">
              <span class="font-medium text-gray-700 dark:text-gray-300">{{ c.title }}</span>
              <span class="text-gray-500">
                {{ Number(c.raised_amount || 0).toLocaleString() }} / {{ Number(c.target_amount).toLocaleString() }} {{ c.currency }}
              </span>
            </div>
            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
              <div
                class="h-full rounded-full bg-accent-500 transition-all"
                :style="{ width: Math.min(((c.raised_amount || 0) / c.target_amount) * 100, 100) + '%' }"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  totalRaised: Number,
  totalDonors: Number,
  byCurrency: Array,
  byMethod: Array,
  campaigns: Array,
  monthly: Array,
  publishedAt: String,
})

function formatAmount(val) {
  return Number(val || 0).toLocaleString()
}
</script>
