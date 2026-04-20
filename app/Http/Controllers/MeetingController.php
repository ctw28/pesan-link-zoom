<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Meeting;
use Carbon\Carbon;

class MeetingController extends Controller
{
    // ===============================
    // 🔐 TOKEN ZOOM
    // ===============================
    private function generateToken()
    {
        $response = Http::withBasicAuth(
            env('ZOOM_CLIENT_ID'),
            env('ZOOM_CLIENT_SECRET')
        )->asForm()->post('https://zoom.us/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => env('ZOOM_ACCOUNT_ID')
        ]);

        return $response['access_token'] ?? null;
    }

    // ===============================
    // 📊 LIST JADWAL
    // ===============================
    public function index()
    {
        // =========================
        // 🔥 1. AMBIL ZOOM (BELUM SELESAI)
        // =========================
        $zoomMeetings = collect($this->getZoomMeetings())
            ->filter(function ($z) {

                $start = \Carbon\Carbon::parse($z['start_time'])
                    ->setTimezone('Asia/Makassar');

                $end = (clone $start)->addMinutes($z['duration']);

                // ambil yang belum selesai (scheduled + ongoing)
                return now()->lt($end);
            });

        $zoomIds = $zoomMeetings->pluck('id')->toArray();

        // =========================
        // 🔄 2. UPDATE STATUS WAKTU (AUTO)
        // =========================
        \App\Models\Meeting::whereIn('status', ['scheduled', 'ongoing'])
            ->get()
            ->each(function ($m) {

                $start = \Carbon\Carbon::parse($m->tanggal . ' ' . $m->jam_mulai);
                $end = (clone $start)->addMinutes($m->duration);

                if (now()->between($start, $end)) {
                    $m->status = 'ongoing';
                } elseif (now()->gt($end)) {
                    $m->status = 'finished';
                }

                $m->save();
            });

        // =========================
        // 🧹 3. SYNC: TANDAI YANG HILANG DI ZOOM
        // =========================
        \App\Models\Meeting::whereNotNull('zoom_meeting_id')
            ->whereNotIn('zoom_meeting_id', $zoomIds)
            ->whereIn('status', ['scheduled', 'ongoing']) // 🔥 penting
            ->where('created_at', '<', now()->subMinutes(2)) // delay sync
            ->update([
                'status' => 'missing_in_zoom'
            ]);

        // =========================
        // 🧹 4. TANDAI YANG SUDAH LEWAT
        // =========================
        \App\Models\Meeting::whereDate('tanggal', '<', now()->toDateString())
            ->update([
                'status' => 'finished'
            ]);

        // =========================
        // 📋 5. AMBIL DATA LOCAL (TANPA MISSING)
        // =========================
        $localMeetings = \App\Models\Meeting::where('status', '!=', 'missing_in_zoom')->where('status', '!=', 'finished')->get();

        // ambil zoom_meeting_id yang valid
        $localZoomIds = $localMeetings
            ->whereNotNull('zoom_meeting_id')
            ->pluck('zoom_meeting_id')
            ->toArray();

        // =========================
        // 🟢 6. FORMAT LOCAL
        // =========================
        $local = collect($localMeetings)->map(function ($m) {
            return [
                'id' => $m->id, // 🔥 WAJIB

                'topic' => $m->topic,
                'tanggal' => $m->tanggal,
                'jam_mulai' => $m->jam_mulai,
                'duration' => $m->duration,
                'nama_pemesan' => $m->nama_pemesan,
                'unit' => $m->unit,
                'no_hp' => $m->no_hp,
                'status' => $m->zoom_meeting_id ? 'local_zoom' : 'local'
            ];
        });

        // =========================
        // 🔵 7. FORMAT ZOOM (FIX TIMEZONE)
        // =========================
        $zoom = collect($zoomMeetings)
            ->filter(function ($z) use ($localZoomIds) {
                return !in_array($z['id'], $localZoomIds);
            })
            ->map(function ($z) {

                $start = \Carbon\Carbon::parse($z['start_time'])
                    ->setTimezone('Asia/Makassar');

                return [
                    'id' => null, // 🔥 penting
                    'zoom_meeting_id' => $z['id'],
                    'topic' => $z['topic'],
                    'tanggal' => $start->format('Y-m-d'),
                    'jam_mulai' => $start->format('H:i'),
                    'duration' => $z['duration'],
                    'nama_pemesan' => 'Zoom User',
                    'unit' => '-',
                    'no_hp' => '-',
                    'status' => 'zoom_only'
                ];
            });

        // =========================
        // 🔥 8. MERGE + SORT
        // =========================
        $all = collect($local)
            ->merge($zoom)
            ->sortBy(function ($item) {
                return $item['tanggal'] . ' ' . $item['jam_mulai'];
            })
            ->values();

        return response()->json([
            'data' => $all
        ]);
    }
    public function getJadwal()
    {
        $zoomMeetings = collect($this->getZoomMeetings())
            ->filter(function ($z) {
                return Carbon::parse($z['start_time'])->isFuture();
            });

        $zoomIds = $zoomMeetings->pluck('id')->toArray();

        // 🧹 bersihkan lokal
        Meeting::whereNotNull('zoom_meeting_id')
            ->whereNotIn('zoom_meeting_id', $zoomIds)
            ->delete();

        // 📋 ambil lokal
        $localMeetings = Meeting::get()->map(function ($m) {
            return [
                'tanggal' => $m->tanggal,
                'jam_mulai' => $m->jam_mulai,
                'duration' => $m->duration,
                'nama_pemesan' => $m->nama_pemesan,
                'source' => 'local'
            ];
        });

        // 📋 ambil zoom
        $zoomData = $zoomMeetings->map(function ($z) {
            return [
                'tanggal' => Carbon::parse($z['start_time'])->format('Y-m-d'),
                'jam_mulai' => Carbon::parse($z['start_time'])->format('H:i'),
                'duration' => $z['duration'],
                'nama_pemesan' => 'Zoom User',
                'source' => 'zoom'
            ];
        });

        // 🔥 gabungkan
        $all = $localMeetings->merge($zoomData)
            ->sortBy(['tanggal', 'jam_mulai'])
            ->values();

        return response()->json([
            'data' => $all
        ]);
    }
    // ===============================
    // 🔍 CEK JADWAL (BENTROK / TIDAK)
    // ===============================
    public function check(Request $request)
    {
        $tanggal = $request->tanggal;
        $start = Carbon::parse($tanggal . ' ' . $request->jam_mulai);
        $end = (clone $start)->addMinutes($request->duration);

        $meetings = Meeting::where('tanggal', $tanggal)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->get();
        foreach ($meetings as $m) {
            $m_start = Carbon::parse($m->tanggal . ' ' . $m->jam_mulai);
            $m_end = (clone $m_start)->addMinutes($m->duration);

            // 🔥 CEK OVERLAP SAJA
            if ($start < $m_end && $end > $m_start) {

                return response()->json([
                    'status' => 'conflict',
                    'source' => 'local',
                    'data' => [
                        'nama_pemesan' => $m->nama_pemesan,
                        'unit' => $m->unit,
                        'topic' => $m->topic,
                        'tanggal' => $m->tanggal,
                        'jam_mulai' => $m->jam_mulai,
                        'duration' => $m->duration,
                        'no_hp' => $m->no_hp
                    ]
                ]);
            }
        }
        $zoomMeetings = collect($this->getZoomMeetings())
            ->filter(function ($z) {
                return Carbon::parse($z['start_time'])->isFuture();
            });
        foreach ($zoomMeetings as $z) {

            $z_start = Carbon::parse($z['start_time']);
            $z_end = (clone $z_start)->addMinutes($z['duration']);

            // 🔥 CEK OVERLAP
            if ($start < $z_end && $end > $z_start) {

                return response()->json([
                    'status' => 'conflict',
                    'source' => 'zoom',
                    'data' => [
                        'nama_pemesan' => 'Zoom User',
                        'no_hp' => '-'
                    ]
                ]);
            }
        }
        return response()->json([
            'status' => 'available'
        ]);
    }

    // ===============================
    // ➕ CREATE MEETING
    // ===============================
    public function store(Request $request)
    {
        $request->validate([
            'topic' => 'required',
            'tanggal' => 'required',
            'jam_mulai' => 'required',
            'duration' => 'required|integer',
            'nama_pemesan' => 'required',
            'unit' => 'required',
            'no_hp' => 'required'
        ]);

        $token = $this->generateToken();

        $start = Carbon::createFromFormat(
            'Y-m-d H:i', // 🔥 TANPA detik
            $request->tanggal . ' ' . $request->jam_mulai,
            'Asia/Makassar'
        );
        $start_time = $start->format('Y-m-d\TH:i:s');

        $response = Http::withToken($token)
            ->post('https://api.zoom.us/v2/users/me/meetings', [
                "topic" => $request->topic,
                "type" => 2,
                "start_time" => $start_time,
                "duration" => $request->duration,
                "timezone" => "Asia/Makassar"
            ]);

        $data = $response->json();

        $meeting = Meeting::create([
            'topic' => $data['topic'],
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'duration' => $data['duration'],
            'nama_pemesan' => $request->nama_pemesan,
            'unit' => $request->unit,
            'no_hp' => $request->no_hp,
            'join_url' => $data['join_url'],
            'password' => $data['password'] ?? null,
            'zoom_meeting_id' => $data['id'], // 🔥 INI YANG BENAR
        ]);

        return response()->json($meeting);
    }

    // ===============================
    // 🔎 CARI BERDASARKAN NO HP
    // ===============================
    public function search(Request $request)
    {
        $meetings = Meeting::where('no_hp', $request->no_hp)
            ->whereNotIn('status', ['missing_in_zoom', 'finished', 'deleted'])
            ->orderBy('tanggal', 'desc')
            ->get();
        // $meetings = Meeting::all();
        // return $request->no_hp;

        $data = $meetings->map(function ($m) {
            return [
                'id' => $m->id, // 🔥 penting untuk edit
                'topic' => $m->topic,
                'tanggal' => $m->tanggal,
                'jam_mulai' => $m->jam_mulai,
                'duration' => $m->duration,
                'nama_pemesan' => $m->nama_pemesan,
                'unit' => $m->unit,
                'no_hp' => $m->no_hp,
                'join_url' => $m->join_url,
                'password' => $m->password,
                'zoom_meeting_id' => $m->zoom_meeting_id,
                'status' => $m->zoom_meeting_id ? 'local_zoom' : 'local'
            ];
        });

        return response()->json([
            'data' => $data
        ]);
    }

    private function getZoomMeetings()
    {
        $token = $this->generateToken();

        $response = Http::withToken($token)
            ->get('https://api.zoom.us/v2/users/me/meetings', [
                'type' => 'scheduled'
            ]);

        return $response->json()['meetings'] ?? [];
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'tanggal' => 'required',
            'jam_mulai' => 'required',
            'duration' => 'required|integer',
            'no_hp' => 'required'
        ]);

        $meeting = Meeting::findOrFail($request->id);

        // 🔐 VALIDASI PEMILIK
        if ($meeting->no_hp !== $request->no_hp) {
            return response()->json([
                'message' => 'Nomor HP tidak sesuai'
            ], 403);
        }

        // 🔥 FORMAT WAKTU
        $start = \Carbon\Carbon::parse(
            $request->tanggal . ' ' . $request->jam_mulai,
            'Asia/Makassar'
        );

        $start_time = $start->format('Y-m-d\TH:i:s');

        // =========================
        // 🔍 CEK BENTROK (optional tapi disarankan)
        // =========================
        $check = $this->check($request)->getData(true);

        if ($check['status'] === 'conflict') {
            return response()->json([
                'message' => 'Jadwal bentrok'
            ], 422);
        }

        // =========================
        // 🔥 UPDATE KE ZOOM
        // =========================
        if ($meeting->zoom_meeting_id) {
            Http::withToken($this->generateToken())
                ->patch("https://api.zoom.us/v2/meetings/{$meeting->zoom_meeting_id}", [
                    "topic" => $request->topic,
                    "start_time" => $start_time,
                    "duration" => $request->duration,
                    "timezone" => "Asia/Makassar"
                ]);
        }

        // =========================
        // 💾 UPDATE DB
        // =========================
        $meeting->update([
            'topic' => $request->topic,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'duration' => $request->duration,
            'nama_pemesan' => $request->nama_pemesan,
            'unit' => $request->unit,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'status' => 'success'
        ]);
    }
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'no_hp' => 'required'
        ]);

        $meeting = Meeting::findOrFail($request->id);

        // 🔐 validasi pemilik
        if ($meeting->no_hp !== $request->no_hp) {
            return response()->json([
                'message' => 'Tidak diizinkan'
            ], 403);
        }

        // ❌ tidak boleh hapus kalau sudah selesai / ongoing
        if (in_array($meeting->status, ['finished', 'ongoing'])) {
            return response()->json([
                'message' => 'Meeting sudah berlangsung / selesai'
            ], 422);
        }

        // =========================
        // 🔥 DELETE DI ZOOM
        // =========================
        if ($meeting->zoom_meeting_id) {
            Http::withToken($this->generateToken())
                ->delete("https://api.zoom.us/v2/meetings/{$meeting->zoom_meeting_id}");
        }

        // =========================
        // 💾 UPDATE STATUS DB
        // =========================
        $meeting->update([
            'status' => 'deleted'
        ]);

        return response()->json([
            'status' => 'success'
        ]);
    }
}
