export interface AuthUser {
    id: string;
    name: string;
    email: string;
    role: 'owner' | 'member';
}

export interface AuthMerchant {
    id: string;
    name: string;
    slug: string;
}

export interface LoginPayload {
    merchant: string;
    email: string;
    password: string;
}

export interface LoginResponse {
    user: AuthUser;
    merchant: AuthMerchant;
    token: string;
}

export interface MeResponse {
    user: AuthUser;
    merchant: AuthMerchant;
}
