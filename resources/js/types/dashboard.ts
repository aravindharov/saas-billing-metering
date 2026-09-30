export type DashboardTopCustomer = {
    customer_id: string;
    name: string;
    email: string;
    usage_units: number;
};

export type DashboardUsageDrop = {
    customer_id: string;
    name: string;
    current_usage_units: number;
    previous_usage_units: number;
    percentage_change: number;
};

export type DashboardData = {
    generated_at: string;
    period: {
        month_start: string;
        month_end: string;
    };
    summary: {
        current_month_usage_units: number;
        active_customers: number;
        active_subscriptions: number;
    };
    billing_cycle: {
        period_start: string | null;
        period_end: string | null;
    };
    top_customers: DashboardTopCustomer[];
    projected_overage_revenue: {
        amount: number;
        currency: string;
        unit: string;
    };
    usage_drops: DashboardUsageDrop[];
};
