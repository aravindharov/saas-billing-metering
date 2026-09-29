import { ref, computed, readonly } from 'vue';
import type { AuthUser, AuthMerchant, LoginPayload } from '@/types/auth';
import * as authApi from '@/api/auth';

const user = ref<AuthUser | null>(null);
const merchant = ref<AuthMerchant | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const isAuthenticated = computed(() => user.value !== null);
const isOwner = computed(() => user.value?.role === 'owner');

async function login(payload: LoginPayload): Promise<boolean> {
    loading.value = true;
    error.value = null;

    try {
        const response = await authApi.login(payload);
        localStorage.setItem('auth_token', response.token);
        user.value = response.user;
        merchant.value = response.merchant;
        return true;
    } catch (e: unknown) {
        if (isAxiosError(e) && e.response?.status === 401) {
            error.value = 'The provided credentials are incorrect.';
        } else if (isAxiosError(e) && e.response?.status === 422) {
            const data = e.response.data;
            const errors = data?.errors as Record<string, string[]> | undefined;
            if (errors) {
                error.value = Object.values(errors).flat().join(' ');
            } else {
                error.value = (data?.message as string) ?? 'Validation failed.';
            }
        } else {
            error.value = 'An unexpected error occurred. Please try again.';
        }
        return false;
    } finally {
        loading.value = false;
    }
}

async function fetchMe(): Promise<boolean> {
    const token = localStorage.getItem('auth_token');
    if (!token) return false;

    loading.value = true;
    try {
        const response = await authApi.getMe();
        user.value = response.user;
        merchant.value = response.merchant;
        return true;
    } catch {
        clearState();
        return false;
    } finally {
        loading.value = false;
    }
}

async function logout(): Promise<void> {
    try {
        await authApi.logout();
    } finally {
        clearState();
    }
}

function clearState(): void {
    user.value = null;
    merchant.value = null;
    error.value = null;
    localStorage.removeItem('auth_token');
}

function isAxiosError(
    e: unknown,
): e is { response: { status: number; data: Record<string, unknown> } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}

export function useAuth() {
    return {
        user: readonly(user),
        merchant: readonly(merchant),
        loading: readonly(loading),
        error: readonly(error),
        isAuthenticated,
        isOwner,
        login,
        logout,
        fetchMe,
    };
}
