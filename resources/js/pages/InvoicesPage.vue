<template>
    <div class="ui-page">
        <UiPageHeader title="Invoices">
            <template #actions>
                <select
                    v-model="statusFilter"
                    class="ui-select w-auto min-w-[10rem]"
                    @change="loadInvoices(1)"
                >
                    <option value="">All statuses</option>
                    <option value="issued">Issued</option>
                    <option value="draft">Draft</option>
                </select>
            </template>
        </UiPageHeader>

        <UiAlert v-if="error">{{ error }}</UiAlert>
        <UiLoading v-if="loading" message="Loading invoices…" />
        <UiEmpty
            v-else-if="invoices.length === 0"
            title="No invoices found"
            message="No invoices found."
        />

        <template v-else>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Billing period</th>
                            <th class="ui-table-num">Subtotal</th>
                            <th class="ui-table-num">Total</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="inv in invoices" :key="inv.id">
                            <td class="font-mono text-xs text-slate-600">{{ inv.id }}</td>
                            <td class="ui-table-primary">{{ inv.customer?.name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-slate-600">
                                {{ formatDate(inv.billing_period_start) }} —
                                {{ formatDate(inv.billing_period_end) }}
                            </td>
                            <td class="ui-table-num">{{ formatMoney(inv.subtotal) }}</td>
                            <td class="ui-table-num font-medium text-slate-900">
                                {{ formatMoney(inv.total) }}
                            </td>
                            <td class="capitalize">{{ inv.status }}</td>
                            <td class="text-slate-500">
                                {{ inv.issued_at ? formatDate(inv.issued_at) : '—' }}
                            </td>
                            <td>
                                <router-link
                                    :to="{ name: 'invoices.show', params: { id: inv.id } }"
                                    class="ui-link"
                                >
                                    View
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UiPagination :meta="meta" label="invoices" @change="loadInvoices" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiEmpty from '@/components/UiEmpty.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import UiPagination from '@/components/UiPagination.vue';
import * as invoicesApi from '@/api/invoices';
import type { InvoiceData } from '@/types/invoices';
import type { PaginatedResponse } from '@/types/plans';

const invoices = ref<InvoiceData[]>([]);
const meta = ref<PaginatedResponse<InvoiceData>['meta'] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const statusFilter = ref('');

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString();
}

async function loadInvoices(page = 1) {
    loading.value = true;
    error.value = null;
    try {
        const response = await invoicesApi.getInvoices(
            page,
            statusFilter.value ? { status: statusFilter.value } : undefined,
        );
        invoices.value = response.data;
        meta.value = response.meta;
    } catch {
        error.value = 'Failed to load invoices.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => loadInvoices());
</script>
