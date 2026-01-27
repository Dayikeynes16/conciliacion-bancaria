export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    current_team_id: number | null;
    current_team?: {
        id: number;
        user_id: number;
        name: string;
    };
    teams?: Array<{
        id: number;
        user_id: number;
        name: string;
    }>;
    all_teams?: Array<{
        id: number;
        user_id: number;
        name: string;
        personal_team?: boolean;
    }>;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    filters: {
        month: number;
        year: number;
    };
    available_years: number[];
    flash: {
        success?: string;
        error?: string;
        warning?: string;
    };
};
