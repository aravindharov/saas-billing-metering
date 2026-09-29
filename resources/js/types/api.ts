/**
 * Standard API envelope for paginated responses.
 */
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

/**
 * Standard API error shape.
 */
export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
}
