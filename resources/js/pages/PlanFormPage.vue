<template>
    <div class="ui-page">
        <UiPageHeader :title="isEdit ? 'Edit Plan' : 'Create Plan'" />

        <UiAlert v-if="loadError">{{ loadError }}</UiAlert>

        <form v-else class="ui-form" @submit.prevent="handleSubmit">
            <UiAlert v-if="error">{{ error }}</UiAlert>

            <div>
                <label for="name" class="ui-label">Plan Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    maxlength="255"
                    class="ui-input"
                />
            </div>

            <div>
                <label for="base_price" class="ui-label">Base Price (paise)</label>
                <input
                    id="base_price"
                    v-model.number="form.base_price"
                    type="number"
                    required
                    min="0"
                    class="ui-input"
                />
                <p class="ui-hint">Integer minor units. E.g. ₹499.00 = 49900</p>
            </div>

            <div>
                <label for="billing_cycle" class="ui-label">Billing Cycle</label>
                <select id="billing_cycle" v-model="form.billing_cycle" required class="ui-select">
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div>
                <label for="included_usage_units" class="ui-label">Included Usage Units</label>
                <input
                    id="included_usage_units"
                    v-model.number="form.included_usage_units"
                    type="number"
                    required
                    min="0"
                    class="ui-input"
                />
            </div>

            <div>
                <label for="overage_rate" class="ui-label">Overage Rate (paise per unit)</label>
                <input
                    id="overage_rate"
                    v-model.number="form.overage_rate"
                    type="number"
                    required
                    min="0"
                    class="ui-input"
                />
            </div>

            <div class="ui-form-actions">
                <button type="submit" class="ui-btn-primary" :disabled="submitting">
                    {{ submitting ? 'Saving…' : isEdit ? 'Update Plan' : 'Create Plan' }}
                </button>
                <router-link :to="{ name: 'plans.index' }" class="ui-link-muted"
                    >Cancel</router-link
                >
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import UiAlert from '@/components/UiAlert.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
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
