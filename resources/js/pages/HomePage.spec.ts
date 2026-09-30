import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import HomePage from './HomePage.vue';
import * as dashboardApi from '@/api/dashboard';

vi.mock('@/composables/useAuth', () => ({
    useAuth: () => ({
        merchant: { value: { id: 'merchant-ulid', name: 'Acme', slug: 'acme' } },
        user: { value: { name: 'Owner', email: 'o@test.com', role: 'owner' } },
    }),
}));

vi.mock('@/api/dashboard', () => ({
    getMerchantDashboard: vi.fn(),
}));

describe('HomePage', () => {
    let router: ReturnType<typeof createRouter>;

    const sampleDashboard = {
        generated_at: '2026-06-01T00:00:00Z',
        period: { month_start: '2026-06-01', month_end: '2026-06-30' },
        summary: {
            current_month_usage_units: 12_500,
            active_customers: 3,
            active_subscriptions: 2,
        },
        billing_cycle: {
            period_start: '2026-06-01T00:00:00Z',
            period_end: '2026-07-01T00:00:00Z',
        },
        top_customers: [
            {
                customer_id: 'c1',
                name: 'Acme Corp',
                email: 'a@acme.com',
                usage_units: 12_500,
            },
        ],
        projected_overage_revenue: { amount: 125_000, currency: 'INR', unit: 'paise' },
        usage_drops: [
            {
                customer_id: 'c2',
                name: 'Beta Ltd',
                current_usage_units: 400,
                previous_usage_units: 1000,
                percentage_change: -60,
            },
        ],
    };

    beforeEach(() => {
        vi.mocked(dashboardApi.getMerchantDashboard).mockResolvedValue(sampleDashboard);
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/', name: 'home', component: HomePage },
                { path: '/usage/daily', name: 'usage.daily', component: { template: '<div />' } },
                { path: '/invoices', name: 'invoices.index', component: { template: '<div />' } },
                {
                    path: '/subscriptions',
                    name: 'subscriptions.index',
                    component: { template: '<div />' },
                },
                {
                    path: '/customers',
                    name: 'customers.index',
                    component: { template: '<div />' },
                },
            ],
        });
    });

    it('renders dashboard heading and loading completes', async () => {
        await router.push('/');
        await router.isReady();

        const wrapper = mount(HomePage, { global: { plugins: [router] } });
        expect(wrapper.text()).toContain('Usage & Billing Dashboard');
        await flushPromises();
        expect(wrapper.text()).toContain('12,500');
        expect(wrapper.text()).toContain('Acme Corp');
    });

    it('shows projected overage and usage drops', async () => {
        await router.push('/');
        await router.isReady();

        const wrapper = mount(HomePage, { global: { plugins: [router] } });
        await flushPromises();
        expect(wrapper.text()).toContain('₹1250.00');
        expect(wrapper.text()).toContain('Beta Ltd');
        expect(wrapper.text()).toContain('-60%');
    });

    it('shows error when dashboard API fails', async () => {
        vi.mocked(dashboardApi.getMerchantDashboard).mockRejectedValue(new Error('fail'));
        await router.push('/');
        await router.isReady();

        const wrapper = mount(HomePage, { global: { plugins: [router] } });
        await flushPromises();
        expect(wrapper.text()).toContain('Failed to load dashboard analytics.');
    });

    it('shows empty state for top customers when list is empty', async () => {
        vi.mocked(dashboardApi.getMerchantDashboard).mockResolvedValue({
            ...sampleDashboard,
            top_customers: [],
            usage_drops: [],
        });
        await router.push('/');
        await router.isReady();

        const wrapper = mount(HomePage, { global: { plugins: [router] } });
        await flushPromises();
        expect(wrapper.text()).toContain('No usage recorded for the current month yet.');
    });
});
