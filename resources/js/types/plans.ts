export interface Plan {
    id: string;
    name: string;
    base_price: number;
    billing_cycle: 'monthly' | 'yearly';
    included_usage_units: number;
    overage_rate: number;
    status: 'active' | 'archived';
    created_at: string;
    updated_at: string;
}

export interface PlanFormData {
    name: string;
    base_price: number | '';
    billing_cycle: 'monthly' | 'yearly';
    included_usage_units: number | '';
    overage_rate: number | '';
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}
