import { getJson, debounce } from './http';

/** Pengecekan duplikasi gedung (debounce). Backend & DB tetap pengaman utama. */
export default ({ url, ignore = null, name = '', code = '' }) => ({
    name,
    code,
    checking: false,
    result: null,
    dismissedSimilar: false,

    init() {
        this.check = debounce(this.runCheck.bind(this), 400);
        this.$watch('name', () => { this.dismissedSimilar = false; this.check(); });
        this.$watch('code', () => this.check());
        if (this.name || this.code) this.runCheck();
    },

    async runCheck() {
        if (!this.name.trim() && !this.code.trim()) { this.result = null; return; }
        this.checking = true;
        try {
            this.result = await getJson(url, { name: this.name, code: this.code, ignore });
        } catch (e) {
            this.result = null;
        } finally {
            this.checking = false;
        }
    },

    get blocked() {
        return !!(this.result && (this.result.name_exists || this.result.code_exists));
    },
});
