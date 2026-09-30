<template>
    <div class="ui-page">
        <UiPageHeader :title="isEdit ? 'Edit Customer' : 'Create Customer'" />

        <UiAlert v-if="loadError">{{ loadError }}</UiAlert>

        <form v-else class="ui-form" @submit.prevent="handleSubmit">
            <UiAlert v-if="error">{{ error }}</UiAlert>

            <div>
                <label for="name" class="ui-label">Name</label>
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
                <label for="email" class="ui-label">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    maxlength="255"
                    class="ui-input"
                />
            </div>

            <div>
                <label for="external_reference" class="ui-label">
                    External Reference
                    <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input
                    id="external_reference"
                    v-model="form.external_reference"
                    type="text"
                    maxlength="255"
                    class="ui-input"
                    placeholder="e.g. CRM-10001"
                />
            </div>

            <div class="ui-form-actions">
                <button type="submit" class="ui-btn-primary" :disabled="submitting">
                    {{ submitting ? 'Saving…' : isEdit ? 'Update Customer' : 'Create Customer' }}
                </button>
                <router-link :to="{ name: 'customers.index' }" class="ui-link-muted">
                    Cancel
                </router-link>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import UiAlert from '@/components/UiAlert.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import * as customersApi from '@/api/customers';
import type { CustomerFormData } from '@/types/customers';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const error = ref<string | null>(null);
const loadError = ref<string | null>(null);

const form = reactive<CustomerFormData>({
    name: '',
    email: '',
    external_reference: '',
});

onMounted(async () => {
    if (isEdit.value) {
        try {
            const customer = await customersApi.getCustomer(route.params.id as string);
            form.name = customer.name;
            form.email = customer.email;
            form.external_reference = customer.external_reference ?? '';
        } catch {
            loadError.value = 'Failed to load customer.';
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
        email: form.email,
        external_reference: form.external_reference || null,
    };

    try {
        if (isEdit.value) {
            await customersApi.updateCustomer(route.params.id as string, payload);
        } else {
            await customersApi.createCustomer(payload);
        }
        router.push({ name: 'customers.index' });
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
