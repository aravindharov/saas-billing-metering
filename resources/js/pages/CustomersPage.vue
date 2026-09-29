<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Customers</h2>
            <router-link
                v-if="isOwner"
                :to="{ name: 'customers.create' }"
                class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700"
            >
                Create Customer
            </router-link>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by name, email, or reference…"
                class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                @input="debouncedSearch"
            />
            <select
                v-model="statusFilter"
                class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="loadCustomers(1)"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div
            v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm mb-4"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-gray-500 text-sm">Loading customers…</div>

        <div v-else-if="customers.length === 0" class="text-gray-500 text-sm">
            No customers found.
        </div>

        <div v-else>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Email
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Reference
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Created
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="customer in customers" :key="customer.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ customer.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ customer.email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ customer.external_reference ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="
                                        customer.status === 'active'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    "
                                >
                                    {{ customer.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ new Date(customer.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <router-link
                                    :to="{ name: 'customers.edit', params: { id: customer.id } }"
                                    class="text-blue-600 hover:text-blue-800"
                                    :class="{ 'pointer-events-none opacity-50': !isOwner }"
                                >
                                    Edit
                                </router-link>
                                <button
                                    v-if="isOwner && customer.status === 'active'"
                                    class="text-red-600 hover:text-red-800"
                                    @click="confirmDeactivate(customer)"
                                >
                                    Deactivate
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="meta && meta.last_page > 1"
                class="flex items-center justify-between mt-4 text-sm text-gray-600"
            >
                <span
                    >Page {{ meta.current_page }} of {{ meta.last_page }} ({{
                        meta.total
                    }}
                    customers)</span
                >
                <div class="space-x-2">
                    <button
                        :disabled="meta.current_page <= 1"
                        class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100"
                        @click="loadCustomers(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100"
                        @click="loadCustomers(meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
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
