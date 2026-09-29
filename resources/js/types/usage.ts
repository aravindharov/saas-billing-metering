export interface DailyUsageData {
    customer?: { id: string; name: string };
    usage_date: string;
    total_quantity: number;
}
