<?php

namespace App\Http\Controllers;

use App\Models\Aplikasi;
use App\Models\AppDatabaseDoc;
use App\Models\AppIntegration;
use App\Models\ApplicationDocument;
use App\Models\BackupJob;
use App\Models\BackupMedia;
use App\Models\ConsumerNetworkCredential;
use App\Models\ConsumerNetworkDevice;
use App\Models\ConsumerNetworkInstallation;
use App\Models\ConsumerNetworkIpConfig;
use App\Models\ConsumerNetworkMonitoring;
use App\Models\ConsumerNetworkSite;
use App\Models\DataAsset;
use App\Models\DataCenter;
use App\Models\IpAddress;
use App\Models\Isp;
use App\Models\Rack;
use App\Models\Server;
use App\Models\SocTool;
use App\Models\UpsDevice;
use App\Models\VirtualMachine;
use Illuminate\Contracts\View\View;

class PublicAssetController extends Controller
{
    public function __invoke(string $module, string $id): View
    {
        abort_unless(array_key_exists($module, $this->modules()), 404);

        $asset = $this->publicAsset($module, $id);

        return view('public-asset', [
            'asset' => $asset,
            'logoDataUri' => $this->logoDataUri(),
        ]);
    }

    private function publicAsset(string $module, string $id): array
    {
        $query = match ($module) {
            'data-centers' => DataCenter::query()->withCount('racks'),
            'racks' => Rack::query()->with('dataCenter:id,nama,lokasi'),
            'servers' => Server::query()->with(['dataCenter:id,nama,lokasi', 'rack:id,nama']),
            'vms' => VirtualMachine::query()->with('server:id,nama,asset_code'),
            'isps' => Isp::query()->withCount('ipAddresses'),
            'ip-addresses' => IpAddress::query()->with(['isp:id,nama,asset_code', 'vms:id,nama,status']),
            'applications' => Aplikasi::query()->with('opd:id,nama'),
            'data-assets' => DataAsset::query()->with(['aplikasi:id,nama,asset_code', 'classification:id,code,name,risk_level']),
            'application-documents' => ApplicationDocument::query()->with('aplikasi:id,nama,asset_code'),
            'app-integrations' => AppIntegration::query()->with(['aplikasi:id,nama,asset_code', 'targetApplications:id,nama', 'dataAssets:id,name']),
            'app-database-docs' => AppDatabaseDoc::query()->with('aplikasi:id,nama,asset_code'),
            'backup-media' => BackupMedia::query()->withCount('backupJobs'),
            'backup-jobs' => BackupJob::query()->with(['aplikasi:id,nama,asset_code', 'media:id,nama,asset_code']),
            'ups-devices' => UpsDevice::query()->with('dataCenter:id,nama,lokasi'),
            'soc-tools' => SocTool::query()->with(['dataCenters:id,nama', 'servers:id,nama', 'vms:id,nama', 'applications:id,nama']),
            'network-sites' => ConsumerNetworkSite::query()->with(['dataCenter:id,nama,lokasi', 'rack:id,nama', 'opd:id,nama']),
            'network-devices' => ConsumerNetworkDevice::query()->with(['dataCenter:id,nama,lokasi', 'rack:id,nama', 'opd:id,nama', 'upstreamDevice:id,nama', 'activeInstallation.site:id,nama,kode,asset_code,opd_id', 'activeInstallation.site.opd:id,nama']),
            'network-installations' => ConsumerNetworkInstallation::query()->with(['site:id,nama,kode,asset_code,opd_id', 'site.opd:id,nama', 'device:id,nama,jenis,asset_code', 'replacementDevice:id,nama,jenis,asset_code']),
            'network-ip-configs' => ConsumerNetworkIpConfig::query()->with(['device:id,nama,jenis,asset_code', 'site:id,nama,kode,asset_code', 'ipAddressRecord:id,ip,jenis']),
            'network-credentials' => ConsumerNetworkCredential::query()->with(['device:id,nama,jenis,asset_code', 'site:id,nama,kode,asset_code']),
            'network-monitorings' => ConsumerNetworkMonitoring::query()->with(['site:id,nama,kode,asset_code,alamat,lokasi_detail,opd_id', 'site.opd:id,nama', 'opd:id,nama', 'items.device:id,nama,jenis,asset_code', 'items.installation:id,site_id,device_id,role,status,installed_at', 'items.installation.site:id,nama,kode,asset_code', 'attachments:id,monitoring_id,original_name,mime_type,size_bytes']),
        };

        $row = $query->findOrFail($id);

        return [
            'module' => $module,
            'module_label' => $this->modules()[$module],
            'asset_code' => $row->asset_code ?? $this->fallbackCode($module, $row),
            'name' => $this->nameFor($module, $row),
            'status' => $this->statusFor($module, $row),
            'location' => $this->locationFor($module, $row),
            'details' => $this->detailsFor($module, $row),
        ];
    }

