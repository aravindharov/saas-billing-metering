export interface SubscriptionPlanChange {
    id: string;
    from_plan: { id: string; name: string } | null;
    to_plan: { id: string; name: string } | null;
    effective_at: string;
    from_base_price: number;
    from_included_usage_units: number;
    from_overage_rate: number;
    from_billing_cycle: string;
    to_base_price: number;
    to_included_usage_units: number;
    to_overage_rate: number;
    to_billing_cycle: string;
    created_at: string;
}

export interface SubscriptionData {
    id: string;
    customer?: { id: string; name: string };
    plan?: { id: string; name: string };
    status: 'active' | 'cancelled' | 'expired';
    billing_cycle: 'monthly' | 'yearly';
    base_price: number;
    included_usage_units: number;
    overage_rate: number;
    started_at: string;
    current_period_start: string;
    current_period_end: string;
    cancelled_at: string | null;
    plan_changes: SubscriptionPlanChange[];
    created_at: string;
    updated_at: string;
}
