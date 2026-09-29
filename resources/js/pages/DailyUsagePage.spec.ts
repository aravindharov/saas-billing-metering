import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import DailyUsagePage from './DailyUsagePage.vue';

vi.mock('@/api/usage', () => ({
    getDailyUsage: vi.fn().mockResolvedValue({
        data: [
            {
                customer: { id: '01C', name: 'John Smith' },
                usage_date: '2026-09-29',
                total_quantity: 600,
            },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 50, total: 1 },
        links: { first: null, last: null, prev: null, next: null },
    }),
}));

describe('DailyUsagePage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/usage/daily', name: 'usage.daily', component: DailyUsagePage },
                {
                    path: '/usage/record',
                    name: 'usage.record',
                    component: { template: '<div />' },
                },
            ],
        });
    });

    it('renders daily usage table after loading', async () => {
        await router.push('/usage/daily');
        await router.isReady();

        const wrapper = mount(DailyUsagePage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('John Smith');
        expect(wrapper.text()).toContain('600');
        expect(wrapper.text()).toContain('2026-09-29');
    });

    it('shows date filter input', async () => {
        await router.push('/usage/daily');
        await router.isReady();

        const wrapper = mount(DailyUsagePage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.find('input[type="date"]').exists()).toBe(true);
    });
});
