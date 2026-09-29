import client from './client';

export interface HealthResponse {
    status: 'ok' | 'degraded';
    checks: Record<string, 'ok' | 'down'>;
}

export async function getHealth(): Promise<HealthResponse> {
    const { data } = await client.get<HealthResponse>('/health');
    return data;
}
