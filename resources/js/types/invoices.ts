export type InvoiceLine = {
    type: 'base' | 'overage';
    description: string;
    quantity: number;
    unit_price: number;
    amount: number;
    metadata?: Record<string, unknown> | null;
};

export type InvoiceData = {
    id: string;
    customer?: { id: string; name: string } | null;
    subscription?: { id: string } | null;
    billing_period_start: string;
    billing_period_end: string;
    subtotal: number;
    total: number;
    status: 'draft' | 'issued';
    issued_at: string | null;
    lines?: InvoiceLine[];
    created_at?: string;
};
