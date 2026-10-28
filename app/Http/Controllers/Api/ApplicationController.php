<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aplikasi;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index()
    {
        return Aplikasi::with(['opd:id,nama', 'vms:id,nama,status', 'servers:id,nama,status', 'ipAddresses:id,ip,jenis', 'dataAssets:id,aplikasi_id,classification_id,name,type', 'documents:id,aplikasi_id,document_category'])
            ->orderBy('nama')
            ->get();
    }

    public function statsBeranda()
    {
        return Aplikasi::with(['opd:id,nama'])
            ->orderBy('nama')
            ->get();
    }

    public function show(Aplikasi $application)
    {
        return $application->load($this->relations());
    }

    public function store(Request $request)
    {
        $data = $this->modelData($this->validated($request));
        $app = Aplikasi::create($data);
        $this->syncRelations($app, $request);
        $this->audit('create', $app, null, $app->fresh($this->relations())->toArray(), $request);

        return response()->json($app->fresh($this->relations()), 201);
    }

    public function update(Request $request, Aplikasi $application)
    {
        $before = $application->load($this->relations())->toArray();
        $application->update($this->modelData($this->validated($request)));
        $this->syncRelations($application, $request);
        $this->audit('update', $application, $before, $application->fresh($this->relations())->toArray(), $request);

        return $application->fresh($this->relations());
    }

    public function destroy(Request $request, Aplikasi $application)
    {
        $before = $application->toArray();
        $application->delete();
        $this->audit('delete', $application, $before, null, $request);

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'opd_id' => ['nullable', 'uuid', 'exists:opd,id'],
            'deskripsi' => ['nullable', 'string'],
            'jenis_aplikasi' => ['required', 'in:web,mobile,desktop,service,lainnya'],
            'pengembang' => ['nullable', 'in:instansi_pusat,diskominfo_langkat,unit_penyelenggara,pihak_ketiga,in_house'],
            'klasifikasi_fungsi' => ['array'],
            'klasifikasi_fungsi.*' => ['string', 'in:layanan_publik,layanan_internal,tools_pendukung,platform_integrasi,low_code_no_code,monitoring_observability,security_tools,kolaborasi_knowledge_base'],
            'tech_stack' => ['nullable', 'string'],
            'status' => ['nullable', 'in:aktif,nonaktif,maintenance'],
            'sla_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'jam_operasional' => ['nullable', 'string', 'max:100'],
            'kategori_data' => ['nullable', 'string', 'max:50'],
            'mengandung_data_pribadi' => ['boolean'],
            'jenis_data_pribadi' => ['nullable', 'string'],
            'retensi_data' => ['nullable', 'string', 'max:100'],
            'pic_nama' => ['nullable', 'string', 'max:255'],
            'pic_kontak' => ['nullable', 'string', 'max:100'],
            'tanggal_go_live' => ['nullable', 'date'],
            'vm_ids' => ['array'],
            'vm_ids.*' => ['uuid', 'exists:vm,id'],
            'server_ids' => ['array'],
            'server_ids.*' => ['uuid', 'exists:server,id'],
            'ip_ids' => ['array'],
            'ip_ids.*' => ['uuid', 'exists:ip_address,id'],
        ]);
    }

    private function syncRelations(Aplikasi $app, Request $request): void
    {
        if ($request->has('vm_ids')) {
            $app->vms()->sync($request->array('vm_ids'));
        }

        if ($request->has('server_ids')) {
            $app->servers()->sync($request->array('server_ids'));
        }

        if ($request->has('ip_ids')) {
            $app->ipAddresses()->sync($request->array('ip_ids'));
        }
    }

    private function relations(): array
    {
        return ['opd:id,nama', 'vms:id,nama,status', 'servers:id,nama,status', 'ipAddresses:id,ip,jenis', 'dataAssets:id,aplikasi_id,classification_id,name,type', 'documents:id,aplikasi_id,document_category'];
    }

    private function modelData(array $data): array
    {
        unset($data['vm_ids'], $data['server_ids'], $data['ip_ids']);

        return $data;
    }

    private function audit(string $action, Aplikasi $app, ?array $before, ?array $after, Request $request): void
    {
        AuditLog::create([
            'user_id' => $request->attributes->get('auth_user')?->id,
            'aksi' => $action,
            'tabel' => 'aplikasi',
            'record_id' => $app->id,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
