<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Dashboard</h2>
        <div v-if="merchant" class="bg-white shadow rounded-lg p-6 space-y-2">
            <p class="text-gray-700">
                <span class="font-medium">Merchant:</span> {{ merchant.name }}
            </p>
            <p class="text-gray-700">
                <span class="font-medium">Signed in as:</span> {{ user?.name }} ({{ user?.email }})
            </p>
            <p class="text-gray-700"><span class="font-medium">Role:</span> {{ user?.role }}</p>
        </div>
        <p class="text-gray-500 mt-4 text-sm">
            Business modules will be implemented in upcoming phases.
        </p>
    </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/composables/useAuth';

const { user, merchant } = useAuth();
</script>
