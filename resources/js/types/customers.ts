export interface Customer {
    id: string;
    name: string;
    email: string;
    external_reference: string | null;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
}

export interface CustomerFormData {
    name: string;
    email: string;
    external_reference: string;
}
