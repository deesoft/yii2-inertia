export { };

interface CreateUrl {
    (route: string, params?: Record<string, any>, method?: string): string;

    get(route: string, params?: Record<string, any>): string;
    post(route: string, params?: Record<string, any>): string;
    put(route: string, params?: Record<string, any>): string;
    head(route: string, params?: Record<string, any>): string;
    patch(route: string, params?: Record<string, any>): string;
    delete(route: string, params?: Record<string, any>): string;
    base: string;
    home: string;
    public(asset: string): string;
    back(): void;
}

declare global {
    interface Meta {
        currentPage: number;
        perPage: number;
        totalCount: number;
        pageCount: number;
    }
    interface LinkItem {
        label: string;
        href: string;
        active: boolean;
    }
    interface TDataProvider<T> {
        meta: Meta;
        links: LinkItem[];
        items: T[];
        [key: string]: Meta | LinkItem[] | T[];
    }
    interface Window {
        yiiUrl: CreateUrl;
    }
}