import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import CustomerFormPage from './CustomerFormPage.vue';

vi.mock('@/api/customers', () => ({
    createCustomer: vi.fn().mockResolvedValue({
        id: '01NEW',
        name: 'New Customer',
        email: 'new@example.com',
        external_reference: null,
        status: 'active',
    }),
    getCustomer: vi.fn().mockResolvedValue({
        id: '01EDIT',
        name: 'Existing',
        email: 'existing@example.com',
        external_reference: 'CRM-999',
        status: 'active',
    }),
    updateCustomer: vi.fn().mockResolvedValue({}),
}));

vi.mock('@/composables/useAuth', () => ({
    useAuth: () => ({
        isOwner: { value: true },
        user: { value: { id: '1', name: 'Test', email: 'test@test.com', role: 'owner' } },
        merchant: { value: { id: '1', name: 'Acme', slug: 'acme' } },
        isAuthenticated: { value: true },
    }),
}));

describe('CustomerFormPage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/customers', name: 'customers.index', component: { template: '<div />' } },
                {
                    path: '/customers/create',
                    name: 'customers.create',
                    component: CustomerFormPage,
                },
                {
                    path: '/customers/:id/edit',
                    name: 'customers.edit',
                    component: CustomerFormPage,
                },
            ],
        });
    });

    it('renders create form', async () => {
        await router.push('/customers/create');
        await router.isReady();

        const wrapper = mount(CustomerFormPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Create Customer');
        expect(wrapper.find('input#name').exists()).toBe(true);
        expect(wrapper.find('input#email').exists()).toBe(true);
        expect(wrapper.find('input#external_reference').exists()).toBe(true);
    });

    it('renders edit form with preloaded data', async () => {
        await router.push('/customers/01EDIT/edit');
        await router.isReady();

        const wrapper = mount(CustomerFormPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Edit Customer');
    });
});