    private function modules(): array
    {
        return [
            'data-centers' => 'Data Center',
            'racks' => 'Rack',
            'servers' => 'Server',
            'vms' => 'VM / CT',
            'isps' => 'ISP',
            'ip-addresses' => 'IP Address',
            'applications' => 'Aplikasi',
            'data-assets' => 'Data Aplikasi',
            'application-documents' => 'Dokumen',
            'app-integrations' => 'Interoperabilitas',
            'app-database-docs' => 'Dok. Basis Data',
            'backup-media' => 'Media Pencadangan',
            'backup-jobs' => 'Pencadangan',
            'ups-devices' => 'UPS / Power Backup',
            'soc-tools' => 'SOC',
            'network-sites' => 'Site / Node Jaringan',
            'network-devices' => 'Consumer Networking',
            'network-installations' => 'Instalasi & Pergantian Jaringan',
            'network-ip-configs' => 'Konfigurasi IP Jaringan',
            'network-credentials' => 'Kredensial Jaringan',
            'network-monitorings' => 'Monitoring Site Jaringan',
        ];
    }

    private function nameFor(string $module, object $row): string
    {
        return match ($module) {
            'ip-addresses' => $row->ip ?? '-',
            'data-assets' => $row->name ?? '-',
            'application-documents' => $row->original_name ?? $row->nama ?? '-',
            'app-integrations' => 'Integrasi '.$row->aplikasi?->nama,
            'app-database-docs' => $row->nama_database ?? '-',
            'backup-jobs' => 'Backup '.$row->aplikasi?->nama,
            'network-installations' => collect([$row->device?->nama, $row->site?->nama])->filter()->join(' @ ') ?: '-',
            'network-ip-configs' => collect([$row->device?->nama, $row->ip_address ?: ($row->ipAddressRecord?->ip)])->filter()->join(' / ') ?: '-',
            'network-credentials' => collect([$row->label, $row->device?->nama])->filter()->join(' / ') ?: '-',
            'network-monitorings' => collect(['Monitoring', $row->site?->nama ?: ($row->opd?->nama ? 'Semua Site/Node - '.$row->opd->nama : 'Semua Site/Node'), optional($row->monitoring_at)->format('Y-m-d H:i')])->filter()->join(' / ') ?: '-',
            default => $row->nama ?? '-',
        };
    }

    private function statusFor(string $module, object $row): string
    {
        return match ($module) {
            'data-centers' => $row->tipe ?? '-',
            'racks' => trim(($row->kapasitas_u ?? '-').'U'),
            'servers', 'vms', 'applications' => $row->status ?? '-',
            'isps' => $row->tipe ?? '-',
            'ip-addresses' => $row->jenis ?? '-',
            'data-assets' => $row->classification?->name ?? '-',
            'application-documents' => $row->document_category ?? $row->jenis ?? '-',
            'app-integrations' => trim(($row->jenis_integrasi ?? '-').' / '.($row->metode_integrasi ?? '-')),
            'app-database-docs' => $row->tipe_dbms ?? '-',
            'backup-media' => trim(($row->jenis_media ?? '-').' / '.($row->location ?? '-')),
            'backup-jobs' => trim(($row->repetisi_n ?? '-').' '.($row->repetisi_unit ?? '')),
            'ups-devices' => $row->kondisi ?? '-',
            'soc-tools' => $row->jenis ?? '-',
            'network-sites' => trim(($row->jenis ?? '-').' / '.($row->status ?? '-')),
            'network-devices' => trim(($row->jenis ?? '-').' / '.($row->status ?? '-')),
            'network-installations' => trim(($row->role ?? '-').' / '.($row->status ?? '-')),
            'network-ip-configs' => trim(($row->ip_type ?? '-').' / '.($row->status ?? '-')),
            'network-credentials' => trim(($row->access_method ?? '-').' / '.($row->has_password ? 'Password tersimpan' : 'Tanpa password')),
            'network-monitorings' => $row->period_month ?? optional($row->monitoring_at)->format('Y-m') ?? '-',
            default => '-',
        };
    }

