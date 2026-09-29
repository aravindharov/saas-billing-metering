import client from './client';
import type { LoginPayload, LoginResponse, MeResponse } from '@/types/auth';

export async function login(payload: LoginPayload): Promise<LoginResponse> {
    const { data } = await client.post<LoginResponse>('/v1/auth/login', payload);
    return data;
}

export async function logout(): Promise<void> {
    await client.post('/v1/auth/logout');
}

export async function getMe(): Promise<MeResponse> {
    const { data } = await client.get<MeResponse>('/v1/auth/me');
    return data;
}
