import axios from 'axios';

/**
 * Client for the internal JSON API (/api/web/*) used for dynamic
 * interactions: favourites, follows, comments, search, polling, uploads.
 * Page data comes through Inertia props instead.
 */
export const api = axios.create({
    baseURL: '/api/web',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
});

api.interceptors.response.use(undefined, (error) => {
    // Login is an OIDC redirect, so a session-expired XHR needs a full-page visit.
    if (error.response?.status === 401 && typeof window !== 'undefined') {
        window.location.href = '/login';
    }
    return Promise.reject(error);
});