    private function locationFor(string $module, object $row): string
    {
        return match ($module) {
            'data-centers' => $row->lokasi ?? '-',
            'racks' => collect([$row->dataCenter?->nama, $row->dataCenter?->lokasi])->filter()->join(' / ') ?: '-',
            'servers' => collect([$row->dataCenter?->nama, $row->rack?->nama])->filter()->join(' / ') ?: '-',
            'vms' => $row->server?->nama ?? '-',
            'isps' => $row->bandwidth ?? '-',
            'ip-addresses' => $row->isp?->nama ?? '-',
            'applications' => $row->opd?->nama ?? '-',
            'data-assets' => $row->aplikasi?->nama ?? '-',
            'application-documents' => $row->aplikasi?->nama ?? '-',
            'app-integrations' => $row->targetApplications->pluck('nama')->join(', ') ?: '-',
            'app-database-docs' => $row->aplikasi?->nama ?? '-',
            'backup-media' => $row->location ?? '-',
            'backup-jobs' => $row->media?->nama ?? '-',
            'ups-devices' => collect([$row->dataCenter?->nama, $row->dataCenter?->lokasi])->filter()->join(' / ') ?: '-',
            'soc-tools' => 'DC '.$row->dataCenters->count().' / Server '.$row->servers->count().' / VM '.$row->vms->count().' / Aplikasi '.$row->applications->count(),
            'network-sites' => collect([$row->dataCenter?->nama, $row->rack?->nama, $row->opd?->nama, $row->lokasi_detail, $row->alamat])->filter()->join(' / ') ?: '-',
            'network-devices' => collect([$row->dataCenter?->nama, $row->rack?->nama, $row->opd?->nama, $row->lokasi_instalasi])->filter()->join(' / ') ?: '-',
            'network-installations' => $row->site?->nama ?? '-',
            'network-ip-configs' => $row->site?->nama ?? $row->device?->nama ?? '-',
            'network-credentials' => $row->site?->nama ?? $row->device?->nama ?? '-',
            'network-monitorings' => collect([$row->site?->nama, $row->site?->lokasi_detail, $row->site?->alamat, $row->opd?->nama])->filter()->join(' / ') ?: '-',
            default => '-',
        };
    }

