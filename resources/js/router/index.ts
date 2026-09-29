import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { useAuth } from '@/composables/useAuth';

const routes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: DefaultLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'home',
                component: () => import('@/pages/HomePage.vue'),
            },
            {
                path: 'plans',
                name: 'plans.index',
                component: () => import('@/pages/PlansPage.vue'),
            },
            {
                path: 'plans/create',
                name: 'plans.create',
                component: () => import('@/pages/PlanFormPage.vue'),
            },
            {
                path: 'plans/:id/edit',
                name: 'plans.edit',
                component: () => import('@/pages/PlanFormPage.vue'),
            },
            {
                path: 'customers',
                name: 'customers.index',
                component: () => import('@/pages/CustomersPage.vue'),
            },
            {
                path: 'customers/create',
                name: 'customers.create',
                component: () => import('@/pages/CustomerFormPage.vue'),
            },
            {
                path: 'customers/:id/edit',
                name: 'customers.edit',
                component: () => import('@/pages/CustomerFormPage.vue'),
            },
            {
                path: 'subscriptions',
                name: 'subscriptions.index',
                component: () => import('@/pages/SubscriptionsPage.vue'),
            },
            {
                path: 'subscriptions/create',
                name: 'subscriptions.create',
                component: () => import('@/pages/CreateSubscriptionPage.vue'),
            },
            {
                path: 'subscriptions/:id',
                name: 'subscriptions.show',
                component: () => import('@/pages/SubscriptionDetailPage.vue'),
            },
            {
                path: 'usage',
                redirect: { name: 'usage.daily' },
            },
            {
                path: 'usage/daily',
                name: 'usage.daily',
                component: () => import('@/pages/DailyUsagePage.vue'),
            },
            {
                path: 'usage/record',
                name: 'usage.record',
                component: () => import('@/pages/RecordUsagePage.vue'),
            },
        ],
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

let authInitialized = false;

router.beforeEach(async (to) => {
    const { isAuthenticated, fetchMe } = useAuth();

    // On first navigation, try restoring the session from a stored token.
    if (!authInitialized) {
        authInitialized = true;
        await fetchMe();
    }

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return { name: 'login' };
    }

    if (to.meta.guest && isAuthenticated.value) {
        return { name: 'home' };
    }
});
