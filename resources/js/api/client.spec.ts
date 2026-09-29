import { describe, it, expect, beforeEach } from 'vitest';
import client from './client';

describe('API client', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('uses /api as the base URL', () => {
        expect(client.defaults.baseURL).toBe('/api');
    });

    it('sends JSON Accept header', () => {
        expect(client.defaults.headers.Accept).toBe('application/json');
    });
});
