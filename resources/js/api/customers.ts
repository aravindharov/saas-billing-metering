import client from './client';
import type { Customer } from '@/types/customers';
import type { PaginatedResponse } from '@/types/plans';

export async function getCustomers(
    page = 1,
    status?: string,
    search?: string,
): Promise<PaginatedResponse<Customer>> {
    const params: Record<string, string | number> = { page };
    if (status) params.status = status;
    if (search) params.search = search;

    const { data } = await client.get<PaginatedResponse<Customer>>('/v1/customers', { params });
    return data;
}

export async function getCustomer(id: string): Promise<Customer> {
    const { data } = await client.get<{ data: Customer }>(`/v1/customers/${id}`);
    return data.data;
}

export async function createCustomer(
    payload: Omit<Customer, 'id' | 'status' | 'created_at' | 'updated_at'>,
): Promise<Customer> {
    const { data } = await client.post<{ data: Customer }>('/v1/customers', payload);
    return data.data;
}

export async function updateCustomer(
    id: string,
    payload: Partial<Omit<Customer, 'id' | 'status' | 'created_at' | 'updated_at'>>,
): Promise<Customer> {
    const { data } = await client.put<{ data: Customer }>(`/v1/customers/${id}`, payload);
    return data.data;
}

export async function deactivateCustomer(id: string): Promise<Customer> {
    const { data } = await client.delete<{ data: Customer }>(`/v1/customers/${id}`);
    return data.data;
}
