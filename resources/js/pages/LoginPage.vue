<template>
    <div class="ui-login-shell">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Subscription Billing &amp; Usage Metering
                </h1>
                <p class="ui-subtitle mt-2">Sign in to your merchant workspace</p>
            </div>

            <form class="ui-login-card space-y-5" @submit.prevent="handleLogin">
                <h2 class="text-lg font-semibold text-slate-900">Sign in</h2>

                <UiAlert v-if="error">{{ error }}</UiAlert>

                <div>
                    <label for="merchant" class="ui-label">Organization</label>
                    <input
                        id="merchant"
                        v-model="form.merchant"
                        type="text"
                        required
                        autocomplete="organization"
                        class="ui-input"
                        placeholder="e.g. acme"
                    />
                </div>

                <div>
                    <label for="email" class="ui-label">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="ui-input"
                        placeholder="you@example.com"
                    />
                </div>

                <div>
                    <label for="password" class="ui-label">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="ui-input"
                    />
                </div>

                <button type="submit" class="ui-btn-primary w-full" :disabled="loading">
                    {{ loading ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import UiAlert from '@/components/UiAlert.vue';
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
