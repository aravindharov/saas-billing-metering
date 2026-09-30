<template>
    <div class="ui-page">
        <UiPageHeader title="Invoice">
            <template #actions>
                <router-link :to="{ name: 'invoices.index' }" class="ui-link-muted">
                    ← Back to invoices
                </router-link>
            </template>
        </UiPageHeader>

        <UiLoading v-if="loading" />
        <UiAlert v-else-if="error">{{ error }}</UiAlert>

        <template v-else-if="invoice">
            <p class="font-mono text-xs text-slate-500">{{ invoice.id }}</p>

            <section class="ui-card ui-card-body">
                <dl class="ui-dl-grid">
                    <div class="ui-dl-item">
                        <dt>Customer</dt>
                        <dd>{{ invoice.customer?.name ?? '—' }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Subscription</dt>
                        <dd class="font-mono text-xs font-normal text-slate-600">
                            {{ invoice.subscription?.id ?? '—' }}
                        </dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Billing period</dt>
                        <dd>
                            {{ formatDate(invoice.billing_period_start) }} —
                            {{ formatDate(invoice.billing_period_end) }}
                        </dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Status</dt>
                        <dd class="capitalize">{{ invoice.status }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Issued</dt>
                        <dd>{{ invoice.issued_at ? formatDate(invoice.issued_at) : '—' }}</dd>
                    </div>
                </dl>
            </section>

            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="ui-table-num">Qty</th>
                            <th class="ui-table-num">Unit price</th>
                            <th class="ui-table-num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, idx) in invoice.lines ?? []" :key="idx">
                            <td class="capitalize">{{ line.type }}</td>
                            <td class="ui-table-primary">{{ line.description }}</td>
                            <td class="ui-table-num">{{ line.quantity }}</td>
                            <td class="ui-table-num">{{ formatMoney(line.unit_price) }}</td>
                            <td class="ui-table-num font-medium text-slate-900">
                                {{ formatMoney(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="ui-table-num text-slate-600">Subtotal</td>
                            <td class="ui-table-num font-medium">
                                {{ formatMoney(invoice.subtotal) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="ui-table-num text-slate-600">Total</td>
                            <td class="ui-table-num text-base font-bold text-slate-900">
                                {{ formatMoney(invoice.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import UiAlert from '@/components/UiAlert.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
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
