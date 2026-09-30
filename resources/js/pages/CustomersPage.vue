<template>
    <div class="ui-page">
        <UiPageHeader title="Customers">
            <template #actions>
                <router-link
                    v-if="isOwner"
                    :to="{ name: 'customers.create' }"
                    class="ui-btn-primary"
                >
                    Create Customer
                </router-link>
            </template>
        </UiPageHeader>

        <div class="ui-toolbar">
            <input
                v-model="searchQuery"
                type="search"
                placeholder="Search by name, email, or reference…"
                class="ui-input min-w-[12rem] flex-1"
                @input="debouncedSearch"
            />
            <select
                v-model="statusFilter"
                class="ui-select w-auto min-w-[10rem]"
                @change="loadCustomers(1)"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <UiAlert v-if="error">{{ error }}</UiAlert>
        <UiLoading v-if="loading" message="Loading customers…" />
        <UiEmpty
            v-else-if="customers.length === 0"
            title="No customers found"
            message="No customers found."
        />

        <template v-else>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in customers" :key="customer.id">
                            <td class="ui-table-primary">{{ customer.name }}</td>
                            <td>{{ customer.email }}</td>
                            <td class="text-slate-500">
                                {{ customer.external_reference ?? '—' }}
                            </td>
                            <td>
                                <UiBadge
                                    :variant="customer.status === 'active' ? 'success' : 'neutral'"
                                >
                                    {{ customer.status }}
                                </UiBadge>
                            </td>
                            <td class="text-slate-500">
                                {{ new Date(customer.created_at).toLocaleDateString() }}
                            </td>
                            <td class="space-x-3 whitespace-nowrap">
                                <router-link
                                    :to="{ name: 'customers.edit', params: { id: customer.id } }"
                                    class="ui-link"
                                    :class="{ 'pointer-events-none opacity-50': !isOwner }"
                                >
                                    Edit
                                </router-link>
                                <button
                                    v-if="isOwner && customer.status === 'active'"
                                    type="button"
                                    class="ui-btn-danger px-0 py-0"
                                    @click="confirmDeactivate(customer)"
                                >
                                    Deactivate
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UiPagination :meta="meta" label="customers" @change="loadCustomers" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiBadge from '@/components/UiBadge.vue';
import UiEmpty from '@/components/UiEmpty.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import UiPagination from '@/components/UiPagination.vue';
import { useAuth } from '@/composables/useAuth';
import * as customersApi from '@/api/customers';
import type { Customer } from '@/types/customers';
import type { PaginatedResponse } from '@/types/plans';

const { isOwner } = useAuth();
const customers = ref<Customer[]>([]);
const meta = ref<PaginatedResponse<Customer>['meta'] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const statusFilter = ref('');
const searchQuery = ref('');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function debouncedSearch() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadCustomers(1), 300);
}

async function loadCustomers(page = 1) {
    loading.value = true;
    error.value = null;
    try {
        const response = await customersApi.getCustomers(
            page,
            statusFilter.value || undefined,
            searchQuery.value || undefined,
        );
        customers.value = response.data;
        meta.value = response.meta;
    } catch {
        error.value = 'Failed to load customers.';
    } finally {
        loading.value = false;
    }
}

async function confirmDeactivate(customer: Customer) {
    if (!confirm(`Deactivate customer "${customer.name}"? They will no longer be active.`)) {
        return;
    }
    try {
        await customersApi.deactivateCustomer(customer.id);
        await loadCustomers(meta.value?.current_page ?? 1);
    } catch {
        error.value = 'Failed to deactivate customer.';
    }
}

onMounted(() => loadCustomers());
</script>
