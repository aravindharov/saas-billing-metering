import client from './client';
import type { InvoiceData } from '@/types/invoices';
import type { PaginatedResponse } from '@/types/plans';

export async function getInvoices(
    page = 1,
    filters?: {
        status?: string;
        customer_id?: string;
        subscription_id?: string;
        period_from?: string;
        period_to?: string;
    },
): Promise<PaginatedResponse<InvoiceData>> {
    const params: Record<string, string | number> = { page, ...filters };
    const { data } = await client.get<PaginatedResponse<InvoiceData>>('/v1/invoices', { params });
    return data;
}

export async function getInvoice(id: string): Promise<InvoiceData> {
    const { data } = await client.get<{ data: InvoiceData }>(`/v1/invoices/${id}`);
    return data.data;
}
