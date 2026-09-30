import client from './client';
import type { DashboardData } from '@/types/dashboard';

export async function getMerchantDashboard(merchantId: string): Promise<DashboardData> {
    const { data } = await client.get<{ data: DashboardData }>(
        `/v1/merchants/${merchantId}/dashboard`,
    );
    return data.data;
}