    private function detailsFor(string $module, object $row): array
    {
        return match ($module) {
            'servers' => [
                'Merk / Tipe' => trim(($row->merk ?? '-').' '.($row->tipe ?? '')),
                'Processor' => $row->merk_processor ?? '-',
                'Kapasitas' => ($row->cpu_core ?? 0).' core / '.($row->ram_gb ?? 0).' GB RAM / '.($row->storage_gb ?? 0).' GB storage',
            ],
            'vms' => [
                'Host' => $row->server?->nama ?? '-',
                'OS' => $row->os ?? '-',
                'Kapasitas' => ($row->vcpu ?? 0).' vCPU / '.($row->ram_gb ?? 0).' GB RAM / '.($row->storage_gb ?? 0).' GB storage',
            ],
            'applications' => [
                'Jenis' => $row->jenis_aplikasi ?? '-',
                'Pengembang' => $row->pengembang ?? '-',
            ],
            'data-assets' => [
                'Tipe Data' => $row->type ?? '-',
                'Klasifikasi' => $row->classification?->code ?? '-',
                'Aplikasi' => $row->aplikasi?->nama ?? '-',
            ],
            'ip-addresses' => [
                'Jenis' => $row->jenis ?? '-',
                'Assignment' => $row->assignment ?? '-',
                'ISP' => $row->isp?->nama ?? '-',
                'Ping Terakhir' => collect([
                    $row->ping_status ?? 'unknown',
                    filled($row->ping_latency_ms) ? $row->ping_latency_ms.' ms' : null,
                    optional($row->ping_checked_at)->format('Y-m-d H:i'),
                ])->filter()->join(' / ') ?: '-',
                'VM Terkait' => $row->vms->pluck('nama')->join(', ') ?: '-',
            ],
            'backup-jobs' => [
                'Retensi' => ($row->retensi_n ?? '-').' '.($row->retensi_unit ?? ''),
                'Repetisi' => ($row->repetisi_n ?? '-').' '.($row->repetisi_unit ?? ''),
                'Media' => $row->media?->nama ?? '-',
            ],
            'network-devices' => [
                'Merk / Model' => trim(($row->merk ?? '-').' '.($row->model ?? '')),
                'Firmware' => $row->os_firmware ?? '-',
                'Site Aktif' => $row->activeInstallation?->site?->nama ?? '-',
                'Uplink' => $row->upstreamDevice?->nama ?? '-',
                'Catatan / Deskripsi' => $row->deskripsi ?? '-',
            ],
            'network-sites' => [
                'Kode Site' => $row->kode ?? '-',
                'PIC' => collect([$row->pic_nama, $row->pic_kontak])->filter()->join(' / ') ?: '-',
                'Koordinat' => $row->titik_koordinat ?? '-',
                'Catatan' => $row->catatan ?? '-',
            ],
            'network-installations' => [
                'Perangkat' => $row->device?->nama ?? '-',
                'Site / Node' => $row->site?->nama ?: ($row->opd?->nama ? 'Semua Site/Node - '.$row->opd->nama : 'Semua Site/Node'),
                'Tanggal Pasang' => optional($row->installed_at)->format('Y-m-d H:i') ?? '-',
                'Tanggal Lepas' => optional($row->removed_at)->format('Y-m-d H:i') ?? '-',
                'Perangkat Pengganti' => $row->replacementDevice?->nama ?? '-',
                'Catatan' => $row->notes ?? '-',
            ],
            'network-ip-configs' => [
                'Perangkat' => $row->device?->nama ?? '-',
                'Site / Node' => $row->site?->nama ?? '-',
                'Interface' => $row->interface_name ?? '-',
                'IP Address' => $row->ip_address ?: ($row->ipAddressRecord?->ip ?? '-'),
                'Gateway' => $row->gateway ?? '-',
                'VLAN / SSID' => collect([$row->vlan, $row->ssid])->filter()->join(' / ') ?: '-',
                'Catatan' => $row->notes ?? '-',
            ],
            'network-credentials' => [
                'Perangkat' => $row->device?->nama ?? '-',
                'Site / Node' => $row->site?->nama ?? '-',
                'Metode Akses' => $row->access_method ?? '-',
                'Username' => $row->username ?? '-',
                'Password' => $row->has_password ? 'Tersimpan, tidak ditampilkan pada halaman publik' : 'Belum ada',
                'Catatan' => $row->notes ?? '-',
            ],
            'network-monitorings' => [
                'Site / Node' => $row->site?->nama ?? '-',
                'Tanggal Pemantauan' => optional($row->monitoring_at)->format('Y-m-d H:i') ?? '-',
                'Petugas' => collect($row->officers ?? [])->join(', ') ?: '-',
                'Speedtest' => collect([
                    filled($row->speedtest_download_mbps) ? 'Down '.$row->speedtest_download_mbps.' Mbps' : null,
                    filled($row->speedtest_upload_mbps) ? 'Up '.$row->speedtest_upload_mbps.' Mbps' : null,
                    filled($row->speedtest_ping_ms) ? 'Ping '.$row->speedtest_ping_ms.' ms' : null,
                ])->filter()->join(' / ') ?: '-',
                'Kondisi Menara' => $row->tower_available
                    ? collect([
                        'Besi: '.($row->tower_besi_condition ?? '-'),
                        'Kawat: '.($row->tower_kawat_condition ?? '-'),
                        'Pondasi: '.($row->tower_pondasi_condition ?? '-'),
                    ])->join(' / ')
                    : 'Tidak ada / tidak diperiksa',
                'Checklist Perangkat' => $row->items->map(fn ($item) => collect([$item->device?->nama ?? '-', $item->installation?->site?->nama])->filter()->join(' @ ').' - '.$item->condition.($item->note ? ' ('.$item->note.')' : ''))->join('; ') ?: '-',
                'Lampiran' => $row->attachments->pluck('original_name')->join(', ') ?: '-',
                'Catatan' => $row->notes ?? '-',
            ],
            default => [],
        };
    }

    private function fallbackCode(string $module, object $row): string
    {
        return strtoupper($module).'-'.strtoupper(substr((string) $row->id, 0, 8));
    }

    private function logoDataUri(): ?string
    {
        $path = resource_path('img/logo_langkat.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
