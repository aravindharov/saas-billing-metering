<template>
    <div class="ui-page">
        <UiPageHeader title="Plans">
            <template #actions>
                <select
                    v-model="statusFilter"
                    class="ui-select w-auto min-w-[10rem]"
                    @change="loadPlans(1)"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>
                <router-link v-if="isOwner" :to="{ name: 'plans.create' }" class="ui-btn-primary">
                    Create Plan
                </router-link>
            </template>
        </UiPageHeader>

        <UiAlert v-if="error">{{ error }}</UiAlert>
        <UiLoading v-if="loading" message="Loading plans…" />
        <UiEmpty v-else-if="plans.length === 0" title="No plans found" message="No plans found." />

        <template v-else>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Base Price</th>
                            <th>Billing Cycle</th>
                            <th>Included Units</th>
                            <th>Overage Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans" :key="plan.id">
                            <td class="ui-table-primary">{{ plan.name }}</td>
                            <td>{{ formatMoney(plan.base_price) }}</td>
                            <td class="capitalize">{{ plan.billing_cycle }}</td>
                            <td>{{ plan.included_usage_units.toLocaleString() }}</td>
                            <td>{{ formatMoney(plan.overage_rate) }}</td>
                            <td>
                                <UiBadge
                                    :variant="plan.status === 'active' ? 'success' : 'neutral'"
                                >
                                    {{ plan.status }}
                                </UiBadge>
                            </td>
                            <td class="space-x-3 whitespace-nowrap">
                                <router-link
                                    :to="{ name: 'plans.edit', params: { id: plan.id } }"
                                    class="ui-link"
                                    :class="{ 'pointer-events-none opacity-50': !isOwner }"
                                >
                                    Edit
                                </router-link>
                                <button
                                    v-if="isOwner && plan.status === 'active'"
                                    type="button"
                                    class="ui-btn-danger px-0 py-0"
                                    @click="confirmArchive(plan)"
                                >
                                    Archive
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UiPagination :meta="meta" label="plans" @change="loadPlans" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiBadge from '@/components/UiBadge.vue';
import UiEmpty from '@/components/UiEmpty.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import UiPagination from '@/components/UiPagination.vue';
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
