<template>
    <div>
        <h2 class="mb-6 text-2xl font-bold text-gray-800">Invoices</h2>

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <select
                v-model="statusFilter"
                class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="loadInvoices(1)"
            >
                <option value="">All statuses</option>
                <option value="issued">Issued</option>
                <option value="draft">Draft</option>
            </select>
        </div>

        <div
            v-if="error"
            class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-sm text-gray-500">Loading invoices…</div>

        <div v-else-if="invoices.length === 0" class="text-sm text-gray-500">
            No invoices found.
        </div>

        <div v-else>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Invoice
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Customer
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Billing period
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Subtotal
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Total
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Issued
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="inv in invoices" :key="inv.id">
                            <td class="px-6 py-4 font-mono text-xs text-gray-700">
                                {{ inv.id }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ inv.customer?.name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ formatDate(inv.billing_period_start) }} —
                                {{ formatDate(inv.billing_period_end) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ formatMoney(inv.subtotal) }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ formatMoney(inv.total) }}
                            </td>
                            <td class="px-6 py-4 text-sm capitalize text-gray-700">
                                {{ inv.status }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ inv.issued_at ? formatDate(inv.issued_at) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <router-link
                                    :to="{ name: 'invoices.show', params: { id: inv.id } }"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    View
                                </router-link>
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
                    invoices)</span
                >
                <div class="space-x-2">
                    <button
                        :disabled="meta.current_page <= 1"
                        class="rounded border px-3 py-1 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="loadInvoices(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="meta.current_page >= meta.last_page"
                        class="rounded border px-3 py-1 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="loadInvoices(meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
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
