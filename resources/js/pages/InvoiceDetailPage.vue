<template>
    <div>
        <router-link
            :to="{ name: 'invoices.index' }"
            class="mb-4 inline-block text-sm text-blue-600 hover:underline"
        >
            ← Back to invoices
        </router-link>

        <div v-if="loading" class="text-sm text-gray-500">Loading…</div>

        <div
            v-else-if="error"
            class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div v-else-if="invoice">
            <h2 class="mb-2 text-2xl font-bold text-gray-800">Invoice</h2>
            <p class="mb-6 font-mono text-xs text-gray-500">{{ invoice.id }}</p>

            <dl class="mb-6 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500">Customer</dt>
                    <dd class="font-medium text-gray-900">{{ invoice.customer?.name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Subscription</dt>
                    <dd class="font-mono text-xs text-gray-700">
                        {{ invoice.subscription?.id ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Billing period</dt>
                    <dd class="text-gray-900">
                        {{ formatDate(invoice.billing_period_start) }} —
                        {{ formatDate(invoice.billing_period_end) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Status</dt>
                    <dd class="capitalize text-gray-900">{{ invoice.status }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Issued</dt>
                    <dd class="text-gray-900">
                        {{ invoice.issued_at ? formatDate(invoice.issued_at) : '—' }}
                    </dd>
                </div>
            </dl>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Description
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Qty
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Unit price
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(line, idx) in invoice.lines ?? []" :key="idx">
                            <td class="px-6 py-4 text-sm capitalize text-gray-700">
                                {{ line.type }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ line.description }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-700">
                                {{ line.quantity }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-700">
                                {{ formatMoney(line.unit_price) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                {{ formatMoney(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">
                                Subtotal
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">
                                {{ formatMoney(invoice.subtotal) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">
                                Total
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                {{ formatMoney(invoice.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import * as invoicesApi from '@/api/invoices';
import type { InvoiceData } from '@/types/invoices';

const route = useRoute();
const invoice = ref<InvoiceData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString();
}

onMounted(async () => {
    const id = route.params.id as string;
    try {
        invoice.value = await invoicesApi.getInvoice(id);
    } catch {
        error.value = 'Invoice not found or could not be loaded.';
    } finally {
        loading.value = false;
    }
});
</script>
