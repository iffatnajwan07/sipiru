import { getJson, debounce } from './http';

export default ({ url, ignore = null, buildingId = '', name = '', code = '' }) => ({
    buildingId: buildingId ? String(buildingId) : '',
    name,
    code,
    checking: false,
    result: null,

    init() {
        this.check = debounce(this.runCheck.bind(this), 400);
        this.$watch('name', () => this.check());
        this.$watch('code', () => this.check());
        this.$watch('buildingId', () => this.runCheck());
        if (this.name || this.code) this.runCheck();
    },

    async runCheck() {
        if (!this.name.trim() && !this.code.trim()) { this.result = null; return; }
        this.checking = true;
        try {
            this.result = await getJson(url, { building_id: this.buildingId, name: this.name, code: this.code, ignore });
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
