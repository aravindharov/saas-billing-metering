import { describe, it, expect, beforeEach } from 'vitest';
import { useAuth } from './useAuth';

describe('useAuth', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('starts unauthenticated', () => {
        const { isAuthenticated, user, merchant } = useAuth();

        expect(isAuthenticated.value).toBe(false);
        expect(user.value).toBeNull();
        expect(merchant.value).toBeNull();
    });

    it('exposes loading state', () => {
        const { loading } = useAuth();
        expect(loading.value).toBe(false);
    });
});
