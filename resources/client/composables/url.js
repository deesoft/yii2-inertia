import { router, usePage } from "@inertiajs/vue3";
import { reactive, computed } from "vue";
const { yiiUrl } = window;
const page = usePage();

export const URL = reactive({
    current: computed(() => page.url),
    route: computed(() => page.props.$r[0]),
    params: computed(() => page.props.$r[1]),
    /**
     * 
     * @param {object} params 
     * @param {object} options 
     */
    reload(params, options) {
        let url = yiiUrl(this.route, { ...this.params, ...(params || {}) });
        return router.visit(url, {
            preserveScroll: true,
            preserveState: true,
            ...(options || {}),
            method: 'get',
        });
    },
});
