<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            {{ isEdit ? 'Edit Customer' : 'Create Customer' }}
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
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
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
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1"
                    >Email</label
                >
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    maxlength="255"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <div>
                <label
                    for="external_reference"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    External Reference
                    <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input
                    id="external_reference"
                    v-model="form.external_reference"
                    type="text"
                    maxlength="255"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g. CRM-10001"
                />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="submitting"
                    class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ submitting ? 'Saving…' : isEdit ? 'Update Customer' : 'Create Customer' }}
                </button>
                <router-link
                    :to="{ name: 'customers.index' }"
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
