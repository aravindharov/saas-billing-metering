<template>
    <div class="ui-page">
        <UiPageHeader
            title="Record Usage"
            description="Submit a usage event for an active customer subscription."
        >
            <template #actions>
                <router-link :to="{ name: 'usage.daily' }" class="ui-link-muted">
                    ← Daily totals
                </router-link>
            </template>
        </UiPageHeader>

        <p class="max-w-xl text-sm text-slate-600">
            Each event needs a unique <code>event_id</code> (retries with the same id are ignored).
        </p>

        <form class="ui-form" @submit.prevent="handleSubmit">
            <UiAlert v-if="error">{{ error }}</UiAlert>
            <UiAlert v-if="success" variant="success">{{ success }}</UiAlert>

            <div>
                <label for="customer_id" class="ui-label">Customer</label>
                <select
                    id="customer_id"
                    v-model="form.customer_id"
                    required
                    class="ui-select"
                    @change="onCustomerChange"
                >
                    <option value="">Select a customer…</option>
                    <option v-for="c in customers" :key="c.id" :value="c.id">
                        {{ c.name }} ({{ c.email }})
                    </option>
                </select>
            </div>

            <div>
                <label for="subscription_id" class="ui-label">Subscription</label>
                <select
                    id="subscription_id"
                    v-model="form.subscription_id"
                    required
                    :disabled="!form.customer_id || loadingSubscriptions"
                    class="ui-select"
                >
                    <option value="">
                        {{
                            loadingSubscriptions
                                ? 'Loading…'
                                : form.customer_id
                                  ? 'Select a subscription…'
                                  : 'Choose a customer first'
                        }}
                    </option>
                    <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                        {{ s.plan?.name ?? 'Plan' }} — {{ s.status }}
                    </option>
                </select>
            </div>

            <div>
                <label for="event_id" class="ui-label">Event ID</label>
                <div class="flex gap-2">
                    <input
                        id="event_id"
                        v-model="form.event_id"
                        type="text"
                        required
                        maxlength="255"
                        class="ui-input min-w-0 flex-1"
                    />
                    <button
                        type="button"
                        class="ui-btn-secondary shrink-0"
                        @click="regenerateEventId"
                    >
                        New ID
                    </button>
                </div>
            </div>

            <div>
                <label for="quantity" class="ui-label">Quantity (units)</label>
                <input
                    id="quantity"
                    v-model.number="form.quantity"
                    type="number"
                    min="1"
                    max="1000000"
                    required
                    class="ui-input"
                />
            </div>

            <div>
                <label for="occurred_at" class="ui-label">Occurred at (UTC)</label>
                <input
                    id="occurred_at"
                    v-model="form.occurred_at"
                    type="datetime-local"
                    required
                    class="ui-input"
                />
            </div>

            <div class="ui-form-actions">
                <button type="submit" class="ui-btn-primary" :disabled="submitting">
                    {{ submitting ? 'Submitting…' : 'Record usage' }}
                </button>
                <router-link :to="{ name: 'usage.daily' }" class="ui-link-muted">
                    Cancel
                </router-link>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import * as usageApi from '@/api/usage';
import * as customersApi from '@/api/customers';
import * as subscriptionsApi from '@/api/subscriptions';
import type { Customer } from '@/types/customers';
import type { SubscriptionData } from '@/types/subscriptions';

const customers = ref<Customer[]>([]);
const subscriptions = ref<SubscriptionData[]>([]);
const submitting = ref(false);
const loadingSubscriptions = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

const form = reactive({
    customer_id: '',
    subscription_id: '',
    event_id: '',
    quantity: 1,
    occurred_at: '',
});

function newEventId(): string {
    return `evt_ui_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
}

function regenerateEventId(): void {
    form.event_id = newEventId();
}

function defaultOccurredAt(): string {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
}

function toIsoUtc(localDatetime: string): string {
    const d = new Date(localDatetime);
    return d.toISOString();
}

function isAxiosError(
    e: unknown,
): e is { response: { status: number; data: Record<string, unknown> } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}

async function onCustomerChange(): Promise<void> {
    form.subscription_id = '';
    subscriptions.value = [];
    if (!form.customer_id) return;

    loadingSubscriptions.value = true;
    try {
        const res = await subscriptionsApi.getSubscriptions(1, 'active', form.customer_id);
        subscriptions.value = res.data;
        if (res.data.length === 1) {
            form.subscription_id = res.data[0].id;
        }
    } catch {
        error.value = 'Failed to load subscriptions for this customer.';
    } finally {
        loadingSubscriptions.value = false;
    }
}

onMounted(async () => {
    form.event_id = newEventId();
    form.occurred_at = defaultOccurredAt();
    try {
        const res = await customersApi.getCustomers(1, 'active');
        customers.value = res.data;
    } catch {
        error.value = 'Failed to load customers.';
    }
});

async function handleSubmit(): Promise<void> {
    submitting.value = true;
    error.value = null;
    success.value = null;
    try {
        const result = await usageApi.recordUsage({
            event_id: form.event_id,
            customer_id: form.customer_id,
            subscription_id: form.subscription_id,
            quantity: form.quantity,
            occurred_at: toIsoUtc(form.occurred_at),
        });
        success.value = `Recorded ${result.quantity} units (event ${result.event_id}). Daily totals update after aggregation runs.`;
        regenerateEventId();
        form.quantity = 1;
        form.occurred_at = defaultOccurredAt();
    } catch (e: unknown) {
        if (isAxiosError(e) && e.response?.status === 422) {
            const data = e.response.data;
            const errors = data?.errors as Record<string, string[]> | undefined;
            error.value = errors
                ? Object.values(errors).flat().join(' ')
                : ((data?.message as string) ?? 'Validation failed.');
        } else if (isAxiosError(e) && e.response?.status === 429) {
            error.value = 'Rate limit exceeded. Try again in a minute.';
        } else {
            error.value = 'Failed to record usage.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>
