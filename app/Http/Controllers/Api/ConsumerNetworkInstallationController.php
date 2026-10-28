<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ConsumerNetworkInstallation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ConsumerNetworkInstallationController extends Controller
{
    public function index()
    {
        return ConsumerNetworkInstallation::with($this->relations())
            ->orderByDesc('installed_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function show(ConsumerNetworkInstallation $networkInstallation)
    {
        return $networkInstallation->load($this->relations());
    }

    /**
     * GET /api/network-installations/stats
     *
     * Statistik kecepatan tindak lanjut laporan oleh petugas.
     * Waktu penanganan = installed_at - reported_at.
     * Riwayat tanpa reported_at (pemasangan rutin) tidak ikut dihitung.
     *
     * Query opsional:
     *   ?from=2026-10-01&to=2026-10-31  -> rentang tanggal laporan masuk (reported_at)
     *   ?opd_id=<uuid>                   -> filter OPD (lewat site)
     *   ?site_id=<uuid>                  -> filter site / node
     *   ?target_hours=24                 -> target penyelesaian (default 24 jam)
     */
    public function stats(Request $request): JsonResponse
    {
        $toRules = ['nullable', 'date'];
        if ($request->filled('from')) {
            $toRules[] = 'after_or_equal:from';
        }

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => $toRules,
            'opd_id' => ['nullable', 'uuid'],
            'site_id' => ['nullable', 'uuid'],
            'target_hours' => ['nullable', 'numeric', 'min:1', 'max:8760'],
        ]);

        $targetMinutes = (int) round(((float) ($filters['target_hours'] ?? 24)) * 60);

        $rows = ConsumerNetworkInstallation::query()
            ->with(['site:id,nama,opd_id', 'site.opd:id,nama'])
            ->whereNotNull('reported_at')
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('reported_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('reported_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($filters['site_id'] ?? null, fn ($q, $siteId) => $q->where('site_id', $siteId))
            ->when($filters['opd_id'] ?? null, fn ($q, $opdId) => $q->whereHas('site', fn ($site) => $site->where('opd_id', $opdId)))
            ->orderBy('reported_at')
            ->get();

        // Laporan yang sudah ditangani (punya waktu pasang yang valid) + durasinya dalam menit.
        $handled = $rows
            ->filter(fn ($row) => $row->installed_at && $row->installed_at->greaterThanOrEqualTo($row->reported_at))
            ->map(fn ($row) => [
                'row' => $row,
                'minutes' => (int) $row->reported_at->diffInMinutes($row->installed_at),
            ])
            ->values();

        $pending = $rows->filter(fn ($row) => ! $row->installed_at)->count();
        $invalid = $rows->count() - $handled->count() - $pending;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $handled->isEmpty()
                ? 'Belum ada laporan yang selesai ditangani pada filter ini.'
                : 'Statistik kecepatan tindak lanjut berhasil diambil.',
            'filter' => [
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'opd_id' => $filters['opd_id'] ?? null,
                'site_id' => $filters['site_id'] ?? null,
                'target_hours' => round($targetMinutes / 60, 2),
            ],
            'data' => [
                'summary' => array_merge([
                    'total_laporan' => $rows->count(),
                    'selesai' => $handled->count(),
                    'belum_selesai' => $pending,
                    'data_tidak_valid' => $invalid,
                ], $this->durationSummary($handled, $targetMinutes)),
                'per_petugas' => $this->groupDurations(
                    $handled,
                    fn ($item) => trim((string) $item['row']->installed_by) ?: 'Tanpa nama petugas',
                    'petugas',
                    $targetMinutes,
                ),
                'per_opd' => $this->groupDurations(
                    $handled,
                    fn ($item) => $item['row']->site?->opd?->nama ?? 'Tanpa OPD',
                    'opd',
                    $targetMinutes,
                ),
                'per_bulan' => $this->groupDurations(
                    $handled,
                    fn ($item) => $item['row']->reported_at->format('Y-m'),
                    'bulan',
                    $targetMinutes,
                    sortByKey: true,
                ),
            ],
        ], 200);
    }

    /**
     * Ringkasan durasi: rata-rata, median, tercepat, terlama, % sesuai target.
     */
    private function durationSummary(Collection $handled, int $targetMinutes): array
    {
        $minutes = $handled->pluck('minutes')->sort()->values();

        if ($minutes->isEmpty()) {
            return [
                'rata_rata_menit' => null,
                'rata_rata_teks' => '-',
                'median_menit' => null,
                'median_teks' => '-',
                'tercepat_menit' => null,
                'tercepat_teks' => '-',
                'terlama_menit' => null,
                'terlama_teks' => '-',
                'sesuai_target' => 0,
                'persen_sesuai_target' => null,
            ];
        }

        $average = (int) round($minutes->avg());
        $median = (int) round($minutes->median());
        $withinTarget = $minutes->filter(fn ($value) => $value <= $targetMinutes)->count();

        return [
            'rata_rata_menit' => $average,
            'rata_rata_teks' => $this->durationText($average),
            'median_menit' => $median,
            'median_teks' => $this->durationText($median),
            'tercepat_menit' => $minutes->first(),
            'tercepat_teks' => $this->durationText($minutes->first()),
            'terlama_menit' => $minutes->last(),
            'terlama_teks' => $this->durationText($minutes->last()),
            'sesuai_target' => $withinTarget,
            'persen_sesuai_target' => round($withinTarget / $minutes->count() * 100, 1),
        ];
    }

    private function groupDurations(Collection $handled, callable $groupBy, string $label, int $targetMinutes, bool $sortByKey = false): array
    {
        $groups = $handled->groupBy($groupBy);
        $groups = $sortByKey ? $groups->sortKeys() : $groups;

        $result = $groups
            ->map(fn (Collection $items, $key) => array_merge(
                [$label => $key, 'selesai' => $items->count()],
                $this->durationSummary($items, $targetMinutes),
            ))
            ->values();

        // Selain per bulan: urutkan dari rata-rata tercepat.
        return ($sortByKey ? $result : $result->sortBy('rata_rata_menit')->values())->all();
    }

    /**
     * 330 -> "5 jam 30 menit", 1690 -> "1 hari 4 jam 10 menit".
     */
    private function durationText(int $minutes): string
    {
        if ($minutes < 1) {
            return 'kurang dari 1 menit';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        return collect([
            $days ? "{$days} hari" : null,
            $hours ? "{$hours} jam" : null,
            $mins ? "{$mins} menit" : null,
        ])->filter()->join(' ');
    }

    public function store(Request $request)
    {
        $installation = ConsumerNetworkInstallation::create($this->validated($request));
        $this->audit('create', $installation, null, $installation->toArray(), $request);

        return response()->json($installation->fresh($this->relations()), 201);
    }

    public function update(Request $request, ConsumerNetworkInstallation $networkInstallation)
    {
        $before = $networkInstallation->toArray();
        $networkInstallation->update($this->validated($request));
        $after = $networkInstallation->fresh()->toArray();
        $this->audit('update', $networkInstallation, $before, $after, $request);

        return $networkInstallation->fresh($this->relations());
    }

    public function destroy(Request $request, ConsumerNetworkInstallation $networkInstallation)
    {
        $before = $networkInstallation->toArray();
        $networkInstallation->delete();
        $this->audit('delete', $networkInstallation, $before, null, $request);

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        // Waktu laporan masuk (opsional). Kosong = pemasangan rutin, bukan tindak lanjut laporan.
        // Bila waktu pasang diisi, laporan tidak boleh setelah waktu pasang.
        $reportedAtRules = ['nullable', 'date'];
        if ($request->filled('installed_at')) {
            $reportedAtRules[] = 'before_or_equal:installed_at';
        }

        return $request->validate([
            'site_id' => ['required', 'uuid', 'exists:consumer_network_sites,id'],
            'device_id' => ['required', 'uuid', 'exists:consumer_network_devices,id'],
            'replaced_by_device_id' => ['nullable', 'uuid', 'exists:consumer_network_devices,id', 'different:device_id'],
            'role' => ['nullable', 'in:primary,backup,distribution,access,uplink,client,lainnya'],
            'status' => ['required', 'in:aktif,diganti,dilepas,rusak,maintenance'],
            'reported_at' => $reportedAtRules,
            'installed_at' => ['nullable', 'date'],
            'removed_at' => ['nullable', 'date', 'after_or_equal:installed_at'],
            'installed_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'reported_at.date' => 'Waktu laporan masuk tidak valid.',
            'reported_at.before_or_equal' => 'Waktu laporan masuk tidak boleh setelah waktu pemasangan.',
        ]);
    }

    private function relations(): array
    {
        return [
            'site:id,nama,kode,jenis,asset_code,opd_id',
            'site.opd:id,nama',
            'device:id,nama,jenis,asset_code',
            'replacementDevice:id,nama,jenis,asset_code',
        ];
    }

    private function audit(string $action, ConsumerNetworkInstallation $installation, ?array $before, ?array $after, Request $request): void
    {
        AuditLog::create([
            'user_id' => $request->attributes->get('auth_user')?->id,
            'aksi' => $action,
            'tabel' => 'consumer_network_installations',
            'record_id' => $installation->id,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
