<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Plans</h2>
            <div class="flex items-center gap-3">
                <select
                    v-model="statusFilter"
                    class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @change="loadPlans(1)"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>
                <router-link
                    v-if="isOwner"
                    :to="{ name: 'plans.create' }"
                    class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700"
                >
                    Create Plan
                </router-link>
            </div>
        </div>

        <div
            v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm mb-4"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-gray-500 text-sm">Loading plans…</div>

        <div v-else-if="plans.length === 0" class="text-gray-500 text-sm">No plans found.</div>

        <div v-else>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Base Price
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Billing Cycle
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Included Units
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Overage Rate
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="plan in plans" :key="plan.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ plan.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ formatMoney(plan.base_price) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 capitalize">
                                {{ plan.billing_cycle }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ plan.included_usage_units.toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ formatMoney(plan.overage_rate) }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="
                                        plan.status === 'active'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    "
                                >
                                    {{ plan.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <router-link
                                    :to="{ name: 'plans.edit', params: { id: plan.id } }"
                                    class="text-blue-600 hover:text-blue-800"
                                    :class="{ 'pointer-events-none opacity-50': !isOwner }"
                                >
                                    Edit
                                </router-link>
                                <button
                                    v-if="isOwner && plan.status === 'active'"
                                    class="text-red-600 hover:text-red-800"
                                    @click="confirmArchive(plan)"
                                >
                                    Archive
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="meta && meta.last_page > 1"
                class="flex items-center justify-between mt-4 text-sm text-gray-600"
            >
                <span
                    >Page {{ meta.current_page }} of {{ meta.last_page }} ({{
                        meta.total
                    }}
                    plans)</span
                >
                <div class="space-x-2">
                    <button
                        :disabled="meta.current_page <= 1"
                        class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100"
                        @click="loadPlans(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100"
                        @click="loadPlans(meta.current_page + 1)"
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
import { useAuth } from '@/composables/useAuth';
import * as plansApi from '@/api/plans';
import type { Plan, PaginatedResponse } from '@/types/plans';

const { isOwner } = useAuth();
const plans = ref<Plan[]>([]);
const meta = ref<PaginatedResponse<Plan>['meta'] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const statusFilter = ref('');

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}

async function loadPlans(page = 1) {
    loading.value = true;
    error.value = null;
    try {
        const response = await plansApi.getPlans(page, statusFilter.value || undefined);
        plans.value = response.data;
        meta.value = response.meta;
    } catch {
        error.value = 'Failed to load plans.';
    } finally {
        loading.value = false;
    }
}

async function confirmArchive(plan: Plan) {
    if (
        !confirm(
            `Archive plan "${plan.name}"? It will no longer be available for new subscriptions.`,
        )
    ) {
        return;
    }
    try {
        await plansApi.archivePlan(plan.id);
        await loadPlans(meta.value?.current_page ?? 1);
    } catch {
        error.value = 'Failed to archive plan.';
    }
}

onMounted(() => loadPlans());
</script>
