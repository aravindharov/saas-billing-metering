<template>
    <div class="ui-page">
        <UiPageHeader title="Subscription Detail">
            <template #actions>
                <router-link :to="{ name: 'subscriptions.index' }" class="ui-link-muted">
                    ← Back to list
                </router-link>
            </template>
        </UiPageHeader>

        <UiAlert v-if="error">{{ error }}</UiAlert>
        <UiLoading v-if="loading" />

        <div v-else-if="sub" class="space-y-6">
            <section class="ui-card ui-card-body">
                <dl class="ui-dl-grid">
                    <div class="ui-dl-item">
                        <dt>Customer</dt>
                        <dd>{{ sub.customer?.name }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Plan</dt>
                        <dd>{{ sub.plan?.name }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Status</dt>
                        <dd>
                            <UiBadge :variant="sub.status === 'active' ? 'success' : 'neutral'">
                                {{ sub.status }}
                            </UiBadge>
                        </dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Billing cycle</dt>
                        <dd class="capitalize">{{ sub.billing_cycle }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Base price</dt>
                        <dd>{{ formatMoney(sub.base_price) }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Included units</dt>
                        <dd>{{ sub.included_usage_units.toLocaleString() }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Overage rate</dt>
                        <dd>{{ formatMoney(sub.overage_rate) }}/unit</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Started</dt>
                        <dd>{{ formatDate(sub.started_at) }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Current period</dt>
                        <dd>
                            {{ formatDate(sub.current_period_start) }} —
                            {{ formatDate(sub.current_period_end) }}
                        </dd>
                    </div>
                    <div v-if="sub.cancelled_at" class="ui-dl-item">
                        <dt>Cancelled</dt>
                        <dd>{{ formatDate(sub.cancelled_at) }}</dd>
                    </div>
                </dl>
            </section>

            <div v-if="isOwner" class="flex flex-wrap items-center gap-3">
                <button
                    v-if="billingPeriodEnded"
                    type="button"
                    class="ui-btn-success"
                    :disabled="generatingInvoice"
                    @click="handleGenerateInvoice"
                >
                    {{ generatingInvoice ? 'Generating…' : 'Generate invoice' }}
                </button>
                <p v-else class="text-sm text-slate-500">
                    Invoice generation is available after the current billing period ends ({{
                        formatDate(sub.current_period_end)
                    }}).
                </p>
                <template v-if="sub.status === 'active'">
                    <button
                        type="button"
                        class="ui-btn-primary"
                        @click="showChangePlan = !showChangePlan"
                    >
                        Change Plan
                    </button>
                    <button
                        type="button"
                        class="ui-btn-secondary text-red-600 hover:bg-red-50"
                        @click="handleCancel"
                    >
                        Cancel Subscription
                    </button>
                </template>
            </div>

            <UiAlert v-if="invoiceSuccessId" variant="success">
                Invoice created.
                <router-link
                    :to="{ name: 'invoices.show', params: { id: invoiceSuccessId } }"
                    class="font-semibold underline"
                >
                    View invoice
                </router-link>
            </UiAlert>

            <section v-if="showChangePlan" class="ui-card ui-card-body max-w-md">
                <h3 class="ui-card-title mb-4">Change Plan</h3>
                <select v-model="targetPlanId" class="ui-select mb-4">
                    <option value="">Select new plan…</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">
                        {{ p.name }} — {{ formatMoney(p.base_price) }}/{{ p.billing_cycle }}
                    </option>
                </select>
                <button
                    type="button"
                    class="ui-btn-primary"
                    :disabled="!targetPlanId || changing"
                    @click="handleChangePlan"
                >
                    {{ changing ? 'Changing…' : 'Confirm Change' }}
                </button>
            </section>

            <section
                v-if="sub.plan_changes && sub.plan_changes.length > 0"
                class="ui-card ui-card-body-flush"
            >
                <div class="ui-card-header">
                    <h3 class="ui-card-title">Plan Change History</h3>
                </div>
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th class="ui-table-num">Old Price</th>
                            <th class="ui-table-num">New Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ch in sub.plan_changes" :key="ch.id">
                            <td>{{ formatDate(ch.effective_at) }}</td>
                            <td>{{ ch.from_plan?.name ?? '—' }}</td>
                            <td>{{ ch.to_plan?.name ?? '—' }}</td>
                            <td class="ui-table-num">{{ formatMoney(ch.from_base_price) }}</td>
                            <td class="ui-table-num">{{ formatMoney(ch.to_base_price) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import UiAlert from '@/components/UiAlert.vue';
import UiBadge from '@/components/UiBadge.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import { useAuth } from '@/composables/useAuth';
import * as subsApi from '@/api/subscriptions';
import * as plansApi from '@/api/plans';
import type { SubscriptionData } from '@/types/subscriptions';
import type { Plan } from '@/types/plans';

const route = useRoute();
const { isOwner } = useAuth();

const sub = ref<SubscriptionData | null>(null);
const plans = ref<Plan[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const showChangePlan = ref(false);
const targetPlanId = ref('');
const changing = ref(false);
const generatingInvoice = ref(false);
const invoiceSuccessId = ref<string | null>(null);

const billingPeriodEnded = computed(() => {
    if (!sub.value?.current_period_end) return false;
    return new Date(sub.value.current_period_end).getTime() <= Date.now();
});

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}
function formatDate(iso: string): string {
    return new Date(iso).toLocaleString();
}

async function loadSubscription() {
    loading.value = true;
    try {
        sub.value = await subsApi.getSubscription(route.params.id as string);
        const pRes = await plansApi.getPlans(1, 'active');
        plans.value = pRes.data.filter((p) => p.id !== sub.value?.plan?.id);
    } catch {
        error.value = 'Failed to load subscription.';
    } finally {
        loading.value = false;
    }
}

async function handleChangePlan() {
    if (!targetPlanId.value || !sub.value) return;
    changing.value = true;
    error.value = null;
    try {
        await subsApi.changePlan(sub.value.id, targetPlanId.value);
        showChangePlan.value = false;
        targetPlanId.value = '';
        await loadSubscription();
    } catch {
        error.value = 'Failed to change plan.';
    } finally {
        changing.value = false;
    }
}

async function handleCancel() {
    if (!sub.value || !confirm('Cancel this subscription?')) return;
    try {
        await subsApi.cancelSubscription(sub.value.id);
        await loadSubscription();
    } catch {
        error.value = 'Failed to cancel subscription.';
    }
}

async function handleGenerateInvoice() {
    if (!sub.value || !confirm('Generate invoice for the current billing period?')) return;
    generatingInvoice.value = true;
    error.value = null;
    invoiceSuccessId.value = null;
    try {
        const invoice = await subsApi.generateSubscriptionInvoice(sub.value.id);
        invoiceSuccessId.value = invoice.id;
    } catch (e) {
        if (axios.isAxiosError(e) && e.response?.status === 422) {
            const errors = e.response.data?.errors as Record<string, string[]> | undefined;
            error.value =
                errors?.subscription?.[0] ?? 'Cannot generate invoice for this subscription.';
        } else {
            error.value = 'Failed to generate invoice.';
        }
    } finally {
        generatingInvoice.value = false;
    }
}

onMounted(() => loadSubscription());
</script>
