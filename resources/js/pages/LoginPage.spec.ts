import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from './LoginPage.vue';

function createTestRouter() {
    return createRouter({
        history: createWebHistory(),
        routes: [
            { path: '/login', name: 'login', component: LoginPage },
            { path: '/', name: 'home', component: { template: '<div>home</div>' } },
        ],
    });
}

describe('LoginPage', () => {
    it('renders the sign in form', async () => {
        const router = createTestRouter();
        router.push('/login');
        await router.isReady();

        const wrapper = mount(LoginPage, {
            global: { plugins: [router] },
        });

        expect(wrapper.find('h2').text()).toBe('Sign in');
        expect(wrapper.find('input#merchant').exists()).toBe(true);
        expect(wrapper.find('input#email').exists()).toBe(true);
        expect(wrapper.find('input#password').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').text()).toBe('Sign in');
    });

    it('has required attributes on inputs', async () => {
        const router = createTestRouter();
        router.push('/login');
        await router.isReady();

        const wrapper = mount(LoginPage, {
            global: { plugins: [router] },
        });

        expect(wrapper.find('input#merchant').attributes('required')).toBeDefined();
        expect(wrapper.find('input#email').attributes('required')).toBeDefined();
        expect(wrapper.find('input#password').attributes('required')).toBeDefined();
    });
});
