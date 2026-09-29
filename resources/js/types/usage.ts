export interface DailyUsageData {
    customer?: { id: string; name: string };
    usage_date: string;
    total_quantity: number;
}

export interface UsageEventData {
    id: string;
    event_id: string;
    customer?: { id: string; name: string };
    subscription?: { id: string };
    quantity: number;
    occurred_at: string;
    created_at: string;
}
