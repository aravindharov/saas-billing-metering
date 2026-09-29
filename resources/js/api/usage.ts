import client from './client';
import type { DailyUsageData, UsageEventData } from '@/types/usage';
import type { PaginatedResponse } from '@/types/plans';

export async function getDailyUsage(
    page = 1,
    date?: string,
    from?: string,
    to?: string,
    customerId?: string,
): Promise<PaginatedResponse<DailyUsageData>> {
    const params: Record<string, string | number> = { page };
    if (date) params.date = date;
    if (from) params.from = from;
    if (to) params.to = to;
    if (customerId) params.customer_id = customerId;

    const { data } = await client.get<PaginatedResponse<DailyUsageData>>('/v1/usage/daily', {
        params,
    });
    return data;
}

export async function recordUsage(payload: {
    event_id: string;
    customer_id: string;
    subscription_id: string;
    quantity: number;
    occurred_at: string;
}): Promise<UsageEventData> {
    const { data } = await client.post<{ data: UsageEventData }>('/v1/usage', payload);
    return data.data;
}
