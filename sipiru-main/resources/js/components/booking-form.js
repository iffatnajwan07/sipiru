import { getJson, postJson } from './http';

/** Form pengajuan: pilih gedung → ruangan → waktu → cek ketersediaan → ajukan. */
export default ({
    roomsUrl,
    availabilityUrl,
    buildingId = '',
    roomId = '',
    date = '',
    start = '',
    end = '',
    activityType = 'usk',
}) => ({
    buildingId: buildingId ? String(buildingId) : '',
    roomId: roomId ? String(roomId) : '',
    date,
    start,
    end,
    activityType: activityType ? String(activityType) : 'usk',
    rooms: [],
    loadingRooms: false,
    checking: false,
    result: null,

    async init() {
        if (this.buildingId) await this.loadRooms(true);

        // Reset hasil cek ketersediaan jika salah satu field berubah
        ['roomId', 'date', 'start', 'end', 'activityType'].forEach((f) => {
            this.$watch(f, () => (this.result = null));
        });

        // Jika gedung berubah, reset ruangan dan muat ulang daftar ruangan
        this.$watch('buildingId', (value) => {
            this.roomId = '';
            if (value) {
                this.loadRooms();
            } else {
                this.rooms = [];
            }
        });

        if (this.roomId && this.date && this.start && this.end) {
            this.checkAvailability();
        }
    },

    async loadRooms(keepSelection = false) {
        const keep = keepSelection ? this.roomId : '';
        this.rooms = [];
        if (!this.buildingId) return;

        this.loadingRooms = true;
        try {
            this.rooms = await getJson(roomsUrl.replace('__ID__', this.buildingId));
            this.$nextTick(() => {
                this.roomId = keep;
            });
        } finally {
            this.loadingRooms = false;
        }
    },

    get selectedRoom() {
        return this.rooms.find((r) => String(r.id) === String(this.roomId));
    },

    get ready() {
        return this.roomId && this.date && this.start && this.end && this.activityType;
    },

    async checkAvailability() {
        if (!this.ready) return;

        this.checking = true;
        try {
            const { ok, status, data } = await postJson(availabilityUrl, {
                room_id: this.roomId,
                date: this.date,
                start_time: this.start,
                end_time: this.end,
                activity_type: this.activityType,
            });

            if (ok) {
                this.result = data;
            } else if (status === 422) {
                this.result = {
                    available: false,
                    errors: Object.values(data.errors || {}).flat(),
                    conflicts: [],
                };
            } else if (status === 429) {
                this.result = {
                    available: false,
                    errors: ['Terlalu banyak permintaan. Coba lagi sebentar.'],
                    conflicts: [],
                };
            } else {
                this.result = {
                    available: false,
                    errors: ['Gagal memeriksa ketersediaan. Muat ulang halaman.'],
                    conflicts: [],
                };
            }
        } catch (e) {
            this.result = {
                available: false,
                errors: ['Gagal menghubungi server. Periksa koneksi Anda.'],
                conflicts: [],
            };
        } finally {
            this.checking = false;
        }
    },
});