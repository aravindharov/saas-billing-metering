import client from './client';
import type { InvoiceData } from '@/types/invoices';
import type { SubscriptionData, SubscriptionPlanChange } from '@/types/subscriptions';
import type { PaginatedResponse } from '@/types/plans';

export async function getSubscriptions(
    page = 1,
    status?: string,
    customerId?: string,
    planId?: string,
): Promise<PaginatedResponse<SubscriptionData>> {
    const params: Record<string, string | number> = { page };
    if (status) params.status = status;
    if (customerId) params.customer_id = customerId;
    if (planId) params.plan_id = planId;

    const { data } = await client.get<PaginatedResponse<SubscriptionData>>('/v1/subscriptions', {
        params,
    });
    return data;
}

export async function getSubscription(id: string): Promise<SubscriptionData> {
    const { data } = await client.get<{ data: SubscriptionData }>(`/v1/subscriptions/${id}`);
    return data.data;
}

export async function createSubscription(payload: {
    customer_id: string;
    plan_id: string;
}): Promise<SubscriptionData> {
    const { data } = await client.post<{ data: SubscriptionData }>('/v1/subscriptions', payload);
    return data.data;
}

export async function changePlan(
    subscriptionId: string,
    planId: string,
): Promise<SubscriptionPlanChange> {
    const { data } = await client.post<{ data: SubscriptionPlanChange }>(
        `/v1/subscriptions/${subscriptionId}/change-plan`,
        { plan_id: planId },
    );
    return data.data;
}

export async function cancelSubscription(id: string): Promise<SubscriptionData> {
    const { data } = await client.post<{ data: SubscriptionData }>(
        `/v1/subscriptions/${id}/cancel`,
    );
    return data.data;
}

export async function generateSubscriptionInvoice(subscriptionId: string): Promise<InvoiceData> {
    const { data } = await client.post<{ data: InvoiceData }>(
        `/v1/subscriptions/${subscriptionId}/generate-invoice`,
    );
    return data.data;
}
