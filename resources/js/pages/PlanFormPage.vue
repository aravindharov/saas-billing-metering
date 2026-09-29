<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            {{ isEdit ? 'Edit Plan' : 'Create Plan' }}
        </h2>

        <div
            v-if="loadError"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm mb-4"
        >
            {{ loadError }}
        </div>

        <form
            v-else
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
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1"
                    >Plan Name</label
                >
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    maxlength="255"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <div>
                <label for="base_price" class="block text-sm font-medium text-gray-700 mb-1">
                    Base Price (paise)
                </label>
                <input
                    id="base_price"
                    v-model.number="form.base_price"
                    type="number"
                    required
                    min="0"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p class="text-xs text-gray-500 mt-1">Integer minor units. E.g. ₹499.00 = 49900</p>
            </div>

            <div>
                <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">
                    Billing Cycle
                </label>
                <select
                    id="billing_cycle"
                    v-model="form.billing_cycle"
                    required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div>
                <label
                    for="included_usage_units"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Included Usage Units
                </label>
                <input
                    id="included_usage_units"
                    v-model.number="form.included_usage_units"
                    type="number"
                    required
                    min="0"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <div>
                <label for="overage_rate" class="block text-sm font-medium text-gray-700 mb-1">
                    Overage Rate (paise per unit)
                </label>
                <input
                    id="overage_rate"
                    v-model.number="form.overage_rate"
                    type="number"
                    required
                    min="0"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="submitting"
                    class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ submitting ? 'Saving…' : isEdit ? 'Update Plan' : 'Create Plan' }}
                </button>
                <router-link
                    :to="{ name: 'plans.index' }"
                    class="text-gray-600 hover:text-gray-800 text-sm"
                >
                    Cancel
                </router-link>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as plansApi from '@/api/plans';
import type { PlanFormData } from '@/types/plans';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const error = ref<string | null>(null);
const loadError = ref<string | null>(null);

const form = reactive<PlanFormData>({
    name: '',
    base_price: '',
    billing_cycle: 'monthly',
    included_usage_units: '',
    overage_rate: '',
});

onMounted(async () => {
    if (isEdit.value) {
        try {
            const plan = await plansApi.getPlan(route.params.id as string);
            form.name = plan.name;
            form.base_price = plan.base_price;
            form.billing_cycle = plan.billing_cycle;
            form.included_usage_units = plan.included_usage_units;
            form.overage_rate = plan.overage_rate;
        } catch {
            loadError.value = 'Failed to load plan.';
        }
    }
});

function isAxiosError(
    e: unknown,
): e is { response: { status: number; data: Record<string, unknown> } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}

async function handleSubmit() {
    submitting.value = true;
    error.value = null;

    const payload = {
        name: form.name,
        base_price: Number(form.base_price),
        billing_cycle: form.billing_cycle,
        included_usage_units: Number(form.included_usage_units),
        overage_rate: Number(form.overage_rate),
    };

    try {
        if (isEdit.value) {
            await plansApi.updatePlan(route.params.id as string, payload);
        } else {
            await plansApi.createPlan(payload);
        }
        router.push({ name: 'plans.index' });
    } catch (e: unknown) {
        if (isAxiosError(e) && e.response?.status === 422) {
            const data = e.response.data;
            const errors = data?.errors as Record<string, string[]> | undefined;
            if (errors) {
                error.value = Object.values(errors).flat().join(' ');
            } else {
                error.value = (data?.message as string) ?? 'Validation failed.';
            }
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
