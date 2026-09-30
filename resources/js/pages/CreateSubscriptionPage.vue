<template>
    <div class="ui-page">
        <UiPageHeader title="Create Subscription" />

        <form class="ui-form" @submit.prevent="handleSubmit">
            <UiAlert v-if="error">{{ error }}</UiAlert>

            <div>
                <label for="customer_id" class="ui-label">Customer</label>
                <select id="customer_id" v-model="form.customer_id" required class="ui-select">
                    <option value="">Select a customer…</option>
                    <option v-for="c in customers" :key="c.id" :value="c.id">
                        {{ c.name }} ({{ c.email }})
                    </option>
                </select>
            </div>

            <div>
                <label for="plan_id" class="ui-label">Plan</label>
                <select id="plan_id" v-model="form.plan_id" required class="ui-select">
                    <option value="">Select a plan…</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">
                        {{ p.name }} — {{ formatMoney(p.base_price) }}/{{ p.billing_cycle }}
                    </option>
                </select>
            </div>

            <div class="ui-form-actions">
                <button type="submit" class="ui-btn-primary" :disabled="submitting">
                    {{ submitting ? 'Creating…' : 'Create Subscription' }}
                </button>
                <router-link :to="{ name: 'subscriptions.index' }" class="ui-link-muted">
                    Cancel
                </router-link>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import UiAlert from '@/components/UiAlert.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import * as subsApi from '@/api/subscriptions';
import * as customersApi from '@/api/customers';
import * as plansApi from '@/api/plans';
import type { Customer } from '@/types/customers';
import type { Plan } from '@/types/plans';

const router = useRouter();
const submitting = ref(false);
const error = ref<string | null>(null);
const customers = ref<Customer[]>([]);
const plans = ref<Plan[]>([]);

const form = reactive({ customer_id: '', plan_id: '' });

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}

function isAxiosError(
    e: unknown,
): e is { response: { status: number; data: Record<string, unknown> } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}

onMounted(async () => {
    try {
        const [cRes, pRes] = await Promise.all([
            customersApi.getCustomers(1, 'active'),
            plansApi.getPlans(1, 'active'),
        ]);
        customers.value = cRes.data;
        plans.value = pRes.data;
    } catch {
        error.value = 'Failed to load customers or plans.';
    }
});

async function handleSubmit() {
    submitting.value = true;
    error.value = null;
    try {
        await subsApi.createSubscription(form);
        router.push({ name: 'subscriptions.index' });
    } catch (e: unknown) {
        if (isAxiosError(e) && e.response?.status === 422) {
            const data = e.response.data;
            const errors = data?.errors as Record<string, string[]> | undefined;
            error.value = errors
                ? Object.values(errors).flat().join(' ')
                : ((data?.message as string) ?? 'Validation failed.');
        } else if (isAxiosError(e) && e.response?.status === 403) {
            error.value = 'You do not have permission to perform this action.';
        } else {
            error.value = 'An unexpected error occurred.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>
