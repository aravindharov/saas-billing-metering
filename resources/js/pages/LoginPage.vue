<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-md">
            <h1 class="text-2xl font-bold text-center text-gray-900 mb-8">
                Subscription Billing &amp; Usage Metering
            </h1>

            <form
                class="bg-white shadow rounded-lg px-8 py-6 space-y-4"
                @submit.prevent="handleLogin"
            >
                <h2 class="text-lg font-semibold text-gray-800">Sign in</h2>

                <div
                    v-if="error"
                    class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"
                >
                    {{ error }}
                </div>

                <div>
                    <label for="merchant" class="block text-sm font-medium text-gray-700 mb-1">
                        Organization
                    </label>
                    <input
                        id="merchant"
                        v-model="form.merchant"
                        type="text"
                        required
                        autocomplete="organization"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g. acme"
                    />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="you@example.com"
                    />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ loading ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { login, loading, error } = useAuth();

const form = reactive({
    merchant: '',
    email: '',
    password: '',
});

async function handleLogin() {
    const success = await login(form);
    if (success) {
        router.push({ name: 'home' });
    }
}
</script>
