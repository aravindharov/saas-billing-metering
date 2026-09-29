<template>
    <div>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-800">Daily Usage</h2>
            <router-link
                :to="{ name: 'usage.record' }"
                class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
                Record usage
            </router-link>
        </div>

        <p class="mb-4 text-sm text-gray-600">
            Totals per customer per UTC day (from aggregated usage events). Use
            <router-link :to="{ name: 'usage.record' }" class="text-blue-600 hover:underline">
                Record usage
            </router-link>
            to submit new events, or run
            <code class="rounded bg-gray-100 px-1 text-xs">php artisan usage:aggregate</code>
            after bulk imports.
        </p>

        <div class="mb-4 flex items-center gap-3">
            <input
                v-model="dateFilter"
                type="date"
                class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="loadData(1)"
            />
            <button
                class="rounded bg-gray-200 px-3 py-2 text-sm hover:bg-gray-300"
                @click="
                    dateFilter = '';
                    loadData(1);
                "
            >
                Clear
            </button>
        </div>

        <div
            v-if="error"
            class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-sm text-gray-500">Loading…</div>

        <div
            v-else-if="rows.length === 0"
            class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600"
        >
            <p class="font-medium text-gray-800">No daily totals yet</p>
            <p class="mt-2">
                Aggregates appear after usage events are recorded and processed. Try
                <router-link :to="{ name: 'usage.record' }" class="text-blue-600 hover:underline">
                    recording usage
                </router-link>
                or widen the date filter (default: last 31 days).
            </p>
        </div>

        <div v-else>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Customer
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Total Usage
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(row, i) in rows" :key="i">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ row.customer?.name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ row.usage_date }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ row.total_quantity.toLocaleString() }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="meta && meta.last_page > 1"
                class="mt-4 flex items-center justify-between text-sm text-gray-600"
            >
                <span
                    >Page {{ meta.current_page }} of {{ meta.last_page }} ({{
                        meta.total
                    }}
                    rows)</span
                >
                <div class="space-x-2">
                    <button
                        :disabled="meta.current_page <= 1"
                        class="rounded border px-3 py-1 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="loadData(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="meta.current_page >= meta.last_page"
                        class="rounded border px-3 py-1 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="loadData(meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import * as usageApi from '@/api/usage';
import type { DailyUsageData } from '@/types/usage';
import type { PaginatedResponse } from '@/types/plans';

const rows = ref<DailyUsageData[]>([]);
const meta = ref<PaginatedResponse<DailyUsageData>['meta'] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const dateFilter = ref('');

async function loadData(page = 1) {
    loading.value = true;
    error.value = null;
    try {
        const response = await usageApi.getDailyUsage(page, dateFilter.value || undefined);
        rows.value = response.data;
        meta.value = response.meta;
    } catch {
        error.value = 'Failed to load daily usage.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => loadData());
</script>
