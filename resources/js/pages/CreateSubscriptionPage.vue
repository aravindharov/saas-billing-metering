<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Subscription</h2>

        <form
            class="bg-white shadow rounded-lg px-8 py-6 space-y-4 max-w-lg"
            @submit.prevent="handleSubmit"
        >
            <div
                v-if="error"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"
            >
                {{ error }}
            </div>

            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1"
                    >Customer</label
                >
                <select
                    id="customer_id"
                    v-model="form.customer_id"
                    required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Select a customer…</option>
                    <option v-for="c in customers" :key="c.id" :value="c.id">
                        {{ c.name }} ({{ c.email }})
                    </option>
                </select>
            </div>

            <div>
                <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-1"
                    >Plan</label
                >
                <select
                    id="plan_id"
                    v-model="form.plan_id"
                    required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Select a plan…</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">
                        {{ p.name }} — {{ formatMoney(p.base_price) }}/{{ p.billing_cycle }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="submitting"
                    class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ submitting ? 'Creating…' : 'Create Subscription' }}
                </button>
                <router-link
                    :to="{ name: 'subscriptions.index' }"
                    class="text-gray-600 hover:text-gray-800 text-sm"
                >
                    Cancel
                </router-link>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
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
