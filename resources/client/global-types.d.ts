import {TYiiUrl} from '../vendor/deesoft/yii2-client-url/type';
export { };

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
        yiiUrl: TYiiUrl;
    }
}