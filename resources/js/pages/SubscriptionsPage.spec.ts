import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import SubscriptionsPage from './SubscriptionsPage.vue';

vi.mock('@/api/subscriptions', () => ({
    getSubscriptions: vi.fn().mockResolvedValue({
        data: [
            {
                id: '01SUB',
                customer: { id: '01C', name: 'John Smith' },
                plan: { id: '01P', name: 'Starter' },
                status: 'active',
                billing_cycle: 'monthly',
                base_price: 9900,
                included_usage_units: 1000,
                overage_rate: 5,
                started_at: '2026-01-01T00:00:00Z',
                current_period_start: '2026-09-01T00:00:00Z',
                current_period_end: '2026-10-01T00:00:00Z',
                cancelled_at: null,
                plan_changes: [],
                created_at: '2026-01-01T00:00:00Z',
                updated_at: '2026-09-01T00:00:00Z',
            },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        links: { first: null, last: null, prev: null, next: null },
    }),
    cancelSubscription: vi.fn().mockResolvedValue({}),
}));

vi.mock('@/composables/useAuth', () => ({
    useAuth: () => ({
        isOwner: { value: true },
        user: { value: { id: '1', name: 'Test', email: 'test@test.com', role: 'owner' } },
        merchant: { value: { id: '1', name: 'Acme', slug: 'acme' } },
        isAuthenticated: { value: true },
    }),
}));

describe('SubscriptionsPage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                {
                    path: '/subscriptions',
                    name: 'subscriptions.index',
                    component: SubscriptionsPage,
                },
                {
                    path: '/subscriptions/create',
                    name: 'subscriptions.create',
                    component: { template: '<div />' },
                },
                {
                    path: '/subscriptions/:id',
                    name: 'subscriptions.show',
                    component: { template: '<div />' },
                },
            ],
        });
    });

    it('renders subscriptions table after loading', async () => {
        const wrapper = mount(SubscriptionsPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('John Smith');
        expect(wrapper.text()).toContain('Starter');
        expect(wrapper.text()).toContain('active');
        expect(wrapper.text()).toContain('₹99.00');
    });

    it('shows Create Subscription link for owners', async () => {
        const wrapper = mount(SubscriptionsPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Create Subscription');
    });

    it('shows status filter', async () => {
        const wrapper = mount(SubscriptionsPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.find('select').exists()).toBe(true);
    });
});
