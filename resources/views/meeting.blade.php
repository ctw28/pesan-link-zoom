<!DOCTYPE html>
<html>

<head>
    <title>Booking Zoom</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .text-gradient {
            background: linear-gradient(45deg, #0d6efd, #6610f2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>


    <div id="app" class="container mt-4">

        <h4 class="mb-4 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-camera-video text-primary fs-3"></i>
            <span class="text-gradient">Booking Link Zoom IAIN KENDARI</span>
        </h4>


        <p>Yang mau pakai zoom, silahkan buat di aplikasi ini ya... semoga jadwalmu belum ada yang ambil</p>
        <!-- ACTION BUTTON -->
        <div class="col-12">
            <label class="form-label fw-bold">Pilih Akun Zoom yang Ingin Digunakan:</label>
            <select
                v-model="form.zoom_account_id"
                class="form-control mb-3"
                @change="loadMeetings">
                <option value="">Pilih Zoom</option>
                <option v-for="z in zoomAccounts" :value="z.id">
                    @{{ z.name }} (Kapasitas @{{ z.capacity }})
                </option>
            </select>
        </div>
        <div class="row g-2 mb-4" v-if="form.zoom_account_id">
            <div class="col-12 col-md-4">
                <button class="btn btn-primary w-100" @click="openModal">
                    <i class="bi bi-plus-circle"></i> Buat Link Zoom
                </button>
            </div>

            <div class="col-12 col-md-4">
                <button class="btn btn-warning w-100" @click="openSearch">
                    <i class="bi bi-search"></i> Cari / Edit Link
                </button>
            </div>

        </div>

        <!-- LOADING GLOBAL -->
        <div v-if="loadingMeetings" class="text-center p-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Mengambil data jadwal...</p>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="meetings.length == 0 && isPilihAkun && !loadingMeetings" class="text-center p-5">
            <div class="alert alert-warning">Jadwal Kosong, Ambil memang link zoom sebelum diambil orang lain</div>
        </div>

        <!-- ========================= -->
        <!-- 📱 MOBILE (CARD VIEW) -->
        <!-- ========================= -->
        <div class="d-md-none">
            <div class="card mb-3 shadow-sm" v-for="(m,index) in meetings" :key="index" v-if="!loadingMeetings && meetings.length > 0">

                <div class="card-body">

                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-briefcase"></i> @{{ m.topic }}
                    </h6>

                    <p class="mb-1">
                        <i class="bi bi-calendar"></i>
                        @{{ formatTanggal(m.tanggal) }}
                    </p>

                    <p class="mb-1">
                        <i class="bi bi-clock"></i>
                        @{{ m.jam_mulai }}
                        <span class="text-muted">(@{{ m.duration }} menit)</span>
                    </p>

                    <p class="mb-1">
                        <i class="bi bi-person"></i>
                        @{{ m.nama_pemesan }}
                    </p>

                    <!-- STATUS -->
                    <div class="mt-2">
                        <span v-if="getStatus(m)=='ongoing'" class="badge bg-warning">
                            ⏳ Berlangsung
                        </span>
                        <span v-else class="badge bg-success">
                            ✔ Terjadwal
                        </span>
                    </div>

                    <!-- SOURCE -->
                    <div class="mt-2">
                        <small v-if="m.status=='zoom_only'" class="text-muted">
                            <i class="bi bi-cloud"></i> Dari Zoom langsung
                        </small>
                        <small v-else class="text-success">
                            <i class="bi bi-database"></i> Dari aplikasi
                        </small>
                    </div>

                    <!-- AKSI (MOBILE) -->
                    <div v-if="isSearching" class="mt-3 d-flex gap-2">
                        <!-- COPY LINK -->
                        <button
                            class="btn btn-primary btn-sm w-100"
                            @click="copyAll(m)">
                            <i class="bi bi-clipboard"></i> Copy Link
                        </button>
                        <button class="btn btn-warning btn-sm w-100" @click="editMeeting(m)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>

                        <button
                            class="btn btn-danger btn-sm w-100"
                            @click="deleteMeeting(m)"
                            :disabled="m.status === 'finished' || m.status === 'ongoing'">
                            <i class="bi bi-trash"></i> Hapus
                        </button>

                    </div>

                </div>
            </div>
        </div>

        <!-- ========================= -->
        <!-- 💻 DESKTOP (TABLE VIEW) -->
        <!-- ========================= -->
        <div class="card shadow-sm d-none d-md-block"
            v-if="!loadingMeetings && meetings.length > 0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar3"></i> Jadwal Booking</span>
                <span class="badge bg-primary">@{{ meetings.length }} Jadwal</span>
            </div>

            <div class="card-body table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light text-center">
                        <tr>
                            <th>No</th>
                            <th v-if="isSearching">Aksi</th>
                            <th>Kegiatan</th>
                            <th>Waktu</th>
                            <th>Pemesan</th>
                            <th>Status</th>
                            <th>Dibuat dari</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(m,index) in meetings" :key="index">

                            <td class="text-center">@{{ index + 1 }}</td>
                            <td v-if="isSearching" class="text-center">
                                <!-- 🔥 COPY LINK -->
                                <button
                                    class="btn btn-primary btn-sm me-1"
                                    @click="copyAll(m)">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <button class="btn btn-warning btn-sm me-1" @click="editMeeting(m)">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button
                                    class="btn btn-danger btn-sm"
                                    @click="deleteMeeting(m)"
                                    :disabled="m.status === 'finished' || m.status === 'ongoing'">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </td>
                            <td>
                                <b>@{{ m.topic }}</b>
                            </td>

                            <td>
                                <i class="bi bi-calendar"></i>
                                @{{ formatTanggal(m.tanggal) }}<br>

                                <i class="bi bi-clock"></i>
                                @{{ m.jam_mulai }}
                                <small class="text-muted">
                                    (@{{ m.duration }} menit)
                                </small>
                            </td>

                            <td>
                                <i class="bi bi-person"></i>
                                @{{ m.nama_pemesan }}
                            </td>

                            <td class="text-center">
                                <span v-if="getStatus(m)=='ongoing'" class="badge bg-warning">
                                    Berlangsung
                                </span>
                                <span v-else class="badge bg-success">
                                    Terjadwal
                                </span>
                            </td>
                            <td class="text-center">
                                <span v-if="m.status=='zoom_only'" class="badge bg-secondary">
                                    Dari Zoom Langsung
                                </span>
                                <span v-else class="badge bg-success">
                                    Dari Aplikasi Booking
                                </span>
                            </td>

                        </tr>
                    </tbody>

                </table>

            </div>
        </div>



        <!-- MODAL CREATE -->
        <div class="modal fade" id="modalCreate" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header bg-primary text-white">

                        <h5 class="mb-0">
                            <i class="bi bi-calendar-plus"></i>
                            Booking Zoom (@{{ mode === 'edit' ? 'Mode Edit Jadwal' : 'Buat Jadwal Baru' }})
                        </h5>

                        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

                    </div>
                    <div class="px-3 pt-3">

                        <div class="d-flex justify-content-between text-center small">

                            <!-- STEP 1 -->
                            <div>
                                <div :class="step>=1 ? 'text-primary fw-bold' : 'text-muted'">

                                    <span v-if="step > 1">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </span>
                                    <span v-else>
                                        <i class="bi bi-1-circle"></i>
                                    </span>

                                    Pilih Jadwal
                                </div>
                            </div>

                            <!-- STEP 2 -->
                            <div>
                                <div :class="step>=2 ? 'text-primary fw-bold' : 'text-muted'">

                                    <span v-if="step > 2">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </span>
                                    <span v-else>
                                        <i class="bi bi-2-circle"></i>
                                    </span>
                                    Konfirmasi
                                </div>
                            </div>

                            <!-- STEP 3 -->
                            <div>
                                <div :class="step>=3 ? 'text-primary fw-bold' : 'text-muted'">

                                    <span v-if="step >= 3">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </span>
                                    <span v-else>
                                        <i class="bi bi-3-circle"></i>
                                    </span>

                                    Selesai
                                </div>
                            </div>

                        </div>
                        <hr>

                    </div>
                    <div class="modal-body pt-0">
                        <div v-if="errorMessage" class="alert alert-danger">
                            @{{ errorMessage }}
                        </div>
                        <!-- STEP 1 -->
                        <div v-if="step==1">
                            <!-- 🔵 PREVIEW -->
                            <div v-if="form.tanggal && form.jam_mulai && form.duration" class="alert alert-light border">

                                <small class="text-muted">Preview Jadwal:</small><br>

                                <b>
                                    @{{ formatTanggal(form.tanggal) }}<br>
                                    @{{ form.jam_mulai }} - @{{ hitungSelesai(form.jam_mulai, form.duration) }}
                                </b>

                            </div>

                            <div class="card border-0 shadow-sm">
                                <div class="card-body">

                                    <h5 class="mb-3 mt-0">
                                        <i class="bi bi-account"></i> Akun Zoom: @{{ selectedZoom ? selectedZoom.name : '-' }} (Kapasitas @{{ selectedZoom ? selectedZoom.capacity : '-' }})
                                    </h5>
                                    <h6 class="mb-3">
                                        <i class="bi bi-clock"></i> Pilih Jadwal Meeting
                                    </h6>

                                    <!-- 📅 TANGGAL -->
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-calendar"></i>
                                            </span>
                                            <input type="date" v-model="form.tanggal" class="form-control">
                                        </div>
                                    </div>

                                    <!-- ⏰ JAM -->
                                    <div class="mb-3">
                                        <label class="form-label">Jam Mulai</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-clock"></i>
                                            </span>
                                            <input type="time" v-model="form.jam_mulai" class="form-control">
                                        </div>
                                    </div>

                                    <!-- ⏱️ DURASI -->
                                    <div class="mb-3">
                                        <label class="form-label">Durasi (menit)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-hourglass-split"></i>
                                            </span>
                                            <input type="number" v-model.number="form.duration" class="form-control" placeholder="Contoh: 60">
                                        </div>
                                    </div>



                                    <!-- BUTTON -->
                                    <button
                                        class="btn btn-primary w-100 mt-2"
                                        @click="checkSchedule"
                                        :disabled="loadingCheck">

                                        <span v-if="!loadingCheck">
                                            <i class="bi bi-search"></i> Cek Ketersediaan
                                        </span>

                                        <span v-else>
                                            <span class="spinner-border spinner-border-sm"></span>
                                            Mengecek...
                                        </span>

                                    </button>

                                </div>
                            </div>

                        </div>

                        <!-- STEP 2 -->
                        <div v-if="step==2">

                            <!-- 🔴 CONFLICT LOCAL -->
                            <div v-if="status=='conflict' && source=='local'" class="alert alert-danger">

                                <b>Jadwal sudah terisi</b><br><br>

                                <table class="table table-sm">
                                    <tr>
                                        <td>Nama</td>
                                        <td>: @{{ conflict.nama_pemesan }}</td>
                                    </tr>
                                    <tr>
                                        <td>Unit</td>
                                        <td>: @{{ conflict.unit }}</td>
                                    </tr>
                                    <tr>
                                        <td>Kegiatan</td>
                                        <td>: @{{ conflict.topic }}</td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal</td>
                                        <td>: @{{ conflict.tanggal }}</td>
                                    </tr>
                                    <tr>
                                        <td>Jam</td>
                                        <td>: @{{ conflict.jam_mulai }}</td>
                                    </tr>
                                    <tr>
                                        <td>Waktu</td>
                                        <td>:
                                            <span class="badge bg-danger">
                                                @{{ conflict.jam_mulai }} ( @{{ conflict.duration }} menit )
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                                <!-- <p>Bisa kita coba koordinasi dengan pemesan link tersebut, bisa jadi dia batal zoomnya.</p> -->

                                <hr>

                                <!-- 🔥 TOMBOL AKSI -->
                                <button class="btn btn-warning btn-sm w-100" @click="resetJadwal">
                                    Kembali Cari Jadwal Lain
                                </button>

                            </div>

                            <!-- 🔴 CONFLICT ZOOM -->
                            <div v-if="status=='conflict' && source=='zoom'" class="alert alert-danger">
                                Jadwal sudah digunakan dari Zoom.<br>
                                Silahkan hubungi admin Zoom TIPD.
                                <button class="btn btn-warning btn-sm w-100" @click="resetJadwal">
                                    Kembali Cari Jadwal Lain
                                </button>
                            </div>


                            <!-- ✅ AVAILABLE -->
                            <div v-if="status=='available'" class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                Alhamdulillah jadwal tersedia. Silahkan isi data pemesan dan buat link zoomnya sekarang juga.
                            </div>

                            <!-- 🔥 FORM -->
                            <div v-if="status!='conflict'" class="card shadow-sm">

                                <div class="card-body">

                                    <!-- 📅 SUB JUDUL -->
                                    <h5 class="mb-3">
                                        <i class="bi bi-calendar-event"></i> Detail Jadwal
                                    </h5>

                                    <!-- 📅 INFO JADWAL -->
                                    <div class="alert alert-light border">

                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <small class="text-muted">Tanggal</small><br>
                                                <b>@{{ formatTanggal(form.tanggal) }}</b>
                                            </div>

                                            <div class="col-md-4 mb-2">
                                                <small class="text-muted">Jam Mulai</small><br>
                                                <b>@{{ form.jam_mulai }}</b>
                                            </div>

                                            <div class="col-md-4 mb-2">
                                                <small class="text-muted">Durasi</small><br>
                                                <b>@{{ form.duration }} menit</b>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- 📝 KEGIATAN -->
                                    <h6 class="mt-3">
                                        <i class="bi bi-briefcase"></i> Informasi Kegiatan
                                    </h6>

                                    <input v-model="form.topic" class="form-control mb-3" placeholder="Contoh: Rapat Evaluasi, Seminar, dll">

                                    <!-- 👤 DATA PEMESAN -->
                                    <h6>
                                        <i class="bi bi-person"></i> Data Pemesan
                                    </h6>

                                    <input v-model="form.nama_pemesan" class="form-control mb-2" placeholder="Nama Pemesan">
                                    <input v-model="form.unit" class="form-control mb-2" placeholder="Unit / Instansi">
                                    <input :disabled="mode==='edit'" v-model="form.no_hp" class="form-control mb-3" placeholder="No HP / WhatsApp">

                                    <!-- 🔘 ACTION -->
                                    <button
                                        :class="mode === 'edit' ? 'btn btn-warning w-100' : 'btn btn-success w-100'"
                                        @click="submitMeeting"
                                        :disabled="loadingSubmit">

                                        <span v-if="loadingSubmit">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            @{{ mode === 'edit' ? 'Mengupdate...' : 'Memproses...' }}
                                        </span>

                                        <span v-else>
                                            <i class="bi" :class="mode === 'edit' ? 'bi-pencil' : 'bi-check-circle'"></i>
                                            @{{ mode === 'edit' ? 'Update Link Zoom' : 'Pesan Link Zoom Sekarang' }}
                                        </span>

                                    </button>

                                    <div v-if="mode !== 'edit'" class="mt-3 text-center text-muted">
                                        <small>- atau -</small>
                                    </div>

                                    <button
                                        v-if="mode !== 'edit'"
                                        class="btn btn-outline-warning w-100 mt-2"
                                        @click="resetJadwal">
                                        <i class="bi bi-arrow-left"></i> Kembali Cari Jadwal Lain
                                    </button>

                                </div>

                            </div>

                        </div>

                        <!-- STEP 3 -->
                        <div v-if="step==3">

                            <div class="alert alert-success">
                                ✅ Meeting berhasil dibuat
                            </div>

                            <!-- 🔹 INFO KEGIATAN -->
                            <div class="mb-3 p-3 border rounded">

                                <p><b>Kegiatan:</b><br>
                                    @{{ result.topic }}
                                </p>

                                <p><b>Waktu:</b><br>
                                    @{{ formatTanggal(result.tanggal) }}<br>
                                    @{{ result.jam_mulai }} (@{{ result.duration }} menit)
                                </p>

                            </div>

                            <!-- 🔹 INFO ZOOM -->
                            <div class="mb-3 p-3 border rounded">

                                <p><b>Link Zoom:</b></p>
                                <input class="form-control mb-2" :value="result.join_url" readonly>

                                <p><b>Meeting ID:</b></p>
                                <input class="form-control mb-2" :value="result.zoom_meeting_id" readonly>

                                <p><b>Password:</b></p>
                                <input class="form-control mb-2" :value="result.password" readonly>

                            </div>

                            <!-- 🔹 ACTION -->
                            <button class="btn btn-secondary w-100 mb-2" @click="copyAll(result)">
                                📋 Copy Link Zoom
                            </button>

                            <button class="btn btn-primary w-100" @click="resetForm">
                                ➕ Buat Jadwal Lagi
                            </button>

                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL SEARCH -->
        <div class="modal fade" id="modalSearch">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-search"></i> Cari Meeting
                        </h5>
                        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- 🔍 INPUT -->
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="bi bi-phone"></i>
                            </span>
                            <input v-model="search_hp" class="form-control" placeholder="Masukkan No HP">
                        </div>

                        <!-- BUTTON -->
                        <button
                            class="btn btn-primary w-100 mb-3"
                            @click="search"
                            :disabled="loadingSearch">

                            <span v-if="!loadingSearch">
                                <i class="bi bi-search"></i> Cari Meeting
                            </span>

                            <span v-else>
                                <span class="spinner-border spinner-border-sm"></span>
                                Mencari...
                            </span>
                        </button>

                        <!-- 🔄 LOADING -->
                        <div v-if="loadingSearch" class="text-center p-3">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 text-muted">Mencari data...</p>
                        </div>

                        <!-- ❌ TIDAK DITEMUKAN -->
                        <div v-if="!loadingSearch && searched && searchResult.length == 0" class="text-center p-4">
                            <i class="bi bi-search" style="font-size:40px;"></i>
                            <h6 class="mt-2">Data tidak ditemukan</h6>
                            <small class="text-muted">Pastikan nomor HP benar</small>
                        </div>

                        <!-- ✅ HASIL -->


                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        new Vue({
            el: '#app',
            data: {
                meetings: [],
                step: 1,
                status: '',
                conflict: {},
                result: {},
                search_hp: '',
                searchResult: [],
                form: {
                    zoom_account_id: '',
                    tanggal: '',
                    jam_mulai: '',
                    duration: 60,
                    nama_pemesan: '',
                    unit: '',
                    no_hp: '',
                    topic: ''
                },
                source: '',
                errorMessage: '',
                loadingCheck: false,
                loadingMeetings: false,
                loadingSearch: false,
                searched: false,
                mode: 'create',
                isSearching: false, // 🔥 ini kuncinya
                loadingSubmit: false, // 🔥 ini,
                zoomAccounts: [], // 🔥 daftar akun zoom untuk dropdown
                isPilihAkun: false // 🔥 untuk kontrol tampil dropdown akun atau input manual
            },
            mounted() {
                this.getZoomAccounts()
                this.modalSearch = new bootstrap.Modal(document.getElementById('modalSearch'));
                this.modalCreate = new bootstrap.Modal(document.getElementById('modalCreate'));
                this.form.jam_mulai = this.getCurrentTime()

            },
            computed: {
                selectedZoom() {
                    return this.zoomAccounts.find(z => z.id == this.form.zoom_account_id)
                }
            },
            methods: {
                getZoomAccounts() {
                    axios.get('/api/zoom-accounts')
                        .then(res => {
                            this.zoomAccounts = res.data

                        })
                },

                async loadMeetings() {
                    if (!this.form.zoom_account_id) {
                        this.isPilihAkun = false

                        this.meetings = []
                        return
                    }

                    this.loadingMeetings = true

                    try {
                        const res = await axios.get('/api/meetings', {
                            params: {
                                zoom_account_id: this.form.zoom_account_id
                            }
                        })
                        console.log(this.form.zoom_account_id);
                        this.isPilihAkun = true
                        this.isSearching = false
                        this.meetings = res.data.data
                        console.log(this.meetings);


                    } catch (e) {
                        console.log(e)
                    } finally {
                        this.loadingMeetings = false
                    }
                },

                openModal() {
                    // this.resetForm();
                    this.step = 1;
                    new bootstrap.Modal(document.getElementById('modalCreate')).show();
                },
                editMeeting(m) {
                    console.log(m);

                    this.modalSearch.hide();
                    // const zoomId = this.form.zoom_account_id

                    this.mode = 'edit'
                    this.form = {
                        ...m
                    }
                    this.step = 1;

                    this.modalCreate.show();
                },

                openSearch() {
                    new bootstrap.Modal(document.getElementById('modalSearch')).show();
                },

                async checkSchedule() {
                    this.loadingCheck = true

                    try {
                        if (!this.form.tanggal || !this.form.jam_mulai || !this.form.duration) {
                            this.errorMessage = 'Tanggal, jam mulai, dan durasi wajib diisi';
                            return;
                        }

                        this.errorMessage = '';

                        const res = await axios.post('/api/meetings/check', this.form);
                        console.log(res);

                        this.status = res.data.status;
                        this.source = res.data.source;
                        this.conflict = res.data.data || {};
                        this.step = 2;

                    } catch (e) {
                        console.log(e)
                    } finally {
                        this.loadingCheck = false
                    }
                },
                submitMeeting() {
                    if (this.loadingSubmit) return // 🔥 cegah klik berkali2

                    if (this.mode === 'edit') {
                        this.updateMeeting()
                    } else {
                        this.createMeeting()
                    }
                },
                updateMeeting() {

                    this.loadingSubmit = true

                    axios.post('/api/update-meeting', this.form)
                        .then(res => {
                            alert('Berhasil update')

                            this.modalCreate.hide()
                            this.loadMeetings()
                        })
                        .catch(err => {
                            alert(err.response?.data?.message || 'Gagal update')
                        })
                        .finally(() => {
                            this.loadingSubmit = false // 🔥 reset
                        })
                },
                createMeeting() {

                    // 🔴 VALIDASI
                    if (
                        !this.form.nama_pemesan ||
                        !this.form.unit ||
                        !this.form.no_hp ||
                        !this.form.topic
                    ) {
                        alert('Semua data wajib diisi');
                        return;
                    }

                    this.loadingSubmit = true

                    axios.post('/api/meetings', this.form)
                        .then(res => {
                            this.result = res.data
                            this.step = 3
                            this.loadMeetings()
                        })
                        .catch(err => {
                            alert(err.response?.data?.message || 'Gagal membuat meeting')
                        })
                        .finally(() => {
                            this.loadingSubmit = false // 🔥 reset
                        })
                },

                search() {
                    this.loadingSearch = true
                    this.searched = false

                    axios.post('/api/meetings/search', {
                            no_hp: this.search_hp,
                            zoom_account_id: this.form.zoom_account_id
                        })
                        .then(res => {
                            // return console.log(res.data);

                            this.searchResult = res.data.data
                            this.searched = true

                            // 🔥 kalau ketemu → close modal + isi tabel utama
                            if (this.searchResult.length > 0) {
                                this.isSearching = true // 🔥 tandai sedang hasil search

                                // isi tabel utama
                                this.meetings = this.searchResult

                                // tutup modal
                                const modal = bootstrap.Modal.getInstance(document.getElementById('modalSearch'))
                                if (modal) modal.hide()
                            }

                        })
                        .finally(() => {
                            this.loadingSearch = false
                        })
                },

                async copyAll(m) {
                    let text = [
                        `📢 *${m.topic}*`,
                        `📅 ${this.formatTanggal(m.tanggal)}`,
                        `⏰ ${m.jam_mulai} (${m.duration} menit)`,
                        '',
                        `🔗 ${m.join_url}`,
                        `🆔 ID: ${m.zoom_meeting_id}`,
                        `🔑 Password: ${m.password}`
                    ].join('\n');

                    await navigator.clipboard.writeText(text);
                    alert('Semua info berhasil disalin');
                },

                resetForm() {
                    const zoomId = this.form.zoom_account_id

                    this.form = {
                        zoom_account_id: zoomId, // 🔥 dipertahankan
                        tanggal: '',
                        jam_mulai: this.getCurrentTime(),
                        duration: 60,
                        nama_pemesan: '',
                        unit: '',
                        no_hp: '',
                        topic: ''
                    };

                    this.step = 1;
                },

                getStatus(m) {
                    let now = new Date();
                    let start = new Date(m.tanggal + ' ' + m.jam_mulai);
                    let end = new Date(start.getTime() + m.duration * 60000);

                    if (now >= start && now <= end) return 'ongoing';
                    return 'scheduled';
                },
                formatTanggal(tanggal) {
                    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                    let d = new Date(tanggal);

                    return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
                },
                resetJadwal() {
                    this.step = 1 // kembali ke step pilih jadwal
                    this.status = ''
                    this.source = ''
                    this.conflict = {}
                },
                hitungSelesai(jam, durasi) {
                    this.errorMessage = ''
                    if (!jam || !durasi) return ''

                    let [h, m] = jam.split(':')
                    let date = new Date()
                    date.setHours(parseInt(h))
                    date.setMinutes(parseInt(m) + parseInt(durasi))

                    return date.toTimeString().slice(0, 5)
                },
                deleteMeeting(m) {

                    if (!confirm(`Hapus meeting ${m.topic}?`)) return
                    axios.post('/api/delete-meeting', {
                            id: m.id,
                            zoom_account_id: m.zoom_account_id,
                            no_hp: m.no_hp
                        })
                        .then(res => {
                            alert('Meeting berhasil dihapus')

                            this.loadMeetings()

                            // kalau lagi search, ulang search
                            if (this.isSearching) {
                                this.search()
                            }
                        })
                        .catch(err => {
                            alert(err.response?.data?.message || 'Gagal hapus')
                        })
                },
                getCurrentTime() {
                    const now = new Date()
                    const hours = String(now.getHours()).padStart(2, '0')
                    const minutes = String(now.getMinutes()).padStart(2, '0')
                    return `${hours}:${minutes}`
                }
            },


        });
    </script>

</body>

</html>