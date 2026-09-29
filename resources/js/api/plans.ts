import client from './client';
import type { Plan, PaginatedResponse } from '@/types/plans';

export async function getPlans(page = 1, status?: string): Promise<PaginatedResponse<Plan>> {
    const params: Record<string, string | number> = { page };
    if (status) params.status = status;

    const { data } = await client.get<PaginatedResponse<Plan>>('/v1/plans', { params });
    return data;
}

export async function getPlan(id: string): Promise<Plan> {
    const { data } = await client.get<{ data: Plan }>(`/v1/plans/${id}`);
    return data.data;
}

export async function createPlan(
    payload: Omit<Plan, 'id' | 'status' | 'created_at' | 'updated_at'>,
): Promise<Plan> {
    const { data } = await client.post<{ data: Plan }>('/v1/plans', payload);
    return data.data;
}

export async function updatePlan(
    id: string,
    payload: Partial<Omit<Plan, 'id' | 'status' | 'created_at' | 'updated_at'>>,
): Promise<Plan> {
    const { data } = await client.put<{ data: Plan }>(`/v1/plans/${id}`, payload);
    return data.data;
}

export async function archivePlan(id: string): Promise<Plan> {
    const { data } = await client.delete<{ data: Plan }>(`/v1/plans/${id}`);
    return data.data;
}
