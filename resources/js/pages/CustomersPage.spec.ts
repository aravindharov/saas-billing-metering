import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import CustomersPage from './CustomersPage.vue';

vi.mock('@/api/customers', () => ({
    getCustomers: vi.fn().mockResolvedValue({
        data: [
            {
                id: '01ABC',
                name: 'John Smith',
                email: 'john@example.com',
                external_reference: 'CRM-10001',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
                updated_at: '2026-01-01T00:00:00Z',
            },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        links: { first: null, last: null, prev: null, next: null },
    }),
    deactivateCustomer: vi.fn().mockResolvedValue({}),
}));

vi.mock('@/composables/useAuth', () => ({
    useAuth: () => ({
        isOwner: { value: true },
        user: { value: { id: '1', name: 'Test', email: 'test@test.com', role: 'owner' } },
        merchant: { value: { id: '1', name: 'Acme', slug: 'acme' } },
        isAuthenticated: { value: true },
    }),
}));

describe('CustomersPage', () => {
    let router: ReturnType<typeof createRouter>;

    beforeEach(() => {
        router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/customers', name: 'customers.index', component: CustomersPage },
                {
                    path: '/customers/create',
                    name: 'customers.create',
                    component: { template: '<div />' },
                },
                {
                    path: '/customers/:id/edit',
                    name: 'customers.edit',
                    component: { template: '<div />' },
                },
            ],
        });
    });

    it('renders customers table after loading', async () => {
        const wrapper = mount(CustomersPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('John Smith');
        expect(wrapper.text()).toContain('john@example.com');
        expect(wrapper.text()).toContain('CRM-10001');
    });

    it('shows Create Customer link for owners', async () => {
        const wrapper = mount(CustomersPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Create Customer');
    });

    it('shows search input and status filter', async () => {
        const wrapper = mount(CustomersPage, {
            global: { plugins: [router] },
        });

        await flushPromises();

        expect(wrapper.find('input[type="search"]').exists()).toBe(true);
        expect(wrapper.find('select').exists()).toBe(true);
    });
});
