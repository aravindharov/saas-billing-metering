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
