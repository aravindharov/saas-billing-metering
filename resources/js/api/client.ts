import axios from 'axios';

const client = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

/**
 * Attach the Bearer token from localStorage on every request.
 * The token is stored after login and removed on logout.
 */
client.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default client;
