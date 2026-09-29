import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import PlanFormPage from './PlanFormPage.vue';

vi.mock('@/api/plans', () => ({
    createPlan: vi.fn().mockResolvedValue({
        id: '01NEW',
        name: 'New Plan',
        base_price: 9900,
        billing_cycle: 'monthly',
        included_usage_units: 1000,
        overage_rate: 5,
        status: 'active',
    }),
    getPlan: vi.fn().mockResolvedValue({
        id: '01EDIT',
        name: 'Existing',
        base_price: 49900,
        billing_cycle: 'monthly',
        included_usage_units: 10000,
        overage_rate: 3,
        status: 'active',
    }),
    updatePlan: vi.fn().mockResolvedValue({}),
}));

vi.mock('@/composables/useAuth', () => ({
    useAuth: () => ({
        isOwner: { value: true },
        user: { value: { id: '1', name: 'Test', email: 'test@test.com', role: 'owner' } },
        merchant: { value: { id: '1', name: 'Acme', slug: 'acme' } },
        isAuthenticated: { value: true },
    }),
}));

describe('PlanFormPage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/plans', name: 'plans.index', component: { template: '<div />' } },
                {
                    path: '/plans/create',
                    name: 'plans.create',
                    component: PlanFormPage,
                },
                {
                    path: '/plans/:id/edit',
                    name: 'plans.edit',
                    component: PlanFormPage,
                },
            ],
        });
    });

    it('renders create form', async () => {
        await router.push('/plans/create');
        await router.isReady();

        const wrapper = mount(PlanFormPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Create Plan');
        expect(wrapper.find('input#name').exists()).toBe(true);
        expect(wrapper.find('input#base_price').exists()).toBe(true);
        expect(wrapper.find('select#billing_cycle').exists()).toBe(true);
    });

    it('renders edit form with preloaded data', async () => {
        await router.push('/plans/01EDIT/edit');
        await router.isReady();

        const wrapper = mount(PlanFormPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Edit Plan');
    });
});
