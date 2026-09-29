import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import HomePage from './HomePage.vue';

describe('HomePage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/', name: 'home', component: HomePage },
                { path: '/usage/daily', name: 'usage.daily', component: { template: '<div />' } },
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

    it('renders the dashboard heading', async () => {
        await router.push('/');
        await router.isReady();

        const wrapper = mount(HomePage, {
            global: { plugins: [router] },
        });
        expect(wrapper.text()).toContain('Dashboard');
    });

    it('shows quick links to main modules', async () => {
        await router.push('/');
        await router.isReady();

        const wrapper = mount(HomePage, {
            global: { plugins: [router] },
        });
        expect(wrapper.text()).toContain('Usage');
        expect(wrapper.text()).toContain('Subscriptions');
        expect(wrapper.text()).toContain('Customers');
    });
});
