<template>
    <div class="ui-page">
        <UiPageHeader
            title="Daily Usage"
            description="Totals per customer per UTC day from aggregated usage events."
        >
            <template #actions>
                <router-link :to="{ name: 'usage.record' }" class="ui-btn-primary">
                    Record usage
                </router-link>
            </template>
        </UiPageHeader>

        <p class="text-sm text-slate-600">
            Submit events on
            <router-link :to="{ name: 'usage.record' }" class="ui-link">Record usage</router-link>
            or run <code>php artisan usage:aggregate</code> after bulk imports.
        </p>

        <div class="ui-toolbar">
            <input v-model="dateFilter" type="date" class="ui-input w-auto" @change="loadData(1)" />
            <button type="button" class="ui-btn-secondary" @click="clearDate">Clear</button>
        </div>

        <UiAlert v-if="error">{{ error }}</UiAlert>
        <UiLoading v-if="loading" />

        <UiEmpty v-else-if="rows.length === 0" title="No daily totals yet">
            Aggregates appear after usage events are recorded and processed. Try
            <router-link :to="{ name: 'usage.record' }" class="ui-link"
                >
recording usage
</router-link
            >
            or widen the date filter (default: last 31 days).
        </UiEmpty>

        <template v-else>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="ui-table-num">Total Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in rows" :key="i">
                            <td class="ui-table-primary">{{ row.customer?.name ?? '—' }}</td>
                            <td>{{ row.usage_date }}</td>
                            <td class="ui-table-num">
                                {{ row.total_quantity.toLocaleString() }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UiPagination :meta="meta" label="rows" @change="loadData" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiEmpty from '@/components/UiEmpty.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import UiPagination from '@/components/UiPagination.vue';
import * as usageApi from '@/api/usage';
import type { DailyUsageData } from '@/types/usage';
import type { PaginatedResponse } from '@/types/plans';

const rows = ref<DailyUsageData[]>([]);
const meta = ref<PaginatedResponse<DailyUsageData>['meta'] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const dateFilter = ref('');

function clearDate() {
    dateFilter.value = '';
    loadData(1);
}

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
