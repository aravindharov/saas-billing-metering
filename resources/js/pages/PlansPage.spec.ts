import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import PlansPage from './PlansPage.vue';

vi.mock('@/api/plans', () => ({
    getPlans: vi.fn().mockResolvedValue({
        data: [
            {
                id: '01ABC',
                name: 'Starter',
                base_price: 9900,
                billing_cycle: 'monthly',
                included_usage_units: 1000,
                overage_rate: 5,
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
                updated_at: '2026-01-01T00:00:00Z',
            },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        links: { first: null, last: null, prev: null, next: null },
    }),
    archivePlan: vi.fn().mockResolvedValue({}),
}));

vi.mock('@/composables/useAuth', () => ({
    useAuth: () => ({
        isOwner: { value: true },
        user: { value: { id: '1', name: 'Test', email: 'test@test.com', role: 'owner' } },
        merchant: { value: { id: '1', name: 'Acme', slug: 'acme' } },
        isAuthenticated: { value: true },
    }),
}));

describe('PlansPage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/plans', name: 'plans.index', component: PlansPage },
                { path: '/plans/create', name: 'plans.create', component: { template: '<div />' } },
                {
                    path: '/plans/:id/edit',
                    name: 'plans.edit',
                    component: { template: '<div />' },
                },
            ],
        });
    });

    it('renders plans table after loading', async () => {
        const wrapper = mount(PlansPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Starter');
        expect(wrapper.text()).toContain('₹99.00');
        expect(wrapper.text()).toContain('monthly');
    });

    it('shows Create Plan link for owners', async () => {
        const wrapper = mount(PlansPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Create Plan');
    });

    it('shows status filter dropdown', async () => {
        const wrapper = mount(PlansPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        const select = wrapper.find('select');
        expect(select.exists()).toBe(true);
    });
});
