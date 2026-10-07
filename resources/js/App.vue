<script setup>
import {
  Activity,
  AlertTriangle,
  AppWindow,
  Box,
  Building2,
  CheckCircle2,
  Copy,
  Database,
  Eye,
  FileCheck2,
  GitBranch,
  HardDrive,
  Key,
  LayoutDashboard,
  Menu,
  Network,
  Pencil,
  Plus,
  Printer,
  RefreshCw,
  Search,
  Server,
  ShieldCheck,
  Trash2,
  Users,
  X,
} from "lucide-vue-next";
import QRCode from "qrcode";
import {
  computed,
  nextTick,
  onMounted,
  onUpdated,
  reactive,
  ref,
  watch,
} from "vue";
import logoLangkat from "../img/logo_langkat.png";

const menuSections = [
  {
    items: [{ id: "dashboard", label: "Dashboard", icon: LayoutDashboard }],
  },
  {
    label: "Pusat Data / DC",
    items: [
      { id: "data-centers", label: "Gedung / Ruang DC", icon: Building2 },
      { id: "racks", label: "Rack", icon: Database },
      { id: "servers", label: "Server", icon: Server },
      { id: "vms", label: "VM / CT", icon: Box },
      { id: "isps", label: "ISP", icon: HardDrive },
      { id: "ip-addresses", label: "IP Addr", icon: Network },
      { id: "ups-devices", label: "UPS / Power Backup", icon: ShieldCheck },
    ],
  },
  {
    label: "Aplikasi",
    items: [
      { id: "applications", label: "Aplikasi", icon: AppWindow },
      { id: "application-documents", label: "Dokumen", icon: FileCheck2 },
      { id: "data-assets", label: "Klasifikasi Data", icon: FileCheck2 },
      { id: "app-integrations", label: "Interoperabilitas", icon: GitBranch },
      { id: "app-database-docs", label: "Dok. Basis Data", icon: Database },
    ],
  },
  {
    label: "Keamanan Informasi",
    items: [
      { id: "backup-media", label: "Media Pencadangan", icon: Database },
      { id: "backup-jobs", label: "Pencadangan", icon: HardDrive },
      { id: "soc-tools", label: "SOC", icon: ShieldCheck },
    ],
  },
  {
    label: "Consumer Networking",
    items: [
      { id: "network-sites", label: "Site / Node", icon: Building2 },
      {
        id: "network-monitorings",
        label: "Monitoring Site",
        icon: CheckCircle2,
      },
      { id: "network-devices", label: "Perangkat", icon: Network },
      {
        id: "network-installations",
        label: "Instalasi & Pergantian",
        icon: GitBranch,
      },
      { id: "network-ip-configs", label: "Konfigurasi IP", icon: Network },
      { id: "network-credentials", label: "Kredensial", icon: ShieldCheck },
      { id: "network-owner-summary", label: "Relasi OPD", icon: FileCheck2 },
    ],
  },
  {
    label: "Lainnya",
    items: [
      { id: "users", label: "Pengguna & Role", icon: Users },
      { id: "bulk-labels", label: "Cetak Label", icon: Printer },
      { id: "map", label: "Mapping", icon: GitBranch },
      { id: "compliance", label: "Compliance", icon: FileCheck2 },
      { id: "audit", label: "Audit", icon: Activity },
      { id: "api-keys", label: "API Keys", icon: Key },
    ],
  },
];

const activeTab = ref("dashboard");
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const pingingAllIps = ref(false);
const pingingIps = ref({});
const assetDeepLinkHandled = ref(false);
const error = ref("");
const query = ref("");
const networkDeviceOpdFilter = ref("");
const networkIpConfigOpdFilter = ref("");
const selectedServerId = ref("");
const mobileNavOpen = ref(false);
const authToken = ref(localStorage.getItem("iamt_token") || "");
const currentUser = ref(null);
const authForm = reactive({
  email: "",
  password: "",
});
const modal = reactive({
  open: false,
  module: "",
  mode: "create",
  id: null,
});
const alertModal = reactive({
  open: false,
  title: "",
  message: "",
});
const deleteModal = reactive({
  open: false,
  kind: "",
  id: null,
  label: "",
});
const revealPasswordModal = reactive({
  open: false,
  loading: false,
  credential: null,
  account_password: "",
  revealed_password: "",
  error: "",
  copied: false,
});
const labelModal = reactive({
  open: false,
  module: "",
  item: null,
  size: "60x40",
});
const monitoringReportModal = reactive({
  open: false,
  item: null,
});
const bulkLabelForm = reactive({
  module: "servers",
  size: "60x40",
});
const networkSummaryForm = reactive({
  opd_id: "",
});
const detailModal = reactive({
  open: false,
  loading: false,
  module: "",
  item: null,
});
const labelQrDataUrl = ref("");
const monitoringReportQrDataUrl = ref("");
const bulkLabelQrUrls = ref({});
const bulkLabelGenerating = ref(false);
const backdropPointerStartedOnSelf = ref(false);
const ALL_NETWORK_SITES_VALUE = "__all_sites__";

const dashboard = ref(null);
const references = ref({
  opd: [],
  classifications: [],
  data_centers: [],
  racks: [],
  isps: [],
  servers: [],
  vms: [],
  ips: [],
  backup_media: [],
  network_devices: [],
  network_sites: [],
});
const dataCenters = ref([]);
const racks = ref([]);
const isps = ref([]);
const ipAddresses = ref([]);
const servers = ref([]);
const vms = ref([]);
const applications = ref([]);
const dataAssets = ref([]);
const applicationDocuments = ref([]);
const appIntegrations = ref([]);
const appDatabaseDocs = ref([]);
const backupMedia = ref([]);
const backupJobs = ref([]);
const upsDevices = ref([]);
const socTools = ref([]);
const networkSites = ref([]);
const networkDevices = ref([]);
const networkInstallations = ref([]);
const networkIpConfigs = ref([]);
const networkCredentials = ref([]);
const networkMonitorings = ref([]);
const users = ref([]);
const apiKeys = ref([]);
const dependencyMap = ref([]);
const compliance = ref(null);
const auditLog = ref([]);
const assetChangeLogs = ref([]);
const impact = ref(null);

const canWrite = computed(() => Boolean(currentUser.value?.can_write));

const labelSizeOptions = [
  { value: "50x30", label: "50 x 30 mm", width: 50, height: 30 },
  { value: "60x40", label: "60 x 40 mm", width: 60, height: 40 },
  { value: "70x50", label: "70 x 50 mm", width: 70, height: 50 },
  { value: "90x50", label: "90 x 50 mm", width: 90, height: 50 },
];
const printableLabelModules = new Set([
  "data-centers",
  "racks",
  "servers",
  "vms",
  "isps",
  "ip-addresses",
  "applications",
  "data-assets",
  "application-documents",
  "app-integrations",
  "app-database-docs",
  "backup-media",
  "backup-jobs",
  "ups-devices",
  "soc-tools",
  "network-sites",
  "network-devices",
  "network-installations",
  "network-ip-configs",
  "network-credentials",
]);

const dataCenterForm = reactive({
  nama: "",
  lokasi: "",
  tipe: "",
});

const rackForm = reactive({
  dc_id: "",
  nama: "",
  kapasitas_u: null,
});

const ispForm = reactive({
  nama: "",
  tipe: "",
  bandwidth: "",
  kontak: "",
});

const ipAddressForm = reactive({
  ip: "",
  jenis: "",
  assignment: "",
  isp_id: "",
});

const serverForm = reactive({
  nama: "",
  dc_id: "",
  rack_id: "",
  rack_size_u: null,
  merk: "",
  tipe: "",
  merk_processor: "",
  tahun: null,
  cpu_core: null,
  ram_gb: null,
  storage_gb: null,
  kondisi: "",
  status: "",
  penanggung_jawab: "",
  change_reason: "",
  changed_by: "",
});

const vmForm = reactive({
  nama: "",
  server_id: "",
  os: "",
  vcpu: null,
  ram_gb: null,
  storage_gb: null,
  status: "",
  ip_ids: [],
  change_reason: "",
  changed_by: "",
});

const appForm = reactive({
  nama: "",
  url: "",
  opd_id: "",
  jenis_aplikasi: "",
  pengembang: "",
  klasifikasi_fungsi: [],
  tech_stack: "",
  status: "",
  sla_persen: null,
  kategori_data: "",
  pic_nama: "",
  pic_kontak: "",
  vm_ids: [],
  server_ids: [],
  ip_ids: [],
});

const applicationDocumentForm = reactive({
  aplikasi_id: "",
  document_category: "",
  files: [],
});

const appIntegrationForm = reactive({
  aplikasi_id: "",
  deskripsi: "",
  jenis_integrasi: "",
  metode_integrasi: "",
  target_application_ids: [],
  external_endpoints: "",
  data_asset_ids: [],
  documents: [],
});

const appDatabaseDocForm = reactive({
  aplikasi_id: "",
  nama_database: "",
  tipe_dbms: "",
  versi: "",
  host: "",
  port: null,
  nama_db_asli: "",
  jumlah_tabel: null,
  file: [],
  keterangan: "",
});

const backupMediaForm = reactive({
  nama: "",
  location: "",
  jenis_media: "",
  kapasitas_gb: null,
  address_url: "",
});

const backupJobForm = reactive({
  aplikasi_id: "",
  backup_media_id: "",
  retensi_n: null,
  retensi_unit: "",
  repetisi_n: null,
  repetisi_unit: "",
});

const upsDeviceForm = reactive({
  nama: "",
  kapasitas_va: null,
  kondisi: "",
  dc_id: "",
});

const socToolForm = reactive({
  nama: "",
  deskripsi_fungsi: "",
  jenis: "",
  dc_ids: [],
  server_ids: [],
  vm_ids: [],
  application_ids: [],
});

const networkSiteForm = reactive({
  kode: "",
  nama: "",
  jenis: "",
  status: "",
  opd_id: "",
  dc_id: "",
  rack_id: "",
  alamat: "",
  lokasi_detail: "",
  titik_koordinat: "",
  pic_nama: "",
  pic_kontak: "",
  catatan: "",
});

const networkDeviceForm = reactive({
  nama: "",
  site_id: "",
  jenis: "",
  status: "",
  kondisi: "",
  merk: "",
  model: "",
  serial_number: "",
  os_firmware: "",
  mac_address: "",
  kapasitas_port: null,
  poe_support: false,
  wireless_standard: "",
  frekuensi: "",
  bandwidth: "",
  deskripsi: "",
});

const networkInstallationForm = reactive({
  site_id: "",
  device_id: "",
  replaced_by_device_id: "",
  role: "",
  status: "",
  installed_at: "",
  removed_at: "",
  installed_by: "",
  notes: "",
});

const networkIpConfigForm = reactive({
  device_id: "",
  site_id: "",
  ip_address_id: "",
  interface_name: "",
  ip_type: "",
  ip_address: "",
  subnet_mask: "",
  gateway: "",
  dns: "",
  vlan: "",
  ssid: "",
  dhcp_enabled: false,
  status: "",
  notes: "",
});

const networkCredentialForm = reactive({
  device_id: "",
  site_id: "",
  label: "",
  access_method: "",
  management_url: "",
  username: "",
  password: "",
  notes: "",
  last_rotated_at: "",
});

const networkMonitoringForm = reactive({
  opd_id: "",
  site_id: ALL_NETWORK_SITES_VALUE,
  monitoring_at: "",
  period_month: "",
  officers_text: "",
  speedtest_download_mbps: null,
  speedtest_upload_mbps: null,
  speedtest_ping_ms: null,
  tower_available: false,
  tower_besi_condition: "",
  tower_kawat_condition: "",
  tower_pondasi_condition: "",
  tower_notes: "",
  notes: "",
  items: [],
  attachments: [],
  remove_attachment_ids: [],
});

const userForm = reactive({
  nama: "",
  email: "",
  password: "",
  opd_id: "",
  role: "",
  status: "",
});

const dataAssetForm = reactive({
  aplikasi_id: "",
  classification_id: "",
  name: "",
  type: "",
  attributes: "",
  owner_agency: "",
  confidentiality_score: null,
  integrity_score: null,
  availability_score: null,
  table_name: "",
  column_name: "",
  contains_personal_data: false,
  personal_data_type: "",
  processing_purpose: "",
  retention_period: "",
  storage_location: "",
  data_owner: "",
  access_policy: "",
  description: "",
});

const functionClassificationOptions = [
  { value: "layanan_publik", label: "Layanan Publik" },
  { value: "layanan_internal", label: "Layanan Internal" },
  { value: "tools_pendukung", label: "Tools Pendukung" },
  { value: "platform_integrasi", label: "Platform Integrasi" },
  { value: "low_code_no_code", label: "Platform Low-code / No-code" },
  { value: "monitoring_observability", label: "Monitoring / Observability" },
  { value: "security_tools", label: "Security Tools" },
  { value: "kolaborasi_knowledge_base", label: "Kolaborasi / Knowledge Base" },
];

const developerOptions = [
  { value: "instansi_pusat", label: "Instansi Pusat" },
  { value: "diskominfo_langkat", label: "Diskominfo Langkat" },
  { value: "unit_penyelenggara", label: "Unit Penyelenggara" },
  { value: "pihak_ketiga", label: "Pihak Ketiga" },
  { value: "in_house", label: "In-House" },
];

const networkDeviceTypeOptions = [
  { value: "router_utama", label: "Router Utama" },
  { value: "router", label: "Router" },
  { value: "switch", label: "Switch" },
  { value: "access_point", label: "Access Point" },
  { value: "wireless_controller", label: "Wireless Controller" },
  { value: "modem", label: "Modem" },
  { value: "cpe", label: "CPE" },
  { value: "repeater", label: "Repeater" },
  { value: "bridge", label: "Bridge" },
  { value: "firewall", label: "Firewall" },
  { value: "lainnya", label: "Lainnya" },
];

const networkSiteTypeOptions = [
  { value: "kantor", label: "Kantor / OPD" },
  { value: "dc", label: "Data Center" },
  { value: "rack", label: "Rack / Ruang Server" },
  { value: "tower", label: "Tower / Tiang" },
  { value: "ruang", label: "Ruang" },
  { value: "outdoor", label: "Outdoor" },
  { value: "lainnya", label: "Lainnya" },
];

const networkInstallationRoleOptions = [
  { value: "primary", label: "Primary" },
  { value: "backup", label: "Backup" },
  { value: "distribution", label: "Distribution" },
  { value: "access", label: "Access" },
  { value: "uplink", label: "Uplink" },
  { value: "client", label: "Client / Edge" },
  { value: "lainnya", label: "Lainnya" },
];

const networkIpTypeOptions = [
  { value: "management", label: "Management" },
  { value: "wan", label: "WAN" },
  { value: "lan", label: "LAN" },
  { value: "wifi", label: "Wi-Fi" },
  { value: "loopback", label: "Loopback" },
  { value: "lainnya", label: "Lainnya" },
];

const networkAccessMethodOptions = [
  { value: "web", label: "Web" },
  { value: "ssh", label: "SSH" },
  { value: "winbox", label: "Winbox" },
  { value: "snmp", label: "SNMP" },
  { value: "api", label: "API" },
  { value: "vpn", label: "VPN" },
  { value: "lainnya", label: "Lainnya" },
];

const monitoringConditionOptions = [
  { value: "baik", label: "Baik" },
  { value: "kurang_baik", label: "Kurang Baik" },
  { value: "rusak", label: "Rusak" },
];

const networkDeviceTypeLabel = (value) =>
  networkDeviceTypeOptions.find((option) => option.value === value)?.label ||
  value ||
  "-";
const networkSiteTypeLabel = (value) =>
  networkSiteTypeOptions.find((option) => option.value === value)?.label ||
  value ||
  "-";
const networkInstallationRoleLabel = (value) =>
  networkInstallationRoleOptions.find((option) => option.value === value)
    ?.label ||
  value ||
  "-";
const networkIpTypeLabel = (value) =>
  networkIpTypeOptions.find((option) => option.value === value)?.label ||
  value ||
  "-";
const networkAccessMethodLabel = (value) =>
  networkAccessMethodOptions.find((option) => option.value === value)?.label ||
  value ||
  "-";
const monitoringConditionLabel = (value) =>
  monitoringConditionOptions.find((option) => option.value === value)?.label ||
  value ||
  "-";

function functionClassificationLabel(value) {
  return (
    functionClassificationOptions.find((option) => option.value === value)
      ?.label || value
  );
}

function developerLabel(value) {
  return (
    developerOptions.find((option) => option.value === value)?.label || value
  );
}

const changeFieldLabels = {
  nama: "Nama",
  dc_id: "Gedung / Ruang DC",
  rack_id: "Rack",
  rack_size_u: "Rack Size (U)",
  server_id: "Host Server",
  merk: "Merk",
  tipe: "Tipe",
  serial_number: "Serial Number",
  merk_processor: "Merk Processor",
  cpu_core: "CPU Core",
  vcpu: "vCPU",
  ram_gb: "RAM GB",
  storage_gb: "Storage GB",
  os: "Operating System",
  kondisi: "Kondisi",
  status: "Status",
  tahun: "Tahun",
  penanggung_jawab: "Penanggung Jawab",
};

function changeFieldLabel(field) {
  return changeFieldLabels[field] || field;
}

async function api(path, options = {}) {
  const response = await fetch(`/api${path}`, {
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(authToken.value
        ? { Authorization: `Bearer ${authToken.value}` }
        : {}),
      ...(options.headers || {}),
    },
    ...options,
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    if (response.status === 401) clearAuth();
    const apiError = new Error(body.message || `API error ${response.status}`);
    apiError.status = response.status;
    apiError.type = body.type || null;
    throw apiError;
  }

  if (response.status === 204) return null;
  return response.json();
}

async function apiForm(path, formData, method = "POST") {
  if (method !== "POST") {
    formData.append("_method", method);
  }

  const response = await fetch(`/api${path}`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      ...(authToken.value
        ? { Authorization: `Bearer ${authToken.value}` }
        : {}),
    },
    body: formData,
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    if (response.status === 401) clearAuth();
    const apiError = new Error(body.message || `API error ${response.status}`);
    apiError.status = response.status;
    apiError.type = body.type || null;
    throw apiError;
  }

  if (response.status === 204) return null;
  return response.json();
}

function clearAuth() {
  authToken.value = "";
  currentUser.value = null;
  mobileNavOpen.value = false;
  localStorage.removeItem("iamt_token");
}

async function login() {
  error.value = "";
  try {
    const response = await api("/auth/login", {
      method: "POST",
      body: JSON.stringify(authForm),
    });
    authToken.value = response.token;
    currentUser.value = response.user;
    localStorage.setItem("iamt_token", response.token);
    await loadAll();
    await handleAssetDeepLink();
  } catch (err) {
    error.value = err.message;
  }
}

async function loadCurrentUser() {
  if (!authToken.value) return;
  try {
    const response = await api("/auth/me");
    currentUser.value = response.user;
  } catch {
    clearAuth();
  }
}

async function logout() {
  try {
    await api("/auth/logout", { method: "POST", body: JSON.stringify({}) });
  } catch {
    // Token may already be invalid; clear local state either way.
  }
  clearAuth();
}

async function bootstrapAuth() {
  await loadCurrentUser();
  if (currentUser.value) {
    await loadAll();
    await handleAssetDeepLink();
  }
}

function syncTableCardLabels() {
  document.querySelectorAll(".table-wrap table").forEach((table) => {
    const headerLabels = Array.from(table.querySelectorAll("thead th")).map(
      (header, index, headers) => {
        const label = header.textContent?.trim();
        if (label) return label;
        return index === headers.length - 1 ? "Aksi" : `Kolom ${index + 1}`;
      },
    );

    table.querySelectorAll("tbody tr").forEach((row) => {
      Array.from(row.children).forEach((cell, index) => {
        if (
          !(cell instanceof HTMLElement) ||
          cell.tagName.toLowerCase() !== "td"
        )
          return;
        cell.dataset.label = headerLabels[index] || `Kolom ${index + 1}`;
      });
    });
  });
}

function selectMenu(item) {
  if (item.disabled) return;
  activeTab.value = item.id;
  mobileNavOpen.value = false;
}

async function loadAll() {
  loading.value = true;
  error.value = "";
  try {
    const [
      dash,
      refs,
      dcRows,
      rackRows,
      ispRows,
      ipRows,
      serverRows,
      vmRows,
      appRows,
      dataAssetRows,
      documentRows,
      integrationRows,
      dbDocRows,
      backupMediaRows,
      backupJobRows,
      upsRows,
      socRows,
      networkSiteRows,
      networkRows,
      networkInstallationRows,
      networkIpConfigRows,
      networkCredentialRows,
      networkMonitoringRows,
      userRows,
      apiKeyRows,
      mapRows,
      complianceRows,
      auditRows,
      changeRows,
    ] = await Promise.all([
      api("/dashboard"),
      api("/references"),
      api("/data-centers"),
      api("/racks"),
      api("/isps"),
      api("/ip-addresses"),
      api("/servers"),
      api("/vms"),
      api("/applications"),
      api("/data-assets"),
      api("/application-documents"),
      api("/app-integrations"),
      api("/app-database-docs"),
      api("/backup-media"),
      api("/backup-jobs"),
      api("/ups-devices"),
      api("/soc-tools"),
      api("/network-sites"),
      api("/network-devices"),
      api("/network-installations"),
      api("/network-ip-configs"),
      api("/network-credentials"),
      api("/network-monitorings"),
      api("/users"),
      api("/api-keys"),
      api("/dependency-map"),
      api("/compliance"),
      api("/audit-log"),
      api("/asset-change-logs"),
    ]);

    dashboard.value = dash;
    references.value = refs;
    dataCenters.value = dcRows;
    racks.value = rackRows;
    isps.value = ispRows;
    ipAddresses.value = ipRows;
    servers.value = serverRows;
    vms.value = vmRows;
    applications.value = appRows;
    dataAssets.value = dataAssetRows;
    applicationDocuments.value = documentRows;
    appIntegrations.value = integrationRows;
    appDatabaseDocs.value = dbDocRows;
    backupMedia.value = backupMediaRows;
    backupJobs.value = backupJobRows;
    upsDevices.value = upsRows;
    socTools.value = socRows;
    networkSites.value = networkSiteRows;
    networkDevices.value = networkRows;
    networkInstallations.value = networkInstallationRows;
    networkIpConfigs.value = networkIpConfigRows;
    networkCredentials.value = networkCredentialRows;
    networkMonitorings.value = networkMonitoringRows;
    users.value = userRows;
    apiKeys.value = apiKeyRows;
    dependencyMap.value = mapRows;
    compliance.value = complianceRows;
    auditLog.value = auditRows;
    assetChangeLogs.value = changeRows;
    selectedServerId.value ||= serverRows[0]?.id || "";
    if (selectedServerId.value) await loadImpact();
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
}

async function loadImpact() {
  if (!selectedServerId.value) return;
  impact.value = await api(`/impact/server/${selectedServerId.value}`);
}

const moduleLabels = {
  "data-centers": "Data Center",
  racks: "Rack",
  servers: "Server",
  vms: "Virtual Machine",
  "ip-addresses": "IP Address",
  isps: "ISP",
  applications: "Aplikasi",
  "data-assets": "Data Aplikasi",
  "application-documents": "Dokumen",
  "app-integrations": "Interoperabilitas",
  "app-database-docs": "Dok. Basis Data",
  "backup-media": "Media Pencadangan",
  "backup-jobs": "Pencadangan",
  "ups-devices": "UPS / Power Backup",
  "soc-tools": "SOC",
  "network-sites": "Site / Node Jaringan",
  "network-devices": "Perangkat Jaringan",
  "network-installations": "Instalasi & Pergantian",
  "network-ip-configs": "Konfigurasi IP Jaringan",
  "network-credentials": "Kredensial Jaringan",
  "network-monitorings": "Monitoring Site",
  "network-owner-summary": "Relasi OPD Consumer Networking",
  users: "Pengguna & Role",
  "api-keys": "API Key",
};

const bulkLabelModuleOptions = [
  "data-centers",
  "racks",
  "servers",
  "vms",
  "isps",
  "ip-addresses",
  "applications",
  "data-assets",
  "application-documents",
  "app-integrations",
  "app-database-docs",
  "backup-media",
  "backup-jobs",
  "ups-devices",
  "soc-tools",
  "network-sites",
  "network-devices",
  "network-installations",
  "network-ip-configs",
  "network-credentials",
].map((value) => ({ value, label: moduleLabels[value] }));

const activeModuleLabel = computed(() => moduleLabels[modal.module] || "Data");
const selectedLabelSize = computed(
  () =>
    labelSizeOptions.find((option) => option.value === labelModal.size) ||
    labelSizeOptions[1],
);
const selectedBulkLabelSize = computed(
  () =>
    labelSizeOptions.find((option) => option.value === bulkLabelForm.size) ||
    labelSizeOptions[1],
);
const labelPrintStyle = computed(() => ({
  "--label-width": `${selectedLabelSize.value.width}mm`,
  "--label-height": `${selectedLabelSize.value.height}mm`,
}));
const bulkLabelPrintStyle = computed(() => ({
  "--label-width": `${selectedBulkLabelSize.value.width}mm`,
  "--label-height": `${selectedBulkLabelSize.value.height}mm`,
}));

function itemsForLabelModule(module) {
  return (
    {
      "data-centers": dataCenters.value,
      racks: racks.value,
      servers: servers.value,
      vms: vms.value,
      isps: isps.value,
      "ip-addresses": ipAddresses.value,
      applications: applications.value,
      "data-assets": dataAssets.value,
      "application-documents": applicationDocuments.value,
      "app-integrations": appIntegrations.value,
      "app-database-docs": appDatabaseDocs.value,
      "backup-media": backupMedia.value,
      "backup-jobs": backupJobs.value,
      "ups-devices": upsDevices.value,
      "soc-tools": socTools.value,
      "network-sites": networkSites.value,
      "network-devices": networkDevices.value,
      "network-installations": networkInstallations.value,
      "network-ip-configs": networkIpConfigs.value,
      "network-credentials": networkCredentials.value,
    }[module] || []
  );
}

const bulkLabelItems = computed(() =>
  itemsForLabelModule(bulkLabelForm.module),
);

function assetName(row, module = "") {
  if (!row) return "-";
  if (module === "ip-addresses") return row.ip || "-";
  if (module === "data-assets") return row.name || "-";
  if (module === "application-documents")
    return row.original_name || row.nama || "-";
  if (module === "backup-jobs")
    return row.aplikasi?.nama
      ? `Backup ${row.aplikasi.nama}`
      : `Pencadangan #${row.id}`;
  if (module === "app-integrations")
    return row.aplikasi?.nama
      ? `Integrasi ${row.aplikasi.nama}`
      : `Integrasi #${row.id}`;
  if (module === "app-database-docs")
    return `${row.nama_database || "DB"} / ${row.aplikasi?.nama || "-"}`;
  if (module === "network-installations")
    return `${row.device?.nama || "-"} @ ${row.site?.nama || "-"}`;
  if (module === "network-ip-configs")
    return `${row.device?.nama || "-"} / ${row.ip_address || row.ip_address_record?.ip || "-"}`;
  if (module === "network-credentials")
    return `${row.label || "Akses"} / ${row.device?.nama || "-"}`;
  if (module === "network-monitorings")
    return `${monitoringScopeLabel(row)} / ${formatDateTime(row.monitoring_at)}`;
  return row.nama || row.name || row.asset_code || "-";
}

function assetCode(row) {
  return (
    row?.asset_code ||
    (row?.id
      ? `ID ${String(row.id).slice(0, 8).toUpperCase()}`
      : "Kode belum dibuat")
  );
}

function assetLocation(module, row) {
  if (!row) return "-";
  if (module === "data-centers") return row.lokasi || "-";
  if (module === "racks") return row.data_center?.nama || row.dc_id || "-";
  if (module === "servers")
    return (
      [row.data_center?.nama, row.rack?.nama].filter(Boolean).join(" / ") || "-"
    );
  if (module === "vms") return row.server?.nama || "-";
  if (module === "ip-addresses") return row.isp?.nama || "-";
  if (module === "isps") return row.bandwidth || row.tipe || "-";
  if (module === "applications") return row.opd?.nama || "-";
  if (module === "data-assets") return row.aplikasi?.nama || "-";
  if (module === "application-documents") return row.aplikasi?.nama || "-";
  if (module === "app-integrations")
    return (
      (row.target_applications || []).map((app) => app.nama).join(", ") ||
      row.aplikasi?.nama ||
      "-"
    );
  if (module === "app-database-docs")
    return row.aplikasi?.nama || row.host || "-";
  if (module === "backup-media") return row.location || "-";
  if (module === "backup-jobs") return row.media?.nama || "-";
  if (module === "ups-devices")
    return row.data_center?.nama || row.dc_id || "-";
  if (module === "soc-tools") return row.jenis || "-";
  if (module === "network-sites")
    return (
      [
        row.data_center?.nama,
        row.rack?.nama,
        row.opd?.nama,
        row.lokasi_detail,
        row.alamat,
      ]
        .filter(Boolean)
        .join(" / ") || "-"
    );
  if (module === "network-devices")
    return (
      row.active_installation?.site?.nama ||
      [
        row.data_center?.nama,
        row.rack?.nama,
        row.opd?.nama,
        row.lokasi_instalasi,
      ]
        .filter(Boolean)
        .join(" / ") ||
      "-"
    );
  if (module === "network-installations")
    return row.site?.nama || row.device?.nama || "-";
  if (module === "network-ip-configs")
    return row.site?.nama || row.device?.nama || "-";
  if (module === "network-credentials")
    return row.site?.nama || row.device?.nama || "-";
  if (module === "network-monitorings")
    return (
      [row.site?.nama, row.site?.lokasi_detail, row.site?.alamat, row.opd?.nama]
        .filter(Boolean)
        .join(" / ") || "-"
    );
  return row.lokasi || row.location || "-";
}

function assetDetailUrl(module, row) {
  return `${window.location.origin}/asset/${module}/${row.id}`;
}

function formatDateTime(value) {
  if (!value) return "-";
  return new Date(value).toLocaleString("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function toDateTimeLocal(value) {
  if (!value) return "";
  const date = new Date(value);
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
  return local.toISOString().slice(0, 16);
}

function formatFileSize(bytes) {
  if (!bytes) return "-";
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function monitoringOfficersText(row) {
  return (row?.officers || []).filter(Boolean).join(", ") || "-";
}

function monitoringScopeLabel(row) {
  if (row?.site?.nama) return row.site.nama;
  if (row?.opd?.nama) return `Semua Site/Node - ${row.opd.nama}`;

  return "Semua Site/Node";
}

function monitoringSpeedSummary(row) {
  const parts = [];
  if (
    row?.speedtest_download_mbps !== null &&
    row?.speedtest_download_mbps !== undefined &&
    row.speedtest_download_mbps !== ""
  )
    parts.push(`Down ${row.speedtest_download_mbps} Mbps`);
  if (
    row?.speedtest_upload_mbps !== null &&
    row?.speedtest_upload_mbps !== undefined &&
    row.speedtest_upload_mbps !== ""
  )
    parts.push(`Up ${row.speedtest_upload_mbps} Mbps`);
  if (
    row?.speedtest_ping_ms !== null &&
    row?.speedtest_ping_ms !== undefined &&
    row.speedtest_ping_ms !== ""
  )
    parts.push(`Ping ${row.speedtest_ping_ms} ms`);
  return parts.join(" / ") || "-";
}

function monitoringTowerSummary(row) {
  if (!row?.tower_available) return "Tidak ada / tidak diperiksa";
  return [
    `Besi: ${monitoringConditionLabel(row.tower_besi_condition)}`,
    `Kawat: ${monitoringConditionLabel(row.tower_kawat_condition)}`,
    `Pondasi: ${monitoringConditionLabel(row.tower_pondasi_condition)}`,
  ].join(" / ");
}

function monitoringImageAttachments(row) {
  return (row?.attachments || []).filter((attachment) => attachment.is_image);
}

function monitoringDocumentAttachments(row) {
  return (row?.attachments || []).filter((attachment) => !attachment.is_image);
}

function networkDeviceOptionLabel(device) {
  return device ? `${device.nama || "-"} - ${assetCode(device)}` : "-";
}

function networkSiteOptionLabel(site) {
  return site ? `${site.nama || "-"} - ${assetCode(site)}` : "-";
}

function siteOwnerId(site) {
  return site?.opd_id || site?.opd?.id || "";
}

function siteOwnerName(site) {
  return (
    site?.opd?.nama ||
    references.value.opd.find((opd) => opd.id === siteOwnerId(site))?.nama ||
    "-"
  );
}

function networkDeviceOwnerId(device) {
  return (
    device?.active_installation?.site?.opd_id ||
    device?.active_installation?.site?.opd?.id ||
    device?.opd_id ||
    device?.opd?.id ||
    ""
  );
}

function networkDeviceOwnerName(device) {
  return (
    device?.active_installation?.site?.opd?.nama ||
    device?.opd?.nama ||
    references.value.opd.find((opd) => opd.id === networkDeviceOwnerId(device))
      ?.nama ||
    "-"
  );
}

function networkIpConfigOwnerId(config) {
  return (
    siteOwnerId(config?.site) ||
    networkDeviceOwnerId(
      networkDevices.value.find((device) => device.id === config?.device_id),
    ) ||
    ""
  );
}

function networkIpConfigOwnerName(config) {
  return (
    config?.site?.opd?.nama ||
    references.value.opd.find(
      (opd) => opd.id === networkIpConfigOwnerId(config),
    )?.nama ||
    "-"
  );
}

function devicesForNetworkSite(siteId, selectedDeviceId = "") {
  if (!siteId) return [];

  const seen = new Set();
  const rows = networkInstallations.value
    .filter(
      (installation) =>
        installation.site_id === siteId && installation.status === "aktif",
    )
    .map(
      (installation) =>
        installation.device ||
        networkDevices.value.find(
          (device) => device.id === installation.device_id,
        ),
    )
    .filter(Boolean)
    .filter((device) => {
      if (!device.id || seen.has(device.id)) return false;
      seen.add(device.id);
      return true;
    });

  if (selectedDeviceId && !seen.has(selectedDeviceId)) {
    const selectedDevice = networkDevices.value.find(
      (device) => device.id === selectedDeviceId,
    );
    if (selectedDevice) rows.push(selectedDevice);
  }

  return rows.sort((a, b) => (a.nama || "").localeCompare(b.nama || ""));
}

const networkIpConfigDeviceOptions = computed(() =>
  devicesForNetworkSite(
    networkIpConfigForm.site_id,
    networkIpConfigForm.device_id,
  ),
);

function handleNetworkIpConfigSiteChange() {
  if (!networkIpConfigForm.device_id) return;

  const stillInSite = devicesForNetworkSite(networkIpConfigForm.site_id).some(
    (device) => device.id === networkIpConfigForm.device_id,
  );
  if (!stillInSite) networkIpConfigForm.device_id = "";
}

const selectedNetworkSummaryOpd = computed(
  () =>
    references.value.opd.find((opd) => opd.id === networkSummaryForm.opd_id) ||
    null,
);

const networkSummarySiteRows = computed(() => {
  if (!networkSummaryForm.opd_id) return [];

  return networkSites.value
    .filter((site) => siteOwnerId(site) === networkSummaryForm.opd_id)
    .map((site) => {
      const seen = new Set();
      const devices = networkInstallations.value
        .filter(
          (installation) =>
            installation.site_id === site.id && installation.status === "aktif",
        )
        .filter((installation) => {
          if (!installation.device_id || seen.has(installation.device_id))
            return false;
          seen.add(installation.device_id);
          return true;
        })
        .map((installation) => ({
          installation,
          device:
            installation.device ||
            networkDevices.value.find(
              (device) => device.id === installation.device_id,
            ),
        }))
        .filter((row) => row.device)
        .sort((a, b) =>
          (a.device.nama || "").localeCompare(b.device.nama || ""),
        );

      return { site, devices };
    })
    .sort((a, b) => (a.site.nama || "").localeCompare(b.site.nama || ""));
});

const networkSummaryUnassignedDevices = computed(() => {
  if (!networkSummaryForm.opd_id) return [];

  return networkDevices.value
    .filter((device) => !device.active_installation?.site_id)
    .filter(
      (device) =>
        (device.opd_id || device.opd?.id || "") === networkSummaryForm.opd_id,
    )
    .sort((a, b) => (a.nama || "").localeCompare(b.nama || ""));
});

const networkSummaryTotals = computed(() => {
  const deviceIds = new Set(
    networkSummaryUnassignedDevices.value.map((device) => device.id),
  );
  networkSummarySiteRows.value.forEach((row) => {
    row.devices.forEach(({ device }) => deviceIds.add(device.id));
  });

  return {
    sites: networkSummarySiteRows.value.length,
    devices: deviceIds.size,
    emptySites: networkSummarySiteRows.value.filter(
      (row) => !row.devices.length,
    ).length,
  };
});

function networkSummaryPrintedAt() {
  return new Date().toLocaleString("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function ipVmNames(row) {
  return (
    (row?.vms || [])
      .map((vm) => vm.nama)
      .filter(Boolean)
      .join(", ") || "-"
  );
}

function pingLabel(row) {
  if (row?.ping_status === "up") return "Up";
  if (row?.ping_status === "down") return "Down";
  return "Belum dicek";
}

function pingMeta(row) {
  const parts = [];
  if (row?.ping_latency_ms !== null && row?.ping_latency_ms !== undefined)
    parts.push(`${row.ping_latency_ms} ms`);
  if (row?.ping_checked_at) parts.push(formatDateTime(row.ping_checked_at));
  return parts.join(" / ");
}

function bulkLabelKey(module, row) {
  return `${module}:${row.id}`;
}

function detailEntries(item) {
  return Object.entries(item || {})
    .filter(
      ([key, value]) =>
        !["id", "created_at", "updated_at"].includes(key) &&
        value !== null &&
        value !== undefined &&
        typeof value !== "object",
    )
    .map(([key, value]) => ({ key, value }));
}

function relationEntries(item) {
  return Object.entries(item || {})
    .filter(([, value]) => value && typeof value === "object")
    .map(([key, value]) => {
      if (Array.isArray(value)) {
        return {
          key,
          value: value.map((row) => assetName(row)).join(", ") || "-",
        };
      }

      return { key, value: assetName(value) };
    });
}

async function openAssetDetail(module, id) {
  detailModal.open = true;
  detailModal.loading = true;
  detailModal.module = module;
  detailModal.item = null;
  try {
    detailModal.item = await api(`/${module}/${id}`);
  } catch (err) {
    error.value = err.message;
  } finally {
    detailModal.loading = false;
  }
}

function closeDetailModal() {
  Object.assign(detailModal, {
    open: false,
    loading: false,
    module: "",
    item: null,
  });
}

async function handleAssetDeepLink() {
  if (assetDeepLinkHandled.value || !currentUser.value) return;

  const match = window.location.pathname.match(/^\/asset\/([^/]+)\/([^/]+)/);
  if (!match) return;

  assetDeepLinkHandled.value = true;
  await openAssetDetail(match[1], decodeURIComponent(match[2]));
}

async function openLabel(module, item) {
  if (!printableLabelModules.has(module)) return;

  Object.assign(labelModal, {
    open: true,
    module,
    item,
    size: labelModal.size || "60x40",
  });
  labelQrDataUrl.value = await QRCode.toDataURL(assetDetailUrl(module, item), {
    errorCorrectionLevel: "M",
    margin: 1,
    width: 320,
  });
}

async function generateBulkLabelQrs() {
  const module = bulkLabelForm.module;
  if (!printableLabelModules.has(module)) return;

  const rows = bulkLabelItems.value;
  if (!rows.length) {
    bulkLabelQrUrls.value = {};
    return;
  }

  bulkLabelGenerating.value = true;
  try {
    const pairs = await Promise.all(
      rows.map(async (row) => [
        bulkLabelKey(module, row),
        await QRCode.toDataURL(assetDetailUrl(module, row), {
          errorCorrectionLevel: "M",
          margin: 1,
          width: 260,
        }),
      ]),
    );
    if (module === bulkLabelForm.module) {
      bulkLabelQrUrls.value = Object.fromEntries(pairs);
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    bulkLabelGenerating.value = false;
  }
}

async function printBulkLabels() {
  await generateBulkLabelQrs();
  window.print();
}

function closeLabelModal() {
  Object.assign(labelModal, { open: false, module: "", item: null });
  labelQrDataUrl.value = "";
}

function printLabel() {
  window.print();
}

const monitoringSiteOptions = computed(() => {
  const ownerId = networkMonitoringForm.opd_id;

  return networkSites.value.filter(
    (site) => !ownerId || siteOwnerId(site) === ownerId,
  );
});

function monitoringItemsForScope(opdId, siteId) {
  if (!opdId && (!siteId || siteId === ALL_NETWORK_SITES_VALUE)) return [];

  const scopedInstallations = networkInstallations.value.filter((row) => {
    if (siteId && siteId !== ALL_NETWORK_SITES_VALUE)
      return row.site_id === siteId;

    return siteOwnerId(row.site) === opdId;
  });
  const activeInstallations = scopedInstallations.filter(
    (row) => row.status === "aktif",
  );
  const rows = activeInstallations.length
    ? activeInstallations
    : scopedInstallations;
  const seen = new Set();

  return rows
    .filter((row) => {
      if (!row.device_id || seen.has(row.device_id)) return false;
      seen.add(row.device_id);
      return true;
    })
    .map((row) => ({
      device_id: row.device_id,
      installation_id: row.id,
      device_name: row.device?.nama || "-",
      device_type: row.device?.jenis || "",
      site_name: row.site?.nama || "",
      role: row.role || "",
      condition: "",
      note: "",
    }))
    .sort((a, b) => a.device_name.localeCompare(b.device_name));
}

function populateMonitoringItemsFromSite(preserveExisting = false) {
  const existing = new Map(
    networkMonitoringForm.items.map((item) => [item.device_id, item]),
  );
  networkMonitoringForm.items = monitoringItemsForScope(
    networkMonitoringForm.opd_id,
    networkMonitoringForm.site_id,
  ).map((item) => {
    const previous = existing.get(item.device_id);
    return {
      ...item,
      condition: preserveExisting ? previous?.condition || "" : "",
      note: preserveExisting ? previous?.note || "" : "",
    };
  });
}

function handleMonitoringOpdChange() {
  if (
    networkMonitoringForm.site_id !== ALL_NETWORK_SITES_VALUE &&
    !monitoringSiteOptions.value.some(
      (site) => site.id === networkMonitoringForm.site_id,
    )
  ) {
    networkMonitoringForm.site_id = ALL_NETWORK_SITES_VALUE;
  }

  populateMonitoringItemsFromSite(false);
}

function handleMonitoringSiteChange() {
  if (
    networkMonitoringForm.site_id &&
    networkMonitoringForm.site_id !== ALL_NETWORK_SITES_VALUE
  ) {
    const site = networkSites.value.find(
      (item) => item.id === networkMonitoringForm.site_id,
    );

    if (site && !networkMonitoringForm.opd_id) {
      networkMonitoringForm.opd_id = siteOwnerId(site);
    }
  }

  populateMonitoringItemsFromSite(false);
}

function parseOfficersText(text) {
  return String(text || "")
    .split(/[\n,]+/)
    .map((item) => item.trim())
    .filter(Boolean);
}

function setMonitoringAttachments(event) {
  networkMonitoringForm.attachments = Array.from(event.target.files || []);
}

function toggleRemoveMonitoringAttachment(id) {
  toggleInArray(networkMonitoringForm.remove_attachment_ids, id);
}

async function openMonitoringReport(row) {
  Object.assign(monitoringReportModal, { open: true, item: row });
  monitoringReportQrDataUrl.value = await QRCode.toDataURL(
    assetDetailUrl("network-monitorings", row),
    {
      errorCorrectionLevel: "M",
      margin: 1,
      width: 320,
    },
  );
}

function closeMonitoringReport() {
  Object.assign(monitoringReportModal, { open: false, item: null });
  monitoringReportQrDataUrl.value = "";
}

function printMonitoringReport() {
  window.print();
}

async function printNetworkSummary() {
  if (!networkSummaryForm.opd_id) {
    showAlert(
      "OPD / Pemilik belum dipilih",
      "Pilih OPD/Pemilik terlebih dahulu sebelum mencetak ringkasan relasi perangkat.",
    );
    return;
  }

  await nextTick();
  window.print();
}

function replaceIpAddressRow(row) {
  const index = ipAddresses.value.findIndex((item) => item.id === row.id);
  if (index >= 0) ipAddresses.value.splice(index, 1, row);
}

async function refreshIpPing(row) {
  if (!canWrite.value || pingingIps.value[row.id]) return;
  pingingIps.value = { ...pingingIps.value, [row.id]: true };
  try {
    const response = await api(`/ip-addresses/${row.id}/ping`, {
      method: "POST",
      body: JSON.stringify({}),
    });
    replaceIpAddressRow(response);
  } catch (err) {
    error.value = err.message;
  } finally {
    const { [row.id]: _removed, ...rest } = pingingIps.value;
    pingingIps.value = rest;
  }
}

async function refreshAllIpPings() {
  if (!canWrite.value || pingingAllIps.value) return;
  pingingAllIps.value = true;
  try {
    const response = await api("/ip-addresses/ping", {
      method: "POST",
      body: JSON.stringify({}),
    });
    ipAddresses.value = response.items || [];
    showAlert(
      "Ping selesai",
      `${response.up || 0} IP up, ${response.down || 0} IP down dari ${response.total || 0} IP.`,
    );
  } catch (err) {
    error.value = err.message;
  } finally {
    pingingAllIps.value = false;
  }
}

function resetModuleForm(module) {
  if (module === "data-centers")
    Object.assign(dataCenterForm, { nama: "", lokasi: "", tipe: "" });
  if (module === "racks")
    Object.assign(rackForm, { dc_id: "", nama: "", kapasitas_u: null });
  if (module === "isps")
    Object.assign(ispForm, { nama: "", tipe: "", bandwidth: "", kontak: "" });
  if (module === "ip-addresses")
    Object.assign(ipAddressForm, {
      ip: "",
      jenis: "",
      assignment: "",
      isp_id: "",
    });
  if (module === "servers") {
    Object.assign(serverForm, {
      nama: "",
      dc_id: "",
      rack_id: "",
      rack_size_u: null,
      merk: "",
      tipe: "",
      merk_processor: "",
      tahun: null,
      cpu_core: null,
      ram_gb: null,
      storage_gb: null,
      kondisi: "",
      status: "",
      penanggung_jawab: "",
      change_reason: "",
      changed_by: "",
    });
  }
  if (module === "vms") {
    Object.assign(vmForm, {
      nama: "",
      server_id: "",
      os: "",
      vcpu: null,
      ram_gb: null,
      storage_gb: null,
      status: "",
      ip_ids: [],
      change_reason: "",
      changed_by: "",
    });
  }
  if (module === "applications") {
    Object.assign(appForm, {
      nama: "",
      url: "",
      opd_id: "",
      jenis_aplikasi: "",
      pengembang: "",
      klasifikasi_fungsi: [],
      tech_stack: "",
      status: "",
      sla_persen: null,
      kategori_data: "",
      pic_nama: "",
      pic_kontak: "",
      vm_ids: [],
      server_ids: [],
      ip_ids: [],
    });
  }
  if (module === "data-assets") {
    Object.assign(dataAssetForm, {
      aplikasi_id: "",
      classification_id: "",
      name: "",
      type: "",
      attributes: "",
      owner_agency: "",
      confidentiality_score: null,
      integrity_score: null,
      availability_score: null,
      table_name: "",
      column_name: "",
      contains_personal_data: false,
      personal_data_type: "",
      processing_purpose: "",
      retention_period: "",
      storage_location: "",
      data_owner: "",
      access_policy: "",
      description: "",
    });
  }
  if (module === "application-documents") {
    Object.assign(applicationDocumentForm, {
      aplikasi_id: "",
      document_category: "",
      files: [],
    });
  }
  if (module === "app-integrations") {
    Object.assign(appIntegrationForm, {
      aplikasi_id: "",
      deskripsi: "",
      jenis_integrasi: "",
      metode_integrasi: "",
      target_application_ids: [],
      external_endpoints: "",
      data_asset_ids: [],
      documents: [],
    });
  }
  if (module === "app-database-docs") {
    Object.assign(appDatabaseDocForm, {
      aplikasi_id: "",
      nama_database: "",
      tipe_dbms: "",
      versi: "",
      host: "",
      port: null,
      nama_db_asli: "",
      jumlah_tabel: null,
      file: [],
      keterangan: "",
    });
  }
  if (module === "backup-media") {
    Object.assign(backupMediaForm, {
      nama: "",
      location: "",
      jenis_media: "",
      kapasitas_gb: null,
      address_url: "",
    });
  }
  if (module === "backup-jobs") {
    Object.assign(backupJobForm, {
      aplikasi_id: "",
      backup_media_id: "",
      retensi_n: null,
      retensi_unit: "",
      repetisi_n: null,
      repetisi_unit: "",
    });
  }
  if (module === "ups-devices") {
    Object.assign(upsDeviceForm, {
      nama: "",
      kapasitas_va: null,
      kondisi: "",
      dc_id: "",
    });
  }
  if (module === "soc-tools") {
    Object.assign(socToolForm, {
      nama: "",
      deskripsi_fungsi: "",
      jenis: "",
      dc_ids: [],
      server_ids: [],
      vm_ids: [],
      application_ids: [],
    });
  }
  if (module === "network-sites") {
    Object.assign(networkSiteForm, {
      kode: "",
      nama: "",
      jenis: "",
      status: "",
      opd_id: "",
      dc_id: "",
      rack_id: "",
      alamat: "",
      lokasi_detail: "",
      titik_koordinat: "",
      pic_nama: "",
      pic_kontak: "",
      catatan: "",
    });
  }
  if (module === "network-devices") {
    Object.assign(networkDeviceForm, {
      nama: "",
      site_id: "",
      jenis: "",
      status: "",
      kondisi: "",
      merk: "",
      model: "",
      serial_number: "",
      os_firmware: "",
      mac_address: "",
      kapasitas_port: null,
      poe_support: false,
      wireless_standard: "",
      frekuensi: "",
      bandwidth: "",
      deskripsi: "",
    });
  }
  if (module === "network-installations") {
    Object.assign(networkInstallationForm, {
      site_id: "",
      device_id: "",
      replaced_by_device_id: "",
      role: "",
      status: "",
      installed_at: "",
      removed_at: "",
      installed_by: "",
      notes: "",
    });
  }
  if (module === "network-ip-configs") {
    Object.assign(networkIpConfigForm, {
      device_id: "",
      site_id: "",
      ip_address_id: "",
      interface_name: "",
      ip_type: "",
      ip_address: "",
      subnet_mask: "",
      gateway: "",
      dns: "",
      vlan: "",
      ssid: "",
      dhcp_enabled: false,
      status: "",
      notes: "",
    });
  }
  if (module === "network-credentials") {
    Object.assign(networkCredentialForm, {
      device_id: "",
      site_id: "",
      label: "",
      access_method: "",
      management_url: "",
      username: "",
      password: "",
      notes: "",
      last_rotated_at: "",
    });
  }
  if (module === "network-monitorings") {
    Object.assign(networkMonitoringForm, {
      opd_id: "",
      site_id: ALL_NETWORK_SITES_VALUE,
      monitoring_at: "",
      period_month: "",
      officers_text: "",
      speedtest_download_mbps: null,
      speedtest_upload_mbps: null,
      speedtest_ping_ms: null,
      tower_available: false,
      tower_besi_condition: "",
      tower_kawat_condition: "",
      tower_pondasi_condition: "",
      tower_notes: "",
      notes: "",
      items: [],
      attachments: [],
      remove_attachment_ids: [],
    });
  }
  if (module === "users") {
    Object.assign(userForm, {
      nama: "",
      email: "",
      password: "",
      opd_id: "",
      role: "",
      status: "",
    });
  }
}

function formFor(module) {
  return {
    "data-centers": dataCenterForm,
    racks: rackForm,
    isps: ispForm,
    "ip-addresses": ipAddressForm,
    servers: serverForm,
    vms: vmForm,
    applications: appForm,
    "data-assets": dataAssetForm,
    "application-documents": applicationDocumentForm,
    "app-integrations": appIntegrationForm,
    "app-database-docs": appDatabaseDocForm,
    "backup-media": backupMediaForm,
    "backup-jobs": backupJobForm,
    "ups-devices": upsDeviceForm,
    "soc-tools": socToolForm,
    "network-sites": networkSiteForm,
    "network-devices": networkDeviceForm,
    "network-installations": networkInstallationForm,
    "network-ip-configs": networkIpConfigForm,
    "network-credentials": networkCredentialForm,
    "network-monitorings": networkMonitoringForm,
    users: userForm,
  }[module];
}

function openCreate(module) {
  if (!canWrite.value) return;
  resetModuleForm(module);
  Object.assign(modal, { open: true, module, mode: "create", id: null });
}

function openEdit(module, row) {
  if (!canWrite.value) return;
  resetModuleForm(module);
  if (module === "data-centers") {
    Object.assign(dataCenterForm, {
      nama: row.nama || "",
      lokasi: row.lokasi || "",
      tipe: row.tipe || "",
    });
  }
  if (module === "racks") {
    Object.assign(rackForm, {
      dc_id: row.dc_id || "",
      nama: row.nama || "",
      kapasitas_u: row.kapasitas_u ?? null,
    });
  }
  if (module === "isps") {
    Object.assign(ispForm, {
      nama: row.nama || "",
      tipe: row.tipe || "",
      bandwidth: row.bandwidth || "",
      kontak: row.kontak || "",
    });
  }
  if (module === "ip-addresses") {
    Object.assign(ipAddressForm, {
      ip: row.ip || "",
      jenis: row.jenis || "",
      assignment: row.assignment || "",
      isp_id: row.isp_id || "",
    });
  }
  if (module === "servers") {
    Object.assign(serverForm, {
      nama: row.nama || "",
      dc_id: row.dc_id || "",
      rack_id: row.rack_id || "",
      rack_size_u: row.rack_size_u ?? null,
      merk: row.merk || "",
      tipe: row.tipe || "",
      merk_processor: row.merk_processor || "",
      tahun: row.tahun ?? null,
      cpu_core: row.cpu_core ?? null,
      ram_gb: row.ram_gb ?? null,
      storage_gb: row.storage_gb ?? null,
      kondisi: row.kondisi || "",
      status: row.status || "",
      penanggung_jawab: row.penanggung_jawab || "",
      change_reason: "",
      changed_by: "",
    });
  }
  if (module === "vms") {
    Object.assign(vmForm, {
      nama: row.nama || "",
      server_id: row.server_id || "",
      os: row.os || "",
      vcpu: row.vcpu ?? null,
      ram_gb: row.ram_gb ?? null,
      storage_gb: row.storage_gb ?? null,
      status: row.status || "",
      ip_ids: (row.ip_addresses || []).map((ip) => ip.id),
      change_reason: "",
      changed_by: "",
    });
  }
  if (module === "applications") {
    Object.assign(appForm, {
      nama: row.nama || "",
      url: row.url || "",
      opd_id: row.opd_id || "",
      jenis_aplikasi: row.jenis_aplikasi || "",
      pengembang: row.pengembang || "",
      klasifikasi_fungsi: row.klasifikasi_fungsi || [],
      tech_stack: row.tech_stack || "",
      status: row.status || "",
      sla_persen:
        row.sla_persen === null || row.sla_persen === undefined
          ? null
          : Number(row.sla_persen),
      kategori_data: row.kategori_data || "",
      pic_nama: row.pic_nama || "",
      pic_kontak: row.pic_kontak || "",
      vm_ids: (row.vms || []).map((vm) => vm.id),
      server_ids: (row.servers || []).map((server) => server.id),
      ip_ids: (row.ip_addresses || []).map((ip) => ip.id),
    });
  }
  if (module === "application-documents") {
    Object.assign(applicationDocumentForm, {
      aplikasi_id: row.aplikasi_id || "",
      document_category: row.document_category || "",
      files: [],
    });
  }
  if (module === "app-integrations") {
    Object.assign(appIntegrationForm, {
      aplikasi_id: row.aplikasi_id || "",
      deskripsi: row.deskripsi || "",
      jenis_integrasi: row.jenis_integrasi || "",
      metode_integrasi: row.metode_integrasi || "",
      target_application_ids: (row.target_applications || []).map(
        (app) => app.id,
      ),
      external_endpoints: row.external_endpoints || "",
      data_asset_ids: (row.data_assets || []).map((asset) => asset.id),
      documents: [],
    });
  }
  if (module === "app-database-docs") {
    Object.assign(appDatabaseDocForm, {
      aplikasi_id: row.aplikasi_id || "",
      nama_database: row.nama_database || "",
      tipe_dbms: row.tipe_dbms || "",
      versi: row.versi || "",
      host: row.host || "",
      port: row.port ?? null,
      nama_db_asli: row.nama_db_asli || "",
      jumlah_tabel: row.jumlah_tabel ?? null,
      file: [],
      keterangan: row.keterangan || "",
    });
  }
  if (module === "backup-media") {
    Object.assign(backupMediaForm, {
      nama: row.nama || "",
      location: row.location || "",
      jenis_media: row.jenis_media || "",
      kapasitas_gb: row.kapasitas_gb ?? null,
      address_url: row.address_url || "",
    });
  }
  if (module === "backup-jobs") {
    Object.assign(backupJobForm, {
      aplikasi_id: row.aplikasi_id || "",
      backup_media_id: row.backup_media_id || "",
      retensi_n: row.retensi_n ?? null,
      retensi_unit: row.retensi_unit || "",
      repetisi_n: row.repetisi_n ?? null,
      repetisi_unit: row.repetisi_unit || "",
    });
  }
  if (module === "ups-devices") {
    Object.assign(upsDeviceForm, {
      nama: row.nama || "",
      kapasitas_va: row.kapasitas_va ?? null,
      kondisi: row.kondisi || "",
      dc_id: row.dc_id || "",
    });
  }
  if (module === "soc-tools") {
    Object.assign(socToolForm, {
      nama: row.nama || "",
      deskripsi_fungsi: row.deskripsi_fungsi || "",
      jenis: row.jenis || "",
      dc_ids: (row.data_centers || []).map((dc) => dc.id),
      server_ids: (row.servers || []).map((server) => server.id),
      vm_ids: (row.vms || []).map((vm) => vm.id),
      application_ids: (row.applications || []).map((app) => app.id),
    });
  }
  if (module === "network-sites") {
    Object.assign(networkSiteForm, {
      kode: row.kode || "",
      nama: row.nama || "",
      jenis: row.jenis || "",
      status: row.status || "",
      opd_id: row.opd_id || "",
      dc_id: row.dc_id || "",
      rack_id: row.rack_id || "",
      alamat: row.alamat || "",
      lokasi_detail: row.lokasi_detail || "",
      titik_koordinat: row.titik_koordinat || "",
      pic_nama: row.pic_nama || "",
      pic_kontak: row.pic_kontak || "",
      catatan: row.catatan || "",
    });
  }
  if (module === "network-devices") {
    Object.assign(networkDeviceForm, {
      nama: row.nama || "",
      site_id:
        row.active_installation?.site_id ||
        row.active_installation?.site?.id ||
        "",
      jenis: row.jenis || "",
      status: row.status || "",
      kondisi: row.kondisi || "",
      merk: row.merk || "",
      model: row.model || "",
      serial_number: row.serial_number || "",
      os_firmware: row.os_firmware || "",
      mac_address: row.mac_address || "",
      kapasitas_port: row.kapasitas_port ?? null,
      poe_support: Boolean(row.poe_support),
      wireless_standard: row.wireless_standard || "",
      frekuensi: row.frekuensi || "",
      bandwidth: row.bandwidth || "",
      deskripsi: row.deskripsi || "",
    });
  }
  if (module === "network-installations") {
    Object.assign(networkInstallationForm, {
      site_id: row.site_id || "",
      device_id: row.device_id || "",
      replaced_by_device_id: row.replaced_by_device_id || "",
      role: row.role || "",
      status: row.status || "",
      installed_at: row.installed_at
        ? String(row.installed_at).slice(0, 10)
        : "",
      removed_at: row.removed_at ? String(row.removed_at).slice(0, 10) : "",
      installed_by: row.installed_by || "",
      notes: row.notes || "",
    });
  }
  if (module === "network-ip-configs") {
    Object.assign(networkIpConfigForm, {
      device_id: row.device_id || "",
      site_id: row.site_id || "",
      ip_address_id: row.ip_address_id || "",
      interface_name: row.interface_name || "",
      ip_type: row.ip_type || "",
      ip_address: row.ip_address || "",
      subnet_mask: row.subnet_mask || "",
      gateway: row.gateway || "",
      dns: row.dns || "",
      vlan: row.vlan || "",
      ssid: row.ssid || "",
      dhcp_enabled: Boolean(row.dhcp_enabled),
      status: row.status || "",
      notes: row.notes || "",
    });
  }
  if (module === "network-credentials") {
    Object.assign(networkCredentialForm, {
      device_id: row.device_id || "",
      site_id: row.site_id || "",
      label: row.label || "",
      access_method: row.access_method || "",
      management_url: row.management_url || "",
      username: row.username || "",
      password: "",
      notes: row.notes || "",
      last_rotated_at: row.last_rotated_at
        ? String(row.last_rotated_at).slice(0, 10)
        : "",
    });
  }
  if (module === "network-monitorings") {
    Object.assign(networkMonitoringForm, {
      opd_id: row.opd_id || row.site?.opd_id || row.site?.opd?.id || "",
      site_id: row.site_id || ALL_NETWORK_SITES_VALUE,
      monitoring_at: toDateTimeLocal(row.monitoring_at),
      period_month: row.period_month || "",
      officers_text: (row.officers || []).join("\n"),
      speedtest_download_mbps: row.speedtest_download_mbps ?? null,
      speedtest_upload_mbps: row.speedtest_upload_mbps ?? null,
      speedtest_ping_ms: row.speedtest_ping_ms ?? null,
      tower_available: Boolean(row.tower_available),
      tower_besi_condition: row.tower_besi_condition || "",
      tower_kawat_condition: row.tower_kawat_condition || "",
      tower_pondasi_condition: row.tower_pondasi_condition || "",
      tower_notes: row.tower_notes || "",
      notes: row.notes || "",
      items: (row.items || []).map((item) => ({
        device_id: item.device_id,
        installation_id: item.installation_id || "",
        device_name: item.device?.nama || "-",
        device_type: item.device?.jenis || "",
        role: item.installation?.role || "",
        condition: item.condition || "",
        note: item.note || "",
      })),
      attachments: [],
      remove_attachment_ids: [],
    });
  }
  if (module === "users") {
    Object.assign(userForm, {
      nama: row.nama || "",
      email: row.email || "",
      password: "",
      opd_id: row.opd_id || "",
      role: row.role || "",
      status: row.status || "",
    });
  }
  if (module === "data-assets") {
    Object.assign(dataAssetForm, {
      aplikasi_id: row.aplikasi_id || "",
      classification_id: row.classification_id || "",
      name: row.name || "",
      type: row.type || "",
      attributes: row.attributes || "",
      owner_agency: row.owner_agency || "",
      confidentiality_score: row.confidentiality_score ?? null,
      integrity_score: row.integrity_score ?? null,
      availability_score: row.availability_score ?? null,
      table_name: row.table_name || "",
      column_name: row.column_name || "",
      contains_personal_data: Boolean(row.contains_personal_data),
      personal_data_type: row.personal_data_type || "",
      processing_purpose: row.processing_purpose || "",
      retention_period: row.retention_period || "",
      storage_location: row.storage_location || "",
      data_owner: row.data_owner || "",
      access_policy: row.access_policy || "",
      description: row.description || "",
    });
  }
  Object.assign(modal, { open: true, module, mode: "edit", id: row.id });
}

function openCopy(module, row) {
  if (!canWrite.value) return;
  openEdit(module, row);

  if (module === "ip-addresses") ipAddressForm.ip = "";
  if (module === "network-sites") networkSiteForm.kode = "";
  if (module === "users") userForm.email = "";

  Object.assign(modal, { open: true, module, mode: "create", id: null });
}

function closeModal() {
  Object.assign(modal, { open: false, module: "", mode: "create", id: null });
}

function showAlert(title, message) {
  Object.assign(alertModal, { open: true, title, message });
}

function closeAlert() {
  Object.assign(alertModal, { open: false, title: "", message: "" });
}

async function saveModal() {
  if (saving.value) return;
  if (!canWrite.value) {
    error.value = "Akses read only tidak dapat mengubah data.";
    return;
  }
  error.value = "";
  saving.value = true;
  try {
    if (
      [
        "application-documents",
        "app-integrations",
        "app-database-docs",
        "network-monitorings",
      ].includes(modal.module)
    ) {
      const formData = formDataFor(modal.module);
      const endpoint =
        modal.mode === "edit"
          ? `/${modal.module}/${modal.id}`
          : `/${modal.module}`;
      await apiForm(endpoint, formData, modal.mode === "edit" ? "PUT" : "POST");
      closeModal();
      await loadAll();
      return;
    }

    const savedModule = modal.module;
    const savedMode = modal.mode;
    const endpoint =
      savedMode === "edit" ? `/${savedModule}/${modal.id}` : `/${savedModule}`;
    const response = await api(endpoint, {
      method: savedMode === "edit" ? "PUT" : "POST",
      body: JSON.stringify(
        cleanPayload(formFor(savedModule), {
          includeEmpty: savedMode === "edit",
        }),
      ),
    });
    closeModal();
    await loadAll();
    if (
      savedMode === "create" &&
      savedModule === "ip-addresses" &&
      response?.created
    ) {
      showAlert(
        "Bulk IP selesai",
        `${response.total_created || 0} IP dibuat. ${response.total_skipped || 0} IP dilewati karena sudah ada.`,
      );
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

function appendArray(formData, key, values) {
  values.forEach((value) => formData.append(`${key}[]`, value));
}

function appendValue(formData, key, value) {
  formData.append(key, value ?? "");
}

function formDataFor(module) {
  const formData = new FormData();

  if (module === "application-documents") {
    formData.append("aplikasi_id", applicationDocumentForm.aplikasi_id);
    formData.append(
      "document_category",
      applicationDocumentForm.document_category,
    );
    applicationDocumentForm.files.forEach((file) =>
      formData.append("files[]", file),
    );
  }

  if (module === "app-integrations") {
    formData.append("aplikasi_id", appIntegrationForm.aplikasi_id);
    formData.append("deskripsi", appIntegrationForm.deskripsi || "");
    formData.append("jenis_integrasi", appIntegrationForm.jenis_integrasi);
    formData.append("metode_integrasi", appIntegrationForm.metode_integrasi);
    formData.append(
      "external_endpoints",
      appIntegrationForm.external_endpoints || "",
    );
    appendArray(
      formData,
      "target_application_ids",
      appIntegrationForm.target_application_ids,
    );
    appendArray(formData, "data_asset_ids", appIntegrationForm.data_asset_ids);
    appIntegrationForm.documents.forEach((file) =>
      formData.append("documents[]", file),
    );
  }

  if (module === "network-monitorings") {
    appendValue(formData, "opd_id", networkMonitoringForm.opd_id);
    appendValue(
      formData,
      "site_id",
      networkMonitoringForm.site_id === ALL_NETWORK_SITES_VALUE
        ? ""
        : networkMonitoringForm.site_id,
    );
    appendValue(formData, "monitoring_at", networkMonitoringForm.monitoring_at);
    appendValue(formData, "period_month", networkMonitoringForm.period_month);
    appendValue(
      formData,
      "speedtest_download_mbps",
      networkMonitoringForm.speedtest_download_mbps,
    );
    appendValue(
      formData,
      "speedtest_upload_mbps",
      networkMonitoringForm.speedtest_upload_mbps,
    );
    appendValue(
      formData,
      "speedtest_ping_ms",
      networkMonitoringForm.speedtest_ping_ms,
    );
    appendValue(
      formData,
      "tower_available",
      networkMonitoringForm.tower_available ? "1" : "0",
    );
    appendValue(
      formData,
      "tower_besi_condition",
      networkMonitoringForm.tower_besi_condition,
    );
    appendValue(
      formData,
      "tower_kawat_condition",
      networkMonitoringForm.tower_kawat_condition,
    );
    appendValue(
      formData,
      "tower_pondasi_condition",
      networkMonitoringForm.tower_pondasi_condition,
    );
    appendValue(formData, "tower_notes", networkMonitoringForm.tower_notes);
    appendValue(formData, "notes", networkMonitoringForm.notes);
    appendValue(
      formData,
      "officers",
      JSON.stringify(parseOfficersText(networkMonitoringForm.officers_text)),
    );
    appendValue(
      formData,
      "items",
      JSON.stringify(
        networkMonitoringForm.items.map((item) => ({
          device_id: item.device_id,
          installation_id: item.installation_id || null,
          condition: item.condition,
          note: item.note || null,
        })),
      ),
    );
    appendValue(
      formData,
      "remove_attachment_ids",
      JSON.stringify(networkMonitoringForm.remove_attachment_ids),
    );
    networkMonitoringForm.attachments.forEach((file) =>
      formData.append("attachments[]", file),
    );
  }

  if (module === "app-database-docs") {
    appendValue(formData, "aplikasi_id", appDatabaseDocForm.aplikasi_id);
    appendValue(formData, "nama_database", appDatabaseDocForm.nama_database);
    appendValue(formData, "tipe_dbms", appDatabaseDocForm.tipe_dbms);
    appendValue(formData, "versi", appDatabaseDocForm.versi);
    appendValue(formData, "host", appDatabaseDocForm.host);
    appendValue(formData, "port", appDatabaseDocForm.port);
    appendValue(formData, "nama_db_asli", appDatabaseDocForm.nama_db_asli);
    appendValue(formData, "jumlah_tabel", appDatabaseDocForm.jumlah_tabel);
    appendValue(formData, "keterangan", appDatabaseDocForm.keterangan);
    appDatabaseDocForm.file.forEach((file) => formData.append("file", file));
  }

  return formData;
}

function setFiles(target, event) {
  target.files = Array.from(event.target.files || []);
}

function formatChangeFields(fields) {
  return Object.entries(fields || {}).map(
    ([field, values]) =>
      `${changeFieldLabel(field)}: ${values.before ?? "-"} ke ${values.after ?? "-"}`,
  );
}

async function createDataCenter() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    await api("/data-centers", {
      method: "POST",
      body: JSON.stringify(cleanPayload(dataCenterForm)),
    });
    resetModuleForm("data-centers");
    await loadAll();
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function createRack() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    await api("/racks", {
      method: "POST",
      body: JSON.stringify(cleanPayload(rackForm)),
    });
    resetModuleForm("racks");
    await loadAll();
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function createIsp() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    await api("/isps", {
      method: "POST",
      body: JSON.stringify(cleanPayload(ispForm)),
    });
    resetModuleForm("isps");
    await loadAll();
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function createIpAddress() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    const response = await api("/ip-addresses", {
      method: "POST",
      body: JSON.stringify(cleanPayload(ipAddressForm)),
    });
    resetModuleForm("ip-addresses");
    await loadAll();
    if (response?.created) {
      showAlert(
        "Bulk IP selesai",
        `${response.total_created || 0} IP dibuat. ${response.total_skipped || 0} IP dilewati karena sudah ada.`,
      );
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function createServer() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    await api("/servers", {
      method: "POST",
      body: JSON.stringify(cleanPayload(serverForm)),
    });
    resetModuleForm("servers");
    await loadAll();
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function createVm() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    await api("/vms", {
      method: "POST",
      body: JSON.stringify(cleanPayload(vmForm)),
    });
    resetModuleForm("vms");
    await loadAll();
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function createApplication() {
  if (!canWrite.value || saving.value) return;
  saving.value = true;
  try {
    await api("/applications", {
      method: "POST",
      body: JSON.stringify(cleanPayload(appForm)),
    });
    resetModuleForm("applications");
    await loadAll();
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function removeRow(kind, id) {
  if (!canWrite.value) {
    error.value = "Akses read only tidak dapat menghapus data.";
    return;
  }
  error.value = "";
  Object.assign(deleteModal, {
    open: true,
    kind,
    id,
    label: moduleLabels[kind] || "Data",
  });
}

async function createApiKey() {
  if (!canWrite.value || saving.value) return;

  saving.value = true;
  error.value = "";
  try {
    const response = await api("/api-keys", {
      method: "POST",
      body: JSON.stringify({}),
    });
    await loadAll();
    showAlert(
      "API key baru",
      `Salin dan simpan API key ini sekarang. Demi keamanan, key ini tidak dapat ditampilkan lagi: ${response.api_key}`,
    );
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}

function closeDeleteModal() {
  if (deleting.value) return;
  Object.assign(deleteModal, { open: false, kind: "", id: null, label: "" });
}

async function confirmDelete() {
  if (!canWrite.value || deleting.value || !deleteModal.kind || !deleteModal.id)
    return;
  deleting.value = true;
  error.value = "";
  try {
    await api(`/${deleteModal.kind}/${deleteModal.id}`, { method: "DELETE" });
    Object.assign(deleteModal, { open: false, kind: "", id: null, label: "" });
    await loadAll();
  } catch (err) {
    Object.assign(deleteModal, { open: false, kind: "", id: null, label: "" });
    if (err.status === 409 || err.type === "constraint_violation") {
      showAlert("Data tidak dapat dihapus", err.message);
      return;
    }
    error.value = err.message;
  } finally {
    deleting.value = false;
  }
}

function openRevealPassword(credential) {
  if (!canWrite.value || !credential?.has_password) return;
  Object.assign(revealPasswordModal, {
    open: true,
    loading: false,
    credential,
    account_password: "",
    revealed_password: "",
    error: "",
    copied: false,
  });
}

function closeRevealPasswordModal() {
  if (revealPasswordModal.loading) return;
  Object.assign(revealPasswordModal, {
    open: false,
    loading: false,
    credential: null,
    account_password: "",
    revealed_password: "",
    error: "",
    copied: false,
  });
}

async function submitRevealPassword() {
  if (
    !canWrite.value ||
    revealPasswordModal.loading ||
    !revealPasswordModal.credential?.id
  )
    return;
  revealPasswordModal.loading = true;
  revealPasswordModal.error = "";
  revealPasswordModal.revealed_password = "";
  revealPasswordModal.copied = false;

  try {
    const response = await api(
      `/network-credentials/${revealPasswordModal.credential.id}/reveal-password`,
      {
        method: "POST",
        body: JSON.stringify({
          account_password: revealPasswordModal.account_password,
        }),
      },
    );
    revealPasswordModal.revealed_password = response.password || "";
    revealPasswordModal.account_password = "";
  } catch (err) {
    revealPasswordModal.error = err.message;
  } finally {
    revealPasswordModal.loading = false;
  }
}

async function copyRevealedPassword() {
  if (!revealPasswordModal.revealed_password || !navigator.clipboard) return;
  await navigator.clipboard.writeText(revealPasswordModal.revealed_password);
  revealPasswordModal.copied = true;
}

function cleanPayload(source, options = {}) {
  return Object.fromEntries(
    Object.entries(source).filter(([, value]) => {
      if (Array.isArray(value)) return true;
      if (options.includeEmpty) return value !== undefined;
      return value !== "" && value !== null && value !== undefined;
    }),
  );
}

function handleBackdropPointerDown(event) {
  backdropPointerStartedOnSelf.value = event.target === event.currentTarget;
}

function closeFromBackdrop(event, closeFn) {
  const shouldClose =
    backdropPointerStartedOnSelf.value && event.target === event.currentTarget;
  backdropPointerStartedOnSelf.value = false;

  if (shouldClose) closeFn();
}

function toggleInArray(list, id) {
  const index = list.indexOf(id);
  if (index >= 0) list.splice(index, 1);
  else list.push(id);
}

const filteredServers = computed(() => {
  const needle = query.value.toLowerCase();
  return servers.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredDataCenters = computed(() => {
  const needle = query.value.toLowerCase();
  return dataCenters.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredRacks = computed(() => {
  const needle = query.value.toLowerCase();
  return racks.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredIsps = computed(() => {
  const needle = query.value.toLowerCase();
  return isps.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredIpAddresses = computed(() => {
  const needle = query.value.toLowerCase();
  return ipAddresses.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredVms = computed(() => {
  const needle = query.value.toLowerCase();
  return vms.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredApplications = computed(() => {
  const needle = query.value.toLowerCase();
  return applications.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredDataAssets = computed(() => {
  const needle = query.value.toLowerCase();
  return dataAssets.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredApplicationDocuments = computed(() => {
  const needle = query.value.toLowerCase();
  return applicationDocuments.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredAppIntegrations = computed(() => {
  const needle = query.value.toLowerCase();
  return appIntegrations.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredAppDatabaseDocs = computed(() => {
  const needle = query.value.toLowerCase();
  return appDatabaseDocs.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredBackupMedia = computed(() => {
  const needle = query.value.toLowerCase();
  return backupMedia.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredBackupJobs = computed(() => {
  const needle = query.value.toLowerCase();
  return backupJobs.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredUpsDevices = computed(() => {
  const needle = query.value.toLowerCase();
  return upsDevices.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredSocTools = computed(() => {
  const needle = query.value.toLowerCase();
  return socTools.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredNetworkSites = computed(() => {
  const needle = query.value.toLowerCase();
  return networkSites.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredNetworkDevices = computed(() => {
  const needle = query.value.toLowerCase();
  return networkDevices.value.filter((row) => {
    const matchesSearch = JSON.stringify(row).toLowerCase().includes(needle);
    const matchesOwner =
      !networkDeviceOpdFilter.value ||
      networkDeviceOwnerId(row) === networkDeviceOpdFilter.value;

    return matchesSearch && matchesOwner;
  });
});

const filteredNetworkInstallations = computed(() => {
  const needle = query.value.toLowerCase();
  return networkInstallations.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredNetworkIpConfigs = computed(() => {
  const needle = query.value.toLowerCase();
  return networkIpConfigs.value.filter((row) => {
    const matchesSearch = JSON.stringify(row).toLowerCase().includes(needle);
    const matchesOwner =
      !networkIpConfigOpdFilter.value ||
      networkIpConfigOwnerId(row) === networkIpConfigOpdFilter.value;

    return matchesSearch && matchesOwner;
  });
});

const filteredNetworkCredentials = computed(() => {
  const needle = query.value.toLowerCase();
  return networkCredentials.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredNetworkMonitorings = computed(() => {
  const needle = query.value.toLowerCase();
  return networkMonitorings.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredUsers = computed(() => {
  const needle = query.value.toLowerCase();
  return users.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const filteredApiKeys = computed(() => {
  const needle = query.value.toLowerCase();
  return apiKeys.value.filter((row) =>
    JSON.stringify(row).toLowerCase().includes(needle),
  );
});

const dataAssetRiskTotal = computed(
  () =>
    Number(dataAssetForm.confidentiality_score) +
    Number(dataAssetForm.integrity_score) +
    Number(dataAssetForm.availability_score),
);

const dataAssetCalculatedClassification = computed(() => {
  const total = dataAssetRiskTotal.value;
  if (total <= 7)
    return {
      code: "OPEN",
      name: "Data Elektronik Terbuka",
      risk: "LOW",
      color: "Hijau",
    };
  if (total <= 11)
    return {
      code: "LIMITED",
      name: "Data Elektronik Terbatas",
      risk: "MEDIUM",
      color: "Kuning",
    };
  return {
    code: "RESTRICTED",
    name: "Data Elektronik Tertutup",
    risk: "HIGH",
    color: "Merah",
  };
});

const statusClass = (status) => `status status-${status || "unknown"}`;
const yesNo = (value) => (value ? "Ya" : "Tidak");

watch(
  [
    () => activeTab.value,
    () => bulkLabelForm.module,
    () => bulkLabelForm.size,
    () => bulkLabelItems.value.length,
  ],
  () => {
    if (activeTab.value === "bulk-labels") {
      generateBulkLabelQrs();
    }
  },
);

onMounted(async () => {
  await bootstrapAuth();
  await nextTick();
  syncTableCardLabels();
});

onUpdated(() => {
  nextTick(syncTableCardLabels);
});
</script>

<template>
  <div v-if="!currentUser" class="login-shell">
    <section class="login-identity">
      <div class="login-identity-inner">
        <div class="login-badge">
          <img
            class="brand-logo small"
            :src="logoLangkat"
            alt="Logo Kabupaten Langkat"
          />
          <span>CMDB Kabupaten Langkat</span>
        </div>
        <div>
          <p class="eyebrow">Single Source of Truth</p>
          <h1>IAMT CMDB Langkat</h1>
          <p class="login-copy">
            Manajemen aset digital, aplikasi, infrastruktur, klasifikasi data,
            audit, dan kepatuhan dalam satu ruang kerja terpadu.
          </p>
        </div>
        <div class="login-feature-grid">
          <div><Server :size="22" /><span>Infrastructure</span></div>
          <div><AppWindow :size="22" /><span>Applications</span></div>
          <div><ShieldCheck :size="22" /><span>Security</span></div>
          <div><Activity :size="22" /><span>Audit Trail</span></div>
        </div>
      </div>
    </section>

    <section class="login-panel">
      <form class="login-card" @submit.prevent="login">
        <div class="login-card-head">
          <div class="brand-mark">
            <img :src="logoLangkat" alt="Logo Kabupaten Langkat" />
          </div>
          <div>
            <p class="eyebrow">Autentikasi</p>
            <h2>Masuk Aplikasi</h2>
          </div>
        </div>
        <label class="field-label">
          <span>Email</span>
          <input
            v-model="authForm.email"
            required
            type="email"
            autocomplete="username"
            placeholder="nama@langkatkab.go.id"
          />
        </label>
        <label class="field-label">
          <span>Password</span>
          <input
            v-model="authForm.password"
            required
            type="password"
            autocomplete="current-password"
            placeholder="Masukkan password"
          />
        </label>
        <button class="action-button login-submit" type="submit">Masuk</button>
        <div v-if="error" class="alert">
          <AlertTriangle :size="18" />
          {{ error }}
        </div>
      </form>
      <div class="login-footnote">
        <span>Role-based access</span>
        <strong>Full / Read Only</strong>
      </div>
    </section>
  </div>

  <div v-else class="app-shell">
    <div
      class="mobile-nav-backdrop"
      :class="{ open: mobileNavOpen }"
      @click="mobileNavOpen = false"
    ></div>

    <aside class="sidebar" :class="{ open: mobileNavOpen }">
      <div class="brand">
        <div class="brand-mark">
          <img :src="logoLangkat" alt="Logo Kabupaten Langkat" />
        </div>
        <div>
          <p class="eyebrow">Kabupaten Langkat</p>
          <h1>IAMT CMDB</h1>
        </div>
        <button
          class="icon-button mobile-sidebar-close"
          type="button"
          title="Tutup menu"
          @click="mobileNavOpen = false"
        >
          <X :size="18" />
        </button>
      </div>

      <nav class="nav-list">
        <section
          v-for="(section, index) in menuSections"
          :key="section.label || index"
          class="nav-section"
        >
          <p v-if="section.label" class="nav-section-title">
            {{ section.label }}
          </p>
          <button
            v-for="item in section.items"
            :key="item.id"
            class="nav-item"
            :class="{ active: activeTab === item.id, disabled: item.disabled }"
            type="button"
            :disabled="item.disabled"
            @click="selectMenu(item)"
          >
            <component :is="item.icon" :size="18" />
            <span>{{ item.label }}</span>
            <small v-if="item.disabled">TODO</small>
          </button>
        </section>
      </nav>

      <div class="sidebar-panel">
        <p class="panel-title yellow-title">SPBE & PSE</p>
        <strong>{{ dashboard?.capacity.security_coverage ?? 0 }}%</strong>
        <span>coverage security server</span>
      </div>
    </aside>

    <main class="workspace">
      <header class="topbar">
        <button
          class="icon-button primary mobile-menu-button"
          type="button"
          title="Buka menu"
          @click="mobileNavOpen = true"
        >
          <Menu :size="20" />
        </button>
        <div>
          <p class="eyebrow">Single Source of Truth</p>
          <h3>Manajemen Aset Digital</h3>
        </div>
        <div class="top-actions">
          <div class="user-pill">
            <strong>{{ currentUser.nama }}</strong>
            <span>{{
              currentUser.role === "full" ? "Full Access" : "Read Only"
            }}</span>
          </div>
          <label class="search">
            <Search :size="17" />
            <input
              v-model="query"
              type="search"
              placeholder="Cari aset, OPD, status"
            />
          </label>
          <button
            class="icon-button primary"
            type="button"
            title="Refresh"
            @click="loadAll"
          >
            <RefreshCw :size="18" :class="{ spin: loading }" />
          </button>
          <button
            class="action-button ghost compact-button"
            type="button"
            @click="logout"
          >
            Keluar
          </button>
        </div>
      </header>

      <div v-if="error" class="alert">
        <AlertTriangle :size="18" />
        {{ error }}
      </div>

      <section v-if="activeTab === 'bulk-labels'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Inventaris</p>
              <h3 class="yellow-title">Cetak Label Massal</h3>
            </div>
            <button
              class="action-button"
              type="button"
              :disabled="bulkLabelGenerating || !bulkLabelItems.length"
              @click="printBulkLabels"
            >
              <Printer :size="17" />
              Cetak A4
            </button>
          </div>

          <div class="bulk-label-controls">
            <label class="field-label">
              <span>Jenis Aset</span>
              <select v-model="bulkLabelForm.module">
                <option
                  v-for="option in bulkLabelModuleOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </label>
            <label class="field-label">
              <span>Ukuran Label</span>
              <select v-model="bulkLabelForm.size">
                <option
                  v-for="option in labelSizeOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </label>
            <div class="bulk-label-summary">
              <span>Total Label</span>
              <strong>{{ bulkLabelItems.length }}</strong>
              <small>{{
                bulkLabelGenerating
                  ? "Menyiapkan QR"
                  : moduleLabels[bulkLabelForm.module]
              }}</small>
            </div>
          </div>
        </section>

        <section class="surface wide">
          <div class="label-preview-wrap bulk-preview-wrap">
            <article class="bulk-label-print" :style="bulkLabelPrintStyle">
              <div v-if="!bulkLabelItems.length" class="bulk-empty-state">
                <strong
                  >Belum ada data
                  {{ moduleLabels[bulkLabelForm.module] }}</strong
                >
              </div>
              <div v-else class="bulk-label-grid">
                <article
                  v-for="item in bulkLabelItems"
                  :key="item.id"
                  class="inventory-label-print bulk-inventory-label"
                >
                  <div class="label-brand">
                    <img :src="logoLangkat" alt="Logo Kabupaten Langkat" />
                    <div>
                      <strong>PEMKAB LANGKAT</strong>
                      <span>IAMT CMDB</span>
                    </div>
                  </div>
                  <div class="label-body">
                    <div>
                      <small>Kode Aset</small>
                      <strong>{{ assetCode(item) }}</strong>
                      <span>{{
                        moduleLabels[bulkLabelForm.module] || "Aset"
                      }}</span>
                      <b>{{ assetName(item, bulkLabelForm.module) }}</b>
                      <em>{{ assetLocation(bulkLabelForm.module, item) }}</em>
                    </div>
                    <img
                      v-if="
                        bulkLabelQrUrls[
                          bulkLabelKey(bulkLabelForm.module, item)
                        ]
                      "
                      :src="
                        bulkLabelQrUrls[
                          bulkLabelKey(bulkLabelForm.module, item)
                        ]
                      "
                      alt="QR detail aset"
                    />
                    <div v-else class="qr-placeholder">QR</div>
                  </div>
                  <footer>
                    {{ assetDetailUrl(bulkLabelForm.module, item) }}
                  </footer>
                </article>
              </div>
            </article>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'dashboard'" class="content-grid">
        <div class="metrics-row">
          <article
            v-for="metric in dashboard?.metrics || []"
            :key="metric.label"
            class="metric-card"
            :class="metric.tone"
          >
            <span>{{ metric.label }}</span>
            <strong>{{ metric.value }}</strong>
            <small>{{ metric.hint }}</small>
          </article>
        </div>

        <section class="surface wide">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Kapasitas Infrastruktur</p>
              <h3 class="yellow-title">Ringkasan Operasional</h3>
            </div>
            <ShieldCheck class="title-icon" :size="30" />
          </div>
          <div class="capacity-grid">
            <div>
              <HardDrive :size="22" />
              <span>CPU</span>
              <strong>{{ dashboard?.capacity.cpu_core || 0 }} core</strong>
            </div>
            <div>
              <Box :size="22" />
              <span>RAM</span>
              <strong>{{ dashboard?.capacity.ram_gb || 0 }} GB</strong>
            </div>
            <div>
              <Database :size="22" />
              <span>Storage</span>
              <strong>{{ dashboard?.capacity.storage_gb || 0 }} GB</strong>
            </div>
            <div>
              <ShieldCheck :size="22" />
              <span>Security</span>
              <strong>{{ dashboard?.capacity.security_coverage || 0 }}%</strong>
            </div>
          </div>
        </section>

        <section class="surface">
          <div class="section-heading compact">
            <h3>Aplikasi Prioritas</h3>
            <FileCheck2 :size="24" />
          </div>
          <div class="priority-list">
            <article
              v-for="app in dashboard?.priority || []"
              :key="app.id"
              class="mini-row"
            >
              <div>
                <strong>{{ app.nama }}</strong>
                <span>{{ app.opd?.nama || "OPD belum dipilih" }}</span>
              </div>
              <span :class="statusClass(app.status)">{{ app.status }}</span>
            </article>
          </div>
        </section>

        <section class="surface">
          <div class="section-heading compact">
            <h3>Status Layanan</h3>
            <Activity :size="24" />
          </div>
          <div class="status-stack">
            <div v-for="(group, key) in dashboard?.status || {}" :key="key">
              <span>{{ key }}</span>
              <strong>{{
                Object.values(group).reduce((a, b) => a + b, 0)
              }}</strong>
              <small>{{
                Object.entries(group)
                  .map(([name, total]) => `${name}: ${total}`)
                  .join(" / ")
              }}</small>
            </div>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'assets'" class="content-grid">
        <section class="surface wide">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Infrastruktur</p>
              <h3 class="yellow-title">
                Data Center, Rack, Server, VM, IP & ISP
              </h3>
            </div>
            <Network :size="30" />
          </div>

          <div v-if="canWrite" class="split-grid infrastructure-forms">
            <form class="form-panel" @submit.prevent="createDataCenter">
              <h4>Data Center Baru</h4>
              <input
                v-model="dataCenterForm.nama"
                required
                placeholder="Nama data center"
              />
              <input v-model="dataCenterForm.lokasi" placeholder="Lokasi" />
              <select v-model="dataCenterForm.tipe" required>
                <option value="">Tipe data center</option>
                <option value="utama">Utama</option>
                <option value="dr">Disaster Recovery</option>
                <option value="cloud">Cloud</option>
              </select>
              <button class="action-button" type="submit" :disabled="saving">
                <Plus :size="17" /> Tambah Data Center
              </button>
            </form>

            <form class="form-panel" @submit.prevent="createRack">
              <h4>Rack Baru</h4>
              <input v-model="rackForm.nama" required placeholder="Nama rack" />
              <select v-model="rackForm.dc_id" required>
                <option value="">Data center</option>
                <option
                  v-for="dc in references.data_centers"
                  :key="dc.id"
                  :value="dc.id"
                >
                  {{ dc.nama }}
                </option>
              </select>
              <input
                v-model.number="rackForm.kapasitas_u"
                required
                type="number"
                min="1"
                max="60"
                placeholder="Kapasitas U"
              />
              <button
                class="action-button secondary"
                type="submit"
                :disabled="saving"
              >
                <Plus :size="17" /> Tambah Rack
              </button>
            </form>

            <form class="form-panel" @submit.prevent="createIsp">
              <h4>ISP Baru</h4>
              <input v-model="ispForm.nama" required placeholder="Nama ISP" />
              <div class="two-col">
                <input v-model="ispForm.tipe" placeholder="Tipe koneksi" />
                <input v-model="ispForm.bandwidth" placeholder="Bandwidth" />
              </div>
              <input v-model="ispForm.kontak" placeholder="Kontak NOC / PIC" />
              <button class="action-button" type="submit" :disabled="saving">
                <Plus :size="17" /> Tambah ISP
              </button>
            </form>

            <form class="form-panel" @submit.prevent="createIpAddress">
              <h4>IP Address Baru</h4>
              <input
                v-model="ipAddressForm.ip"
                required
                placeholder="Alamat IP atau network CIDR"
              />
              <div class="two-col">
                <select v-model="ipAddressForm.jenis" required>
                  <option value="">Jenis IP</option>
                  <option value="private">Private</option>
                  <option value="publik">Publik</option>
                </select>
                <select v-model="ipAddressForm.isp_id">
                  <option value="">Tanpa ISP</option>
                  <option
                    v-for="isp in references.isps"
                    :key="isp.id"
                    :value="isp.id"
                  >
                    {{ isp.nama }}
                  </option>
                </select>
              </div>
              <input
                v-model="ipAddressForm.assignment"
                placeholder="Assignment / penggunaan IP"
              />
              <button
                class="action-button secondary"
                type="submit"
                :disabled="saving"
              >
                <Plus :size="17" /> Tambah IP
              </button>
            </form>
          </div>

          <div class="split-grid">
            <form class="form-panel" @submit.prevent="createServer">
              <h4>Server Baru</h4>
              <input
                v-model="serverForm.nama"
                required
                placeholder="Nama server"
              />
              <div class="two-col">
                <select v-model="serverForm.dc_id">
                  <option value="">Data center</option>
                  <option
                    v-for="dc in references.data_centers"
                    :key="dc.id"
                    :value="dc.id"
                  >
                    {{ dc.nama }}
                  </option>
                </select>
                <select v-model="serverForm.rack_id">
                  <option value="">Rack</option>
                  <option
                    v-for="rack in references.racks"
                    :key="rack.id"
                    :value="rack.id"
                  >
                    {{ rack.nama }}
                  </option>
                </select>
              </div>
              <input
                v-model.number="serverForm.rack_size_u"
                type="number"
                min="1"
                max="60"
                placeholder="Rack Size (U)"
              />
              <div class="two-col">
                <input v-model="serverForm.merk" placeholder="Merk" />
                <input v-model="serverForm.tipe" placeholder="Tipe" />
              </div>
              <div class="two-col">
                <input
                  v-model="serverForm.merk_processor"
                  placeholder="Merk processor"
                />
                <input
                  v-model.number="serverForm.tahun"
                  type="number"
                  min="2000"
                  max="2100"
                  placeholder="Tahun"
                />
              </div>
              <div class="three-col">
                <input
                  v-model.number="serverForm.cpu_core"
                  type="number"
                  min="1"
                  placeholder="Core"
                />
                <input
                  v-model.number="serverForm.ram_gb"
                  type="number"
                  min="1"
                  placeholder="RAM GB"
                />
                <input
                  v-model.number="serverForm.storage_gb"
                  type="number"
                  min="1"
                  placeholder="Storage GB"
                />
              </div>
              <button class="action-button" type="submit" :disabled="saving">
                <Plus :size="17" /> Tambah Server
              </button>
            </form>

            <form class="form-panel" @submit.prevent="createVm">
              <h4>VM Baru</h4>
              <input v-model="vmForm.nama" required placeholder="Nama VM" />
              <select v-model="vmForm.server_id">
                <option value="">Host server</option>
                <option
                  v-for="server in references.servers"
                  :key="server.id"
                  :value="server.id"
                >
                  {{ server.nama }}
                </option>
              </select>
              <input v-model="vmForm.os" placeholder="Operating system" />
              <div class="three-col">
                <input
                  v-model.number="vmForm.vcpu"
                  type="number"
                  min="1"
                  placeholder="vCPU"
                />
                <input
                  v-model.number="vmForm.ram_gb"
                  type="number"
                  min="1"
                  placeholder="RAM GB"
                />
                <input
                  v-model.number="vmForm.storage_gb"
                  type="number"
                  min="1"
                  placeholder="Storage GB"
                />
              </div>
              <div class="inline-picker">
                <strong>IP Address</strong>
                <button
                  v-for="ip in references.ips"
                  :key="ip.id"
                  class="chip"
                  :class="{ selected: vmForm.ip_ids.includes(ip.id) }"
                  type="button"
                  @click="toggleInArray(vmForm.ip_ids, ip.id)"
                >
                  {{ ip.ip }}
                </button>
              </div>
              <button
                class="action-button secondary"
                type="submit"
                :disabled="saving"
              >
                <Plus :size="17" /> Tambah VM
              </button>
            </form>
          </div>
        </section>

        <section class="surface wide">
          <div class="section-heading compact">
            <h3>Data Center & Rack</h3>
            <Building2 :size="24" />
          </div>
          <div class="two-table-grid">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Data Center</th>
                    <th>Lokasi</th>
                    <th>Tipe</th>
                    <th>Rack</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(dc, index) in filteredDataCenters" :key="dc.id">
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ dc.nama }}</strong>
                    </td>
                    <td>{{ dc.lokasi || "-" }}</td>
                    <td>
                      <span :class="statusClass(dc.tipe)">{{ dc.tipe }}</span>
                    </td>
                    <td>{{ dc.racks_count || 0 }}</td>
                    <td>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus data center"
                        @click="removeRow('data-centers', dc.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Rack</th>
                    <th>Data Center</th>
                    <th>Kapasitas</th>
                    <th>Server</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(rack, index) in filteredRacks" :key="rack.id">
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ rack.nama }}</strong>
                    </td>
                    <td>{{ rack.data_center?.nama || "-" }}</td>
                    <td>{{ rack.kapasitas_u || 0 }}U</td>
                    <td>{{ rack.servers_count || 0 }}</td>
                    <td>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus rack"
                        @click="removeRow('racks', rack.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="surface wide">
          <div class="section-heading compact">
            <h3>IP Address & ISP</h3>
            <Network :size="24" />
          </div>
          <div class="two-table-grid">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>No</th>
                    <th>IP Address</th>
                    <th>Jenis</th>
                    <th>ISP</th>
                    <th>VM</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ip, index) in filteredIpAddresses" :key="ip.id">
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ ip.ip }}</strong>
                    </td>
                    <td>
                      <span :class="statusClass(ip.jenis)">{{ ip.jenis }}</span>
                    </td>
                    <td>
                      {{ ip.isp?.nama || "-"
                      }}<span>{{ ip.isp?.bandwidth || "" }}</span>
                    </td>
                    <td>
                      {{ ipVmNames(ip) }}<span>{{ ip.vms_count || 0 }} VM</span>
                    </td>
                    <td>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus IP address"
                        @click="removeRow('ip-addresses', ip.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>No</th>
                    <th>ISP</th>
                    <th>Tipe</th>
                    <th>Bandwidth</th>
                    <th>IP</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(isp, index) in filteredIsps" :key="isp.id">
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ isp.nama }}</strong
                      ><span>{{ isp.kontak || "-" }}</span>
                    </td>
                    <td>{{ isp.tipe || "-" }}</td>
                    <td>{{ isp.bandwidth || "-" }}</td>
                    <td>{{ isp.ip_addresses_count || 0 }}</td>
                    <td>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus ISP"
                        @click="removeRow('isps', isp.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="surface wide">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Server</th>
                  <th>Lokasi</th>
                  <th>Kapasitas</th>
                  <th>Status</th>
                  <th>VM</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(server, index) in filteredServers" :key="server.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ server.nama }}</strong
                    ><span
                      >{{ server.merk }} {{ server.tipe }} /
                      {{ server.tahun || "-" }}</span
                    >
                  </td>
                  <td>
                    {{ server.data_center?.nama || "-"
                    }}<span
                      >{{ server.rack?.nama || "-"
                      }}{{
                        server.rack_size_u ? ` / ${server.rack_size_u}U` : ""
                      }}</span
                    >
                  </td>
                  <td>
                    {{ server.cpu_core }} core / {{ server.ram_gb }} GB<span
                      >{{ server.merk_processor || "Processor belum diisi" }} /
                      {{ server.storage_gb }} GB storage</span
                    >
                  </td>
                  <td>
                    <span :class="statusClass(server.status)">{{
                      server.status
                    }}</span>
                  </td>
                  <td>{{ server.vms?.length || 0 }}</td>
                  <td>
                    <button
                      v-if="canWrite"
                      class="icon-button danger"
                      title="Hapus server"
                      @click="removeRow('servers', server.id)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="surface wide">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>VM</th>
                  <th>Host</th>
                  <th>OS</th>
                  <th>Kapasitas</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(vm, index) in filteredVms" :key="vm.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ vm.nama }}</strong>
                  </td>
                  <td>{{ vm.server?.nama || "-" }}</td>
                  <td>{{ vm.os }}</td>
                  <td>
                    {{ vm.vcpu }} vCPU / {{ vm.ram_gb }} GB<span
                      >{{ vm.storage_gb }} GB storage</span
                    >
                  </td>
                  <td>
                    <span :class="statusClass(vm.status)">{{ vm.status }}</span>
                  </td>
                  <td>
                    <button
                      v-if="canWrite"
                      class="icon-button danger"
                      title="Hapus VM"
                      @click="removeRow('vms', vm.id)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'data-centers'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Master Infrastruktur</p>
              <h3 class="yellow-title">Data Center</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('data-centers')"
            >
              <Plus :size="17" /> Tambah Data Center
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Data Center</th>
                  <th>Lokasi</th>
                  <th>Tipe</th>
                  <th>Jumlah Rack</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(dc, index) in filteredDataCenters" :key="dc.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ dc.nama }}</strong
                    ><span>{{ assetCode(dc) }}</span>
                  </td>
                  <td>{{ dc.lokasi || "-" }}</td>
                  <td>
                    <span :class="statusClass(dc.tipe)">{{ dc.tipe }}</span>
                  </td>
                  <td>{{ dc.racks_count || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label data center"
                        @click="openLabel('data-centers', dc)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit data center"
                        @click="openEdit('data-centers', dc)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin data center"
                        @click="openCopy('data-centers', dc)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus data center"
                        @click="removeRow('data-centers', dc.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'racks'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Master Infrastruktur</p>
              <h3 class="yellow-title">Rack</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('racks')"
            >
              <Plus :size="17" /> Tambah Rack
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Rack</th>
                  <th>Data Center</th>
                  <th>Kapasitas</th>
                  <th>Jumlah Server</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(rack, index) in filteredRacks" :key="rack.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ rack.nama }}</strong
                    ><span>{{ assetCode(rack) }}</span>
                  </td>
                  <td>
                    {{ rack.data_center?.nama || "-"
                    }}<span>{{ rack.data_center?.lokasi || "" }}</span>
                  </td>
                  <td>{{ rack.kapasitas_u || 0 }}U</td>
                  <td>{{ rack.servers_count || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label rack"
                        @click="openLabel('racks', rack)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit rack"
                        @click="openEdit('racks', rack)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin rack"
                        @click="openCopy('racks', rack)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus rack"
                        @click="removeRow('racks', rack.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'servers'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Aset Fisik</p>
              <h3 class="yellow-title">Server Baremetal</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('servers')"
            >
              <Plus :size="17" /> Tambah Server
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Server</th>
                  <th>Lokasi</th>
                  <th>Kapasitas</th>
                  <th>Kondisi</th>
                  <th>Status</th>
                  <th>VM</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(server, index) in filteredServers" :key="server.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ server.nama }}</strong
                    ><span>{{ assetCode(server) }}</span
                    ><span
                      >{{ server.merk }} {{ server.tipe }} /
                      {{ server.tahun || "-" }}</span
                    >
                  </td>
                  <td>
                    {{ server.data_center?.nama || "-"
                    }}<span
                      >{{ server.rack?.nama || "-"
                      }}{{
                        server.rack_size_u ? ` / ${server.rack_size_u}U` : ""
                      }}</span
                    >
                  </td>
                  <td>
                    {{ server.cpu_core }} core / {{ server.ram_gb }} GB<span
                      >{{ server.merk_processor || "Processor belum diisi" }} /
                      {{ server.storage_gb }} GB storage</span
                    >
                  </td>
                  <td>
                    <span :class="statusClass(server.kondisi)">{{
                      server.kondisi || "-"
                    }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(server.status)">{{
                      server.status
                    }}</span>
                  </td>
                  <td>{{ server.vms?.length || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label server"
                        @click="openLabel('servers', server)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit server"
                        @click="openEdit('servers', server)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin server"
                        @click="openCopy('servers', server)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus server"
                        @click="removeRow('servers', server.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'vms'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Virtualisasi</p>
              <h3 class="yellow-title">Virtual Machine</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('vms')"
            >
              <Plus :size="17" /> Tambah VM
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>VM</th>
                  <th>Host</th>
                  <th>OS</th>
                  <th>Kapasitas</th>
                  <th>IP Address</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(vm, index) in filteredVms" :key="vm.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ vm.nama }}</strong
                    ><span>{{ assetCode(vm) }}</span>
                  </td>
                  <td>{{ vm.server?.nama || "-" }}</td>
                  <td>{{ vm.os || "-" }}</td>
                  <td>
                    {{ vm.vcpu }} vCPU / {{ vm.ram_gb }} GB<span
                      >{{ vm.storage_gb }} GB storage</span
                    >
                  </td>
                  <td>
                    {{
                      (vm.ip_addresses || []).map((ip) => ip.ip).join(", ") ||
                      "-"
                    }}
                  </td>
                  <td>
                    <span :class="statusClass(vm.status)">{{ vm.status }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label VM"
                        @click="openLabel('vms', vm)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit VM"
                        @click="openEdit('vms', vm)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin VM"
                        @click="openCopy('vms', vm)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus VM"
                        @click="removeRow('vms', vm.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'ip-addresses'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Network</p>
              <h3 class="yellow-title">IP Address</h3>
            </div>
            <div class="row-actions">
              <button
                v-if="canWrite"
                class="action-button ghost compact-button"
                type="button"
                :disabled="pingingAllIps"
                @click="refreshAllIpPings"
              >
                <RefreshCw :size="17" :class="{ spin: pingingAllIps }" /> Ping
                Semua
              </button>
              <button
                v-if="canWrite"
                class="action-button"
                type="button"
                @click="openCreate('ip-addresses')"
              >
                <Plus :size="17" /> Tambah IP Address
              </button>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>IP Address</th>
                  <th>Jenis</th>
                  <th>Assignment</th>
                  <th>ISP</th>
                  <th>Ping</th>
                  <th>VM Terkait</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ip, index) in filteredIpAddresses" :key="ip.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ ip.ip }}</strong
                    ><span>{{ assetCode(ip) }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(ip.jenis)">{{ ip.jenis }}</span>
                  </td>
                  <td>{{ ip.assignment || "-" }}</td>
                  <td>
                    {{ ip.isp?.nama || "-"
                    }}<span>{{ ip.isp?.bandwidth || "" }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(ip.ping_status || 'unknown')">{{
                      pingLabel(ip)
                    }}</span
                    ><span>{{ pingMeta(ip) }}</span>
                  </td>
                  <td>
                    {{ ipVmNames(ip) }}<span>{{ ip.vms_count || 0 }} VM</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label IP address"
                        @click="openLabel('ip-addresses', ip)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Refresh ping"
                        :disabled="pingingIps[ip.id]"
                        @click="refreshIpPing(ip)"
                      >
                        <RefreshCw
                          :size="16"
                          :class="{ spin: pingingIps[ip.id] }"
                        />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit IP address"
                        @click="openEdit('ip-addresses', ip)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin IP address"
                        @click="openCopy('ip-addresses', ip)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus IP address"
                        @click="removeRow('ip-addresses', ip.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'isps'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Network</p>
              <h3 class="yellow-title">Internet Service Provider</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('isps')"
            >
              <Plus :size="17" /> Tambah ISP
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>ISP</th>
                  <th>Tipe</th>
                  <th>Bandwidth</th>
                  <th>Kontak</th>
                  <th>Jumlah IP</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(isp, index) in filteredIsps" :key="isp.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ isp.nama }}</strong
                    ><span>{{ assetCode(isp) }}</span>
                  </td>
                  <td>{{ isp.tipe || "-" }}</td>
                  <td>{{ isp.bandwidth || "-" }}</td>
                  <td>{{ isp.kontak || "-" }}</td>
                  <td>{{ isp.ip_addresses_count || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label ISP"
                        @click="openLabel('isps', isp)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit ISP"
                        @click="openEdit('isps', isp)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin ISP"
                        @click="openCopy('isps', isp)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus ISP"
                        @click="removeRow('isps', isp.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'applications'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Core CMDB</p>
              <h3 class="yellow-title">Aplikasi dan Relasi Infrastruktur</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('applications')"
            >
              <Plus :size="17" /> Tambah Aplikasi
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aplikasi</th>
                  <th>OPD</th>
                  <th>Jenis</th>
                  <th>Pengembang</th>
                  <th>Fungsi</th>
                  <th>Tech Stack</th>
                  <th>Target SLA</th>
                  <th>Aset Data</th>
                  <th>Relasi</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(app, index) in filteredApplications" :key="app.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ app.nama }}</strong
                    ><span>{{ assetCode(app) }}</span
                    ><span>{{ app.url || "-" }}</span>
                  </td>
                  <td>{{ app.opd?.nama || "-" }}</td>
                  <td>
                    <span class="status">{{ app.jenis_aplikasi || "-" }}</span>
                  </td>
                  <td>
                    {{ app.pengembang ? developerLabel(app.pengembang) : "-" }}
                  </td>
                  <td>
                    {{
                      (app.klasifikasi_fungsi || [])
                        .map(functionClassificationLabel)
                        .join(", ") || "-"
                    }}
                  </td>
                  <td>{{ app.tech_stack || "-" }}</td>
                  <td>{{ app.sla_persen || 0 }}%</td>
                  <td>{{ app.data_assets?.length || 0 }}</td>
                  <td>
                    {{ app.vms?.length || 0 }} VM /
                    {{ app.servers?.length || 0 }} server
                  </td>
                  <td>
                    <span :class="statusClass(app.status)">{{
                      app.status
                    }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label aplikasi"
                        @click="openLabel('applications', app)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit aplikasi"
                        @click="openEdit('applications', app)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin aplikasi"
                        @click="openCopy('applications', app)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus aplikasi"
                        @click="removeRow('applications', app.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'data-assets'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Permenkomdigi No. 5 Tahun 2025</p>
              <h3 class="yellow-title">Data Aplikasi</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('data-assets')"
            >
              <Plus :size="17" /> Tambah Data Aplikasi
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aset Data</th>
                  <th>Aplikasi</th>
                  <th>Tipe</th>
                  <th>Klasifikasi</th>
                  <th>Risiko</th>
                  <th>K/I/K</th>
                  <th>Data Pribadi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(asset, index) in filteredDataAssets"
                  :key="asset.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ asset.name }}</strong
                    ><span>{{ assetCode(asset) }}</span
                    ><span>{{
                      [asset.table_name, asset.column_name]
                        .filter(Boolean)
                        .join(".") ||
                      asset.description ||
                      "-"
                    }}</span>
                  </td>
                  <td>
                    {{ asset.aplikasi?.nama || "-"
                    }}<span>{{ asset.aplikasi?.jenis_aplikasi || "" }}</span>
                  </td>
                  <td>
                    <span class="status">{{ asset.type }}</span>
                  </td>
                  <td>
                    {{ asset.classification?.name || "-"
                    }}<span>{{ asset.classification?.code || "" }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(asset.classification?.risk_level)"
                      >{{ asset.risk_total || 0 }} /
                      {{ asset.classification?.risk_level || "-" }}</span
                    >
                  </td>
                  <td>
                    {{ asset.confidentiality_score || "-" }}/{{
                      asset.integrity_score || "-"
                    }}/{{ asset.availability_score || "-" }}
                  </td>
                  <td>
                    {{ yesNo(asset.contains_personal_data)
                    }}<span>{{ asset.personal_data_type || "" }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label data aplikasi"
                        @click="openLabel('data-assets', asset)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit data aplikasi"
                        @click="openEdit('data-assets', asset)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin data aplikasi"
                        @click="openCopy('data-assets', asset)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus data aplikasi"
                        @click="removeRow('data-assets', asset.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section
        v-if="activeTab === 'application-documents'"
        class="content-grid"
      >
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Dokumen Aplikasi</p>
              <h3 class="yellow-title">Dokumen</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('application-documents')"
            >
              <Plus :size="17" /> Tambah Dokumen
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aplikasi</th>
                  <th>Kategori</th>
                  <th>File</th>
                  <th>Ukuran</th>
                  <th>Tanggal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(doc, index) in filteredApplicationDocuments"
                  :key="doc.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>{{ doc.aplikasi?.nama || "-" }}</td>
                  <td>
                    <span class="status">{{
                      doc.document_category || doc.jenis
                    }}</span>
                  </td>
                  <td>
                    <strong>{{ doc.original_name || doc.nama }}</strong
                    ><span>{{ assetCode(doc) }}</span
                    ><span>{{ doc.path || "-" }}</span>
                  </td>
                  <td>
                    {{
                      doc.size_bytes
                        ? Math.round(doc.size_bytes / 1024) + " KB"
                        : "-"
                    }}
                  </td>
                  <td>{{ doc.tanggal || "-" }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label dokumen"
                        @click="openLabel('application-documents', doc)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit dokumen"
                        @click="openEdit('application-documents', doc)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin dokumen"
                        @click="openCopy('application-documents', doc)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus dokumen"
                        @click="removeRow('application-documents', doc.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'app-integrations'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Interoperabilitas</p>
              <h3 class="yellow-title">Integrasi Aplikasi</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('app-integrations')"
            >
              <Plus :size="17" /> Tambah Integrasi
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aplikasi</th>
                  <th>Jenis</th>
                  <th>Metode</th>
                  <th>Target</th>
                  <th>Data</th>
                  <th>Dokumen</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(integration, index) in filteredAppIntegrations"
                  :key="integration.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ integration.aplikasi?.nama || "-" }}</strong
                    ><span>{{ assetCode(integration) }}</span
                    ><span>{{ integration.deskripsi || "-" }}</span>
                  </td>
                  <td>
                    <span class="status">{{
                      integration.jenis_integrasi
                    }}</span>
                  </td>
                  <td>
                    <span class="status">{{
                      integration.metode_integrasi
                    }}</span>
                  </td>
                  <td>
                    {{
                      (integration.target_applications || [])
                        .map((app) => app.nama)
                        .join(", ") ||
                      integration.external_endpoints ||
                      "-"
                    }}
                  </td>
                  <td>
                    {{
                      (integration.data_assets || [])
                        .map((asset) => asset.name)
                        .join(", ") || "-"
                    }}
                  </td>
                  <td>{{ integration.documents?.length || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label integrasi"
                        @click="openLabel('app-integrations', integration)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit integrasi"
                        @click="openEdit('app-integrations', integration)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin integrasi"
                        @click="openCopy('app-integrations', integration)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus integrasi"
                        @click="removeRow('app-integrations', integration.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'app-database-docs'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Dokumentasi Basis Data</p>
              <h3 class="yellow-title">Dok. Basis Data</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('app-database-docs')"
            >
              <Plus :size="17" /> Tambah Database
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aplikasi</th>
                  <th>Nama DB</th>
                  <th>DBMS</th>
                  <th>Host</th>
                  <th>Port</th>
                  <th>Tabel</th>
                  <th>Dokumen</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(doc, index) in filteredAppDatabaseDocs"
                  :key="doc.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ doc.aplikasi?.nama || "-" }}</strong>
                  </td>
                  <td>
                    <strong>{{ doc.nama_database }}</strong
                    ><span>{{
                      doc.nama_db_asli ? `(${doc.nama_db_asli})` : ""
                    }}</span
                    ><span>{{ assetCode(doc) }}</span>
                  </td>
                  <td>
                    <span class="status">{{ doc.tipe_dbms }}</span
                    ><span>{{ doc.versi || "" }}</span>
                  </td>
                  <td>{{ doc.host || "-" }}</td>
                  <td>{{ doc.port ?? "-" }}</td>
                  <td>{{ doc.jumlah_tabel ?? "-" }}</td>
                  <td>
                    {{
                      doc.original_name
                        ? Math.round(doc.size_bytes / 1024) + " KB"
                        : "-"
                    }}
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label database"
                        @click="openLabel('app-database-docs', doc)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit database"
                        @click="openEdit('app-database-docs', doc)"
                      >
                        <Pencil :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin database"
                        @click="openCopy('app-database-docs', doc)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus database"
                        @click="removeRow('app-database-docs', doc.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredAppDatabaseDocs.length === 0">
                  <td colspan="9">
                    Belum ada data basis data. Klik "Tambah Database" untuk
                    menambahkan.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'backup-media'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Keamanan Informasi</p>
              <h3 class="yellow-title">Media Pencadangan</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('backup-media')"
            >
              <Plus :size="17" /> Tambah Media
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Media</th>
                  <th>Lokasi</th>
                  <th>Jenis</th>
                  <th>Kapasitas</th>
                  <th>Address / URL</th>
                  <th>Job</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(media, index) in filteredBackupMedia"
                  :key="media.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ media.nama }}</strong
                    ><span>{{ assetCode(media) }}</span>
                  </td>
                  <td>
                    <span class="status">{{ media.location }}</span>
                  </td>
                  <td>{{ media.jenis_media }}</td>
                  <td>{{ media.kapasitas_gb || 0 }} GB</td>
                  <td>{{ media.address_url || "-" }}</td>
                  <td>{{ media.backup_jobs_count || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label media"
                        @click="openLabel('backup-media', media)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit media"
                        @click="openEdit('backup-media', media)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin media"
                        @click="openCopy('backup-media', media)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus media"
                        @click="removeRow('backup-media', media.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'backup-jobs'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Keamanan Informasi</p>
              <h3 class="yellow-title">Pencadangan</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('backup-jobs')"
            >
              <Plus :size="17" /> Tambah Pencadangan
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aplikasi</th>
                  <th>Media</th>
                  <th>Retensi</th>
                  <th>Repetisi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(job, index) in filteredBackupJobs" :key="job.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ job.aplikasi?.nama || "-" }}</strong
                    ><span>{{ assetCode(job) }}</span>
                  </td>
                  <td>
                    {{ job.media?.nama || "-"
                    }}<span>{{ job.media?.jenis_media || "" }}</span>
                  </td>
                  <td>{{ job.retensi_n }} {{ job.retensi_unit }}</td>
                  <td>{{ job.repetisi_n }} {{ job.repetisi_unit }}</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label pencadangan"
                        @click="openLabel('backup-jobs', job)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit pencadangan"
                        @click="openEdit('backup-jobs', job)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin pencadangan"
                        @click="openCopy('backup-jobs', job)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus pencadangan"
                        @click="removeRow('backup-jobs', job.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'ups-devices'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Pusat Data / DC</p>
              <h3 class="yellow-title">UPS / Power Backup</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('ups-devices')"
            >
              <Plus :size="17" /> Tambah UPS
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama</th>
                  <th>Kapasitas</th>
                  <th>Kondisi</th>
                  <th>Lokasi DC</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ups, index) in filteredUpsDevices" :key="ups.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ ups.nama }}</strong
                    ><span>{{ assetCode(ups) }}</span>
                  </td>
                  <td>{{ ups.kapasitas_va }} VA</td>
                  <td>
                    <span :class="statusClass(ups.kondisi)">{{
                      ups.kondisi
                    }}</span>
                  </td>
                  <td>
                    {{ ups.data_center?.nama || "-"
                    }}<span>{{ ups.data_center?.lokasi || "" }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label UPS"
                        @click="openLabel('ups-devices', ups)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit UPS"
                        @click="openEdit('ups-devices', ups)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin UPS"
                        @click="openCopy('ups-devices', ups)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus UPS"
                        @click="removeRow('ups-devices', ups.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'soc-tools'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Keamanan Informasi</p>
              <h3 class="yellow-title">SOC Platform / Device / Tools</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('soc-tools')"
            >
              <Plus :size="17" /> Tambah SOC Tool
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama</th>
                  <th>Jenis</th>
                  <th>Fungsi</th>
                  <th>Cakupan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(tool, index) in filteredSocTools" :key="tool.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ tool.nama }}</strong
                    ><span>{{ assetCode(tool) }}</span>
                  </td>
                  <td>
                    <span class="status">{{ tool.jenis }}</span>
                  </td>
                  <td>{{ tool.deskripsi_fungsi || "-" }}</td>
                  <td>
                    <span>DC: {{ tool.data_centers?.length || 0 }}</span>
                    <span>Server: {{ tool.servers?.length || 0 }}</span>
                    <span>VM: {{ tool.vms?.length || 0 }}</span>
                    <span>Aplikasi: {{ tool.applications?.length || 0 }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label SOC"
                        @click="openLabel('soc-tools', tool)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit SOC"
                        @click="openEdit('soc-tools', tool)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin SOC"
                        @click="openCopy('soc-tools', tool)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus SOC"
                        @click="removeRow('soc-tools', tool.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'network-sites'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Site / Node Instalasi</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('network-sites')"
            >
              <Plus :size="17" /> Tambah Site
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Site / Node</th>
                  <th>Jenis</th>
                  <th>Relasi Lokasi</th>
                  <th>PIC</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(site, index) in filteredNetworkSites"
                  :key="site.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ site.nama }}</strong
                    ><span>{{ assetCode(site) }}</span
                    ><span>{{ site.kode || "-" }}</span>
                  </td>
                  <td>
                    <span class="status">{{
                      networkSiteTypeLabel(site.jenis)
                    }}</span
                    ><span>{{ site.lokasi_detail || site.alamat || "" }}</span>
                  </td>
                  <td>{{ assetLocation("network-sites", site) }}</td>
                  <td>
                    {{ site.pic_nama || "-"
                    }}<span>{{ site.pic_kontak || "" }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(site.status)">{{
                      site.status || "-"
                    }}</span
                    ><span>{{ site.installations_count || 0 }} perangkat</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label site"
                        @click="openLabel('network-sites', site)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit site"
                        @click="openEdit('network-sites', site)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin site"
                        @click="openCopy('network-sites', site)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus site"
                        @click="removeRow('network-sites', site.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'network-monitorings'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Monitoring Site Bulanan</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('network-monitorings')"
            >
              <Plus :size="17" /> Tambah Monitoring
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Site / Periode</th>
                  <th>Speedtest</th>
                  <th>Menara</th>
                  <th>Petugas</th>
                  <th>Checklist</th>
                  <th>Lampiran</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, index) in filteredNetworkMonitorings"
                  :key="row.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ monitoringScopeLabel(row) }}</strong
                    ><span>{{ assetCode(row) }}</span
                    ><span
                      >{{ formatDateTime(row.monitoring_at) }} /
                      {{ row.period_month || "-" }}</span
                    >
                  </td>
                  <td>{{ monitoringSpeedSummary(row) }}</td>
                  <td>
                    {{ monitoringTowerSummary(row)
                    }}<span>{{ row.tower_notes || "" }}</span>
                  </td>
                  <td>{{ monitoringOfficersText(row) }}</td>
                  <td>
                    {{ row.items_count || row.items?.length || 0 }}
                    perangkat<span
                      >{{
                        (row.items || []).filter(
                          (item) => item.condition === "rusak",
                        ).length
                      }}
                      rusak</span
                    >
                  </td>
                  <td>{{ row.attachments?.length || 0 }} file</td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak laporan monitoring"
                        @click="openMonitoringReport(row)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit monitoring"
                        @click="openEdit('network-monitorings', row)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin monitoring"
                        @click="openCopy('network-monitorings', row)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus monitoring"
                        @click="removeRow('network-monitorings', row.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'network-devices'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Perangkat Jaringan</h3>
            </div>
            <div class="module-actions">
              <select v-model="networkDeviceOpdFilter" class="compact-select">
                <option value="">Semua OPD / Pemilik</option>
                <option
                  v-for="opd in references.opd"
                  :key="opd.id"
                  :value="opd.id"
                >
                  {{ opd.nama }}
                </option>
              </select>
              <button
                v-if="canWrite"
                class="action-button"
                type="button"
                @click="openCreate('network-devices')"
              >
                <Plus :size="17" /> Tambah Perangkat
              </button>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Perangkat</th>
                  <th>OPD / Pemilik</th>
                  <th>Jenis</th>
                  <th>Spesifikasi Ringkas</th>
                  <th>Site Aktif</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(device, index) in filteredNetworkDevices"
                  :key="device.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ device.nama }}</strong
                    ><span>{{ assetCode(device) }}</span
                    ><span>{{
                      device.serial_number || device.mac_address || "-"
                    }}</span>
                  </td>
                  <td>{{ networkDeviceOwnerName(device) }}</td>
                  <td>
                    <span class="status">{{
                      networkDeviceTypeLabel(device.jenis)
                    }}</span
                    ><span
                      >{{ device.merk || "-" }} {{ device.model || "" }}</span
                    >
                  </td>
                  <td>
                    {{ device.os_firmware || "-"
                    }}<span
                      >{{
                        device.kapasitas_port
                          ? `${device.kapasitas_port} port`
                          : ""
                      }}
                      {{ device.poe_support ? " / PoE" : "" }}
                      {{ device.wireless_standard || "" }}</span
                    >
                  </td>
                  <td>
                    {{ device.active_installation?.site?.nama || "-"
                    }}<span
                      >{{ device.installations_count || 0 }} riwayat /
                      {{ device.ip_configs_count || 0 }} IP</span
                    >
                  </td>
                  <td>
                    <span :class="statusClass(device.status)">{{
                      device.status || "-"
                    }}</span
                    ><span>{{ device.kondisi || "" }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label perangkat"
                        @click="openLabel('network-devices', device)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit perangkat"
                        @click="openEdit('network-devices', device)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin perangkat"
                        @click="openCopy('network-devices', device)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus perangkat"
                        @click="removeRow('network-devices', device.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section
        v-if="activeTab === 'network-installations'"
        class="content-grid"
      >
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Instalasi & Pergantian</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('network-installations')"
            >
              <Plus :size="17" /> Tambah Riwayat
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Site / Node</th>
                  <th>Perangkat</th>
                  <th>Role</th>
                  <th>Periode</th>
                  <th>Pengganti</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, index) in filteredNetworkInstallations"
                  :key="row.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ row.site?.nama || "-" }}</strong
                    ><span>{{
                      row.site?.kode || row.site?.asset_code || ""
                    }}</span>
                  </td>
                  <td>
                    {{ row.device?.nama || "-"
                    }}<span>{{
                      [
                        row.device?.asset_code,
                        networkDeviceTypeLabel(row.device?.jenis),
                      ]
                        .filter(Boolean)
                        .join(" / ")
                    }}</span>
                  </td>
                  <td>
                    {{ networkInstallationRoleLabel(row.role)
                    }}<span>{{ row.installed_by || "" }}</span>
                  </td>
                  <td>
                    {{
                      row.installed_at
                        ? String(row.installed_at).slice(0, 10)
                        : "-"
                    }}<span>{{
                      row.removed_at
                        ? `s.d. ${String(row.removed_at).slice(0, 10)}`
                        : "masih tercatat"
                    }}</span>
                  </td>
                  <td>{{ row.replacement_device?.nama || "-" }}</td>
                  <td>
                    <span :class="statusClass(row.status)">{{
                      row.status
                    }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label instalasi"
                        @click="openLabel('network-installations', row)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit riwayat instalasi"
                        @click="openEdit('network-installations', row)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin riwayat instalasi"
                        @click="openCopy('network-installations', row)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus riwayat instalasi"
                        @click="removeRow('network-installations', row.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'network-ip-configs'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Konfigurasi IP</h3>
            </div>
            <div class="module-actions">
              <select v-model="networkIpConfigOpdFilter" class="compact-select">
                <option value="">Semua OPD / Pemilik</option>
                <option
                  v-for="opd in references.opd"
                  :key="opd.id"
                  :value="opd.id"
                >
                  {{ opd.nama }}
                </option>
              </select>
              <button
                v-if="canWrite"
                class="action-button"
                type="button"
                @click="openCreate('network-ip-configs')"
              >
                <Plus :size="17" /> Tambah IP Config
              </button>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Perangkat</th>
                  <th>OPD / Pemilik</th>
                  <th>Site</th>
                  <th>Interface</th>
                  <th>IP / Gateway</th>
                  <th>VLAN / SSID</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(config, index) in filteredNetworkIpConfigs"
                  :key="config.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ config.device?.nama || "-" }}</strong
                    ><span>{{
                      networkDeviceTypeLabel(config.device?.jenis)
                    }}</span>
                  </td>
                  <td>{{ networkIpConfigOwnerName(config) }}</td>
                  <td>{{ config.site?.nama || "-" }}</td>
                  <td>
                    {{ config.interface_name || "-"
                    }}<span>{{ networkIpTypeLabel(config.ip_type) }}</span>
                  </td>
                  <td>
                    {{ config.ip_address || config.ip_address_record?.ip || "-"
                    }}<span
                      >{{ config.gateway ? `GW ${config.gateway}` : "" }}
                      {{ config.dhcp_enabled ? " / DHCP" : "" }}</span
                    >
                  </td>
                  <td>
                    {{ config.vlan || "-" }}<span>{{ config.ssid || "" }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(config.status)">{{
                      config.status || "-"
                    }}</span>
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label konfigurasi IP"
                        @click="openLabel('network-ip-configs', config)"
                      >
                        <Printer :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit konfigurasi IP"
                        @click="openEdit('network-ip-configs', config)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin konfigurasi IP"
                        @click="openCopy('network-ip-configs', config)"
                      >
                        <Copy :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus konfigurasi IP"
                        @click="removeRow('network-ip-configs', config.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'network-credentials'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Kredensial Manajemen</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('network-credentials')"
            >
              <Plus :size="17" /> Tambah Kredensial
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Label</th>
                  <th>Perangkat</th>
                  <th>Metode</th>
                  <th>URL / Username</th>
                  <th>Password</th>
                  <th>Rotasi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(credential, index) in filteredNetworkCredentials"
                  :key="credential.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ credential.label }}</strong
                    ><span>{{ credential.site?.nama || "" }}</span>
                  </td>
                  <td>
                    {{ credential.device?.nama || "-"
                    }}<span>{{
                      networkDeviceTypeLabel(credential.device?.jenis)
                    }}</span>
                  </td>
                  <td>
                    {{ networkAccessMethodLabel(credential.access_method) }}
                  </td>
                  <td>
                    {{ credential.management_url || "-"
                    }}<span>{{ credential.username || "" }}</span>
                  </td>
                  <td>
                    <span
                      :class="
                        statusClass(
                          credential.has_password ? 'aktif' : 'nonaktif',
                        )
                      "
                      >{{
                        credential.has_password ? "Tersimpan" : "Belum ada"
                      }}</span
                    >
                  </td>
                  <td>
                    {{
                      credential.last_rotated_at
                        ? String(credential.last_rotated_at).slice(0, 10)
                        : "-"
                    }}
                  </td>
                  <td>
                    <div class="row-actions">
                      <button
                        class="icon-button"
                        title="Cetak label kredensial"
                        @click="openLabel('network-credentials', credential)"
                      >
                        <Printer :size="16" />
                      </button>
                      <button
                        v-if="canWrite && credential.has_password"
                        class="icon-button"
                        title="Reveal password"
                        @click="openRevealPassword(credential)"
                      >
                        <Eye :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button"
                        title="Edit kredensial"
                        @click="openEdit('network-credentials', credential)"
                      >
                        <Pencil :size="16" /></button
                      ><button
                        v-if="canWrite"
                        class="icon-button"
                        title="Salin kredensial"
                        @click="openCopy('network-credentials', credential)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="canWrite"
                        class="icon-button danger"
                        title="Hapus kredensial"
                        @click="removeRow('network-credentials', credential.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section
        v-if="activeTab === 'network-owner-summary'"
        class="content-grid"
      >
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Consumer Networking</p>
              <h3 class="yellow-title">Relasi Perangkat per OPD</h3>
            </div>
            <button
              class="action-button"
              type="button"
              :disabled="!networkSummaryForm.opd_id"
              @click="printNetworkSummary"
            >
              <Printer :size="17" /> Cetak PDF
            </button>
          </div>
          <div class="bulk-label-controls">
            <label class="field-label">
              <span>OPD / Pemilik</span>
              <select v-model="networkSummaryForm.opd_id">
                <option value="">Pilih OPD / Pemilik</option>
                <option
                  v-for="opd in references.opd"
                  :key="opd.id"
                  :value="opd.id"
                >
                  {{ opd.nama }}
                </option>
              </select>
            </label>
            <div class="bulk-label-summary">
              <span>Total Site/Node</span>
              <strong>{{ networkSummaryTotals.sites }}</strong>
              <small>{{
                selectedNetworkSummaryOpd?.nama || "OPD belum dipilih"
              }}</small>
            </div>
            <div class="bulk-label-summary">
              <span>Total Perangkat</span>
              <strong>{{ networkSummaryTotals.devices }}</strong>
              <small>Perangkat aktif dalam relasi OPD</small>
            </div>
          </div>
        </section>

        <section class="surface wide">
          <div v-if="!selectedNetworkSummaryOpd" class="empty-state">
            <strong>Pilih OPD / Pemilik</strong>
            <p>
              Laporan relasi akan menampilkan seluruh Site/Node dan perangkat
              aktif di bawah OPD tersebut.
            </p>
          </div>

          <article v-else class="network-summary-print">
            <header class="report-header">
              <div class="report-brand">
                <img :src="logoLangkat" alt="Logo Kabupaten Langkat" />
                <div>
                  <p>PEMERINTAH KABUPATEN LANGKAT</p>
                  <h1>Ringkasan Relasi Perangkat Jaringan</h1>
                  <span>IAMT CMDB Langkat</span>
                </div>
              </div>
            </header>

            <section class="report-title-block">
              <div>
                <span>OPD / Pemilik</span>
                <strong>{{ selectedNetworkSummaryOpd.nama }}</strong>
                <small
                  >Relasi Site/Node dan perangkat aktif Consumer
                  Networking</small
                >
              </div>
              <div>
                <span>Waktu Cetak</span>
                <strong>{{ networkSummaryPrintedAt() }}</strong>
                <small>Dokumen inventaris jaringan</small>
              </div>
            </section>

            <section class="report-summary-grid compact-report-grid">
              <div>
                <span>Site/Node</span
                ><strong>{{ networkSummaryTotals.sites }}</strong>
              </div>
              <div>
                <span>Perangkat</span
                ><strong>{{ networkSummaryTotals.devices }}</strong>
              </div>
              <div>
                <span>Site Kosong</span
                ><strong>{{ networkSummaryTotals.emptySites }}</strong>
              </div>
            </section>

            <section class="report-section">
              <h2>Daftar Site/Node dan Perangkat</h2>
              <div v-if="!networkSummarySiteRows.length" class="report-note">
                Belum ada Site/Node yang terhubung ke OPD/Pemilik ini.
              </div>
              <div
                v-for="row in networkSummarySiteRows"
                :key="row.site.id"
                class="summary-site-block"
              >
                <div class="summary-site-heading">
                  <div>
                    <strong>{{ row.site.nama }}</strong>
                    <span>{{
                      [
                        row.site.kode || row.site.asset_code,
                        networkSiteTypeLabel(row.site.jenis),
                        row.site.status,
                      ]
                        .filter(Boolean)
                        .join(" / ")
                    }}</span>
                  </div>
                  <small>{{
                    [row.site.lokasi_detail, row.site.alamat]
                      .filter(Boolean)
                      .join(" / ") || "-"
                  }}</small>
                </div>
                <table class="report-table">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Perangkat</th>
                      <th>Kode</th>
                      <th>Jenis / Role</th>
                      <th>Merk / Model</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody v-if="row.devices.length">
                    <tr
                      v-for="({ installation, device }, index) in row.devices"
                      :key="installation.id"
                    >
                      <td>{{ index + 1 }}</td>
                      <td>
                        <strong>{{ device.nama }}</strong
                        ><span>{{
                          device.serial_number || device.mac_address || "-"
                        }}</span>
                      </td>
                      <td>{{ assetCode(device) }}</td>
                      <td>
                        {{ networkDeviceTypeLabel(device.jenis)
                        }}<span>{{
                          networkInstallationRoleLabel(installation.role)
                        }}</span>
                      </td>
                      <td>
                        {{
                          [device.merk, device.model]
                            .filter(Boolean)
                            .join(" / ") || "-"
                        }}<span>{{ device.os_firmware || "" }}</span>
                      </td>
                      <td>
                        <span :class="statusClass(device.status)">{{
                          device.status || "-"
                        }}</span
                        ><span>{{ device.kondisi || "" }}</span>
                      </td>
                    </tr>
                  </tbody>
                  <tbody v-else>
                    <tr>
                      <td colspan="6">
                        Belum ada perangkat aktif tercatat pada site/node ini.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <section
              v-if="networkSummaryUnassignedDevices.length"
              class="report-section"
            >
              <h2>Perangkat Belum Terpasang di Site Aktif</h2>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Perangkat</th>
                    <th>Kode</th>
                    <th>Jenis</th>
                    <th>Merk / Model</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(device, index) in networkSummaryUnassignedDevices"
                    :key="device.id"
                  >
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ device.nama }}</strong
                      ><span>{{
                        device.serial_number || device.mac_address || "-"
                      }}</span>
                    </td>
                    <td>{{ assetCode(device) }}</td>
                    <td>{{ networkDeviceTypeLabel(device.jenis) }}</td>
                    <td>
                      {{
                        [device.merk, device.model]
                          .filter(Boolean)
                          .join(" / ") || "-"
                      }}
                    </td>
                    <td>
                      <span :class="statusClass(device.status)">{{
                        device.status || "-"
                      }}</span
                      ><span>{{ device.kondisi || "" }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </section>

            <footer class="report-footer">
              <div>
                <span>Dokumen</span>
                <strong>Relasi OPD Consumer Networking</strong>
              </div>
              <div>
                <span>Sumber Data</span>
                <strong>IAMT CMDB Kabupaten Langkat</strong>
              </div>
            </footer>
          </article>
        </section>
      </section>

      <section v-if="activeTab === 'users'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">Autentikasi</p>
              <h3 class="yellow-title">Pengguna & Role</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              @click="openCreate('users')"
            >
              <Plus :size="17" /> Tambah Pengguna
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Login Terakhir</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(user, index) in filteredUsers" :key="user.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ user.nama }}</strong>
                  </td>
                  <td>{{ user.email }}</td>
                  <td>
                    <span :class="statusClass(user.role)">{{
                      user.role === "full" ? "Full" : "Read Only"
                    }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(user.status)">{{
                      user.status
                    }}</span>
                  </td>
                  <td>
                    {{
                      user.last_login_at
                        ? new Date(user.last_login_at).toLocaleString("id-ID")
                        : "-"
                    }}
                  </td>
                  <td>
                    <div v-if="canWrite" class="row-actions">
                      <button
                        class="icon-button"
                        title="Edit pengguna"
                        @click="openEdit('users', user)"
                      >
                        <Pencil :size="16" />
                      </button>
                      <button
                        class="icon-button"
                        title="Salin pengguna"
                        @click="openCopy('users', user)"
                      >
                        <Copy :size="16" />
                      </button>
                      <button
                        v-if="currentUser.id !== user.id"
                        class="icon-button danger"
                        title="Hapus pengguna"
                        @click="removeRow('users', user.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'map'" class="content-grid">
        <div class="metrics-row">
          <article class="metric-card blue">
            <span>Total Aplikasi</span>
            <strong>{{ dependencyMap.length }}</strong>
            <small>Dengan mapping</small>
          </article>
          <article class="metric-card cyan">
            <span>Virtual Machine</span>
            <strong>{{
              dependencyMap.reduce((s, a) => s + a.vms.length, 0)
            }}</strong>
            <small>Terpetakan</small>
          </article>
          <article class="metric-card yellow">
            <span>Server</span>
            <strong>{{
              new Set(
                dependencyMap
                  .flatMap((a) => a.vms.map((v) => v.server?.id))
                  .filter(Boolean),
              ).size
            }}</strong>
            <small>Terlibat</small>
          </article>
          <article class="metric-card green">
            <span>IP Address</span>
            <strong>{{
              dependencyMap.reduce((s, a) => s + a.ips.length, 0)
            }}</strong>
            <small>Terdaftar</small>
          </article>
        </div>
        <section class="surface wide">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Dependency Graph</p>
              <h3 class="yellow-title">Aplikasi → VM → Server → IP</h3>
            </div>
            <GitBranch :size="30" />
          </div>
          <div class="dependency-list">
            <article
              v-for="app in dependencyMap"
              :key="app.id"
              class="dependency-card"
            >
              <div class="dependency-head">
                <div>
                  <strong>{{ app.nama }}</strong>
                  <span>{{ app.opd || "OPD belum dipilih" }}</span>
                </div>
                <span :class="statusClass(app.status)">{{ app.status }}</span>
              </div>
              <div class="dependency-flow">
                <div class="node app-node">
                  <AppWindow :size="18" />{{ app.nama }}
                </div>
                <span v-if="app.vms.length" class="flow-arrow">→</span>
                <template v-for="vm in app.vms" :key="vm.id">
                  <div class="node vm-node">
                    <Server :size="18" />{{ vm.nama }}
                    <small>{{
                      vm.server?.nama || "host belum dipetakan"
                    }}</small>
                  </div>
                </template>
                <span v-if="app.vms.length && app.ips.length" class="flow-arrow"
                  >→</span
                >
                <template v-for="ip in app.ips" :key="ip.id">
                  <div class="node ip-node">
                    <Network :size="18" />{{ ip.ip }}
                    <small>{{ ip.jenis }}</small>
                  </div>
                </template>
              </div>
            </article>
          </div>
        </section>

        <section class="surface wide">
          <div class="section-heading compact">
            <h3>Impact Analysis</h3>
            <AlertTriangle :size="24" />
          </div>
          <div class="impact-toolbar">
            <select v-model="selectedServerId" @change="loadImpact">
              <option
                v-for="server in references.servers"
                :key="server.id"
                :value="server.id"
              >
                {{ server.nama }}
              </option>
            </select>
            <button
              class="action-button compact-button"
              type="button"
              @click="loadImpact"
            >
              Analisis
            </button>
          </div>
          <div v-if="impact" class="impact-grid">
            <div class="impact-summary">
              <strong>{{ impact.server.nama }}</strong>
              <span>{{ impact.server.lokasi }}</span>
              <small>{{ impact.server.kapasitas }}</small>
            </div>
            <div class="impact-summary warning">
              <strong>{{ impact.summary.total_aplikasi }}</strong>
              <span>Aplikasi terdampak</span>
              <small>Risk level: {{ impact.summary.risk_level }}</small>
            </div>
            <article
              v-for="app in impact.applications"
              :key="app.id"
              class="mini-row"
            >
              <div>
                <strong>{{ app.nama }}</strong>
                <span>{{ app.jalur }} / {{ app.opd || "-" }}</span>
              </div>
              <span :class="statusClass(app.status)">{{ app.status }}</span>
            </article>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'compliance'" class="content-grid">
        <div class="metrics-row">
          <article class="metric-card blue">
            <span>Total Aplikasi</span>
            <strong>{{ compliance?.summary.total || 0 }}</strong>
            <small>Objek PSE</small>
          </article>
          <article class="metric-card red">
            <span>Data Pribadi</span>
            <strong>{{ compliance?.summary.data_pribadi || 0 }}</strong>
            <small>Butuh kontrol ekstra</small>
          </article>
          <article class="metric-card yellow">
            <span>SLA Kritis</span>
            <strong>{{ compliance?.summary.sla_kritis || 0 }}</strong>
            <small>Minimal 99%</small>
          </article>
          <article class="metric-card cyan">
            <span>Tanpa Mapping</span>
            <strong>{{ compliance?.summary.tanpa_vm || 0 }}</strong>
            <small>Perlu dilengkapi</small>
          </article>
        </div>
        <section class="surface wide">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aplikasi</th>
                  <th>OPD</th>
                  <th>Target SLA</th>
                  <th>Kontrol</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, index) in compliance?.items || []"
                  :key="item.id"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ item.nama }}</strong
                    ><span>Data pribadi: {{ yesNo(item.data_pribadi) }}</span>
                  </td>
                  <td>{{ item.opd || "-" }}</td>
                  <td>{{ item.sla || 0 }}%</td>
                  <td>
                    <div class="control-dots">
                      <CheckCircle2
                        v-for="(ok, key) in item.kontrol"
                        :key="key"
                        :size="18"
                        :class="{ ok, miss: !ok }"
                      />
                    </div>
                  </td>
                  <td>
                    <span :class="statusClass(item.status)">{{
                      item.status
                    }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
        <section class="surface wide">
          <div class="section-heading compact">
            <h3>Security Gap</h3>
            <ShieldCheck :size="24" />
          </div>
          <div class="priority-list">
            <article
              v-for="server in compliance?.security_gap.servers_without_tools ||
              []"
              :key="server.id"
              class="mini-row"
            >
              <div>
                <strong>{{ server.nama }}</strong>
                <span>Belum tercatat memiliki security tool</span>
              </div>
              <span :class="statusClass(server.status)">{{
                server.status
              }}</span>
            </article>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'audit'" class="content-grid">
        <section class="surface wide">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Audit-first</p>
              <h3 class="yellow-title">Riwayat Perubahan Aset</h3>
            </div>
            <Activity :size="30" />
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Waktu</th>
                  <th>Aset</th>
                  <th>Jenis</th>
                  <th>Alasan</th>
                  <th>Field Berubah</th>
                  <th>Operator</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(log, index) in assetChangeLogs" :key="log.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{
                      new Date(log.created_at).toLocaleDateString("id-ID")
                    }}</strong>
                    <span>{{
                      new Date(log.created_at).toLocaleTimeString("id-ID")
                    }}</span>
                  </td>
                  <td>
                    <strong>{{ log.asset_name }}</strong>
                    <span>{{
                      log.asset_type === "server" ? "Server" : "VM / CT"
                    }}</span>
                  </td>
                  <td>
                    <span :class="statusClass(log.change_type)">{{
                      log.change_type
                    }}</span>
                  </td>
                  <td>{{ log.reason || "-" }}</td>
                  <td>
                    <div class="stack-list">
                      <span
                        v-for="item in formatChangeFields(log.changed_fields)"
                        :key="item"
                        >{{ item }}</span
                      >
                    </div>
                  </td>
                  <td>
                    <strong>{{ log.changed_by || "-" }}</strong>
                    <span>{{ log.ip_address || "-" }}</span>
                  </td>
                </tr>
                <tr v-if="assetChangeLogs.length === 0">
                  <td colspan="7">
                    Belum ada perubahan spesifikasi Server atau VM.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="surface wide">
          <div class="section-heading">
            <div>
              <p class="eyebrow">Audit umum</p>
              <h3 class="yellow-title">Jejak Perubahan Data</h3>
            </div>
            <Activity :size="30" />
          </div>
          <div class="timeline">
            <article
              v-for="log in auditLog"
              :key="log.id"
              class="timeline-item"
            >
              <span class="timeline-dot"></span>
              <div>
                <strong>{{ log.aksi }} / {{ log.tabel }}</strong>
                <span>{{ log.record_id || "system" }}</span>
                <small
                  >{{ new Date(log.created_at).toLocaleString("id-ID") }} Â·
                  {{ log.ip_address }}</small
                >
              </div>
            </article>
          </div>
        </section>
      </section>

      <section v-if="activeTab === 'api-keys'" class="content-grid">
        <section class="surface wide">
          <div class="module-header">
            <div>
              <p class="eyebrow">API Keys</p>
              <h3 class="yellow-title">Buat API Key</h3>
            </div>
            <button
              v-if="canWrite"
              class="action-button"
              type="button"
              :disabled="saving"
              @click="createApiKey"
            >
              <Plus :size="17" /> Tambah API Key
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>API KEY</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(apiKey, index) in filteredApiKeys" :key="apiKey.id">
                  <td>{{ index + 1 }}</td>
                  <td>
                    <strong>{{ apiKey.api_key_masked }}</strong>
                  </td>
                  <td>
                    <div v-if="canWrite" class="row-actions">
                      <button
                        class="icon-button danger"
                        title="Hapus Token"
                        @click="removeRow('api-keys', apiKey.id)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>

      <div
        v-if="modal.open"
        class="modal-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeModal)"
      >
        <form class="modal-card" @submit.prevent="saveModal">
          <header class="modal-header">
            <div>
              <p class="eyebrow">
                {{ modal.mode === "edit" ? "Edit Data" : "Tambah Data" }}
              </p>
              <h3>{{ activeModuleLabel }}</h3>
            </div>
            <button
              class="icon-button"
              type="button"
              title="Tutup form"
              @click="closeModal"
            >
              <X :size="18" />
            </button>
          </header>

          <div v-if="modal.module === 'data-centers'" class="modal-form">
            <input
              v-model="dataCenterForm.nama"
              required
              placeholder="Nama data center"
            />
            <input v-model="dataCenterForm.lokasi" placeholder="Lokasi" />
            <select v-model="dataCenterForm.tipe" required>
              <option value="">Tipe data center</option>
              <option value="utama">Utama</option>
              <option value="dr">Disaster Recovery</option>
              <option value="cloud">Cloud</option>
            </select>
          </div>

          <div v-if="modal.module === 'racks'" class="modal-form">
            <input v-model="rackForm.nama" required placeholder="Nama rack" />
            <select v-model="rackForm.dc_id" required>
              <option value="">Data center</option>
              <option
                v-for="dc in references.data_centers"
                :key="dc.id"
                :value="dc.id"
              >
                {{ dc.nama }}
              </option>
            </select>
            <input
              v-model.number="rackForm.kapasitas_u"
              required
              type="number"
              min="1"
              max="60"
              placeholder="Kapasitas U"
            />
          </div>

          <div v-if="modal.module === 'servers'" class="modal-form">
            <input
              v-model="serverForm.nama"
              required
              placeholder="Nama server"
            />
            <div class="two-col">
              <select v-model="serverForm.dc_id">
                <option value="">Data center</option>
                <option
                  v-for="dc in references.data_centers"
                  :key="dc.id"
                  :value="dc.id"
                >
                  {{ dc.nama }}
                </option>
              </select>
              <select v-model="serverForm.rack_id">
                <option value="">Rack</option>
                <option
                  v-for="rack in references.racks"
                  :key="rack.id"
                  :value="rack.id"
                >
                  {{ rack.nama }}
                </option>
              </select>
            </div>
            <input
              v-model.number="serverForm.rack_size_u"
              type="number"
              min="1"
              max="60"
              placeholder="Rack Size (U)"
            />
            <div class="two-col">
              <input v-model="serverForm.merk" placeholder="Merk" />
              <input v-model="serverForm.tipe" placeholder="Tipe" />
            </div>
            <div class="two-col">
              <input
                v-model="serverForm.merk_processor"
                placeholder="Merk processor"
              />
              <input
                v-model.number="serverForm.tahun"
                type="number"
                min="2000"
                max="2100"
                placeholder="Tahun"
              />
            </div>
            <div class="three-col">
              <input
                v-model.number="serverForm.cpu_core"
                type="number"
                min="1"
                placeholder="Core"
              />
              <input
                v-model.number="serverForm.ram_gb"
                type="number"
                min="1"
                placeholder="RAM GB"
              />
              <input
                v-model.number="serverForm.storage_gb"
                type="number"
                min="1"
                placeholder="Storage GB"
              />
            </div>
            <div class="two-col">
              <select v-model="serverForm.kondisi">
                <option value="">Kondisi server</option>
                <option value="baik">Baik</option>
                <option value="rusak">Rusak</option>
              </select>
              <select v-model="serverForm.status">
                <option value="">Status server</option>
                <option value="aktif">Aktif</option>
                <option value="maintenance">Maintenance</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
            <input
              v-model="serverForm.penanggung_jawab"
              placeholder="Penanggung jawab"
            />
            <div v-if="modal.mode === 'edit'" class="two-col">
              <input
                v-model="serverForm.changed_by"
                placeholder="Operator / pelaksana perubahan"
              />
              <input
                v-model="serverForm.change_reason"
                placeholder="Alasan perubahan"
              />
            </div>
          </div>

          <div v-if="modal.module === 'vms'" class="modal-form">
            <input v-model="vmForm.nama" required placeholder="Nama VM" />
            <select v-model="vmForm.server_id">
              <option value="">Host server</option>
              <option
                v-for="server in references.servers"
                :key="server.id"
                :value="server.id"
              >
                {{ server.nama }}
              </option>
            </select>
            <input v-model="vmForm.os" placeholder="Operating system" />
            <div class="three-col">
              <input
                v-model.number="vmForm.vcpu"
                type="number"
                min="1"
                placeholder="vCPU"
              />
              <input
                v-model.number="vmForm.ram_gb"
                type="number"
                min="1"
                placeholder="RAM GB"
              />
              <input
                v-model.number="vmForm.storage_gb"
                type="number"
                min="1"
                placeholder="Storage GB"
              />
            </div>
            <select v-model="vmForm.status">
              <option value="">Status VM</option>
              <option value="running">Running</option>
              <option value="stopped">Stopped</option>
              <option value="suspended">Suspended</option>
              <option value="maintenance">Maintenance</option>
            </select>
            <div class="inline-picker">
              <strong>IP Address</strong>
              <button
                v-for="ip in references.ips"
                :key="ip.id"
                class="chip"
                :class="{ selected: vmForm.ip_ids.includes(ip.id) }"
                type="button"
                @click="toggleInArray(vmForm.ip_ids, ip.id)"
              >
                {{ ip.ip }}
              </button>
            </div>
            <div v-if="modal.mode === 'edit'" class="two-col">
              <input
                v-model="vmForm.changed_by"
                placeholder="Operator / pelaksana perubahan"
              />
              <input
                v-model="vmForm.change_reason"
                placeholder="Alasan perubahan"
              />
            </div>
          </div>

          <div v-if="modal.module === 'ip-addresses'" class="modal-form">
            <input
              v-model="ipAddressForm.ip"
              required
              placeholder="Alamat IP atau network, contoh: 10.10.10.10 / 10.10.10.0/24"
            />
            <div class="two-col">
              <select v-model="ipAddressForm.jenis" required>
                <option value="">Jenis IP</option>
                <option value="private">Private</option>
                <option value="publik">Publik</option>
              </select>
              <select v-model="ipAddressForm.isp_id">
                <option value="">Tanpa ISP</option>
                <option
                  v-for="isp in references.isps"
                  :key="isp.id"
                  :value="isp.id"
                >
                  {{ isp.nama }}
                </option>
              </select>
            </div>
            <input
              v-model="ipAddressForm.assignment"
              placeholder="Assignment, contoh: Gateway router OPD / VM Portal / IP management switch"
            />
          </div>

          <div v-if="modal.module === 'isps'" class="modal-form">
            <input v-model="ispForm.nama" required placeholder="Nama ISP" />
            <div class="two-col">
              <input v-model="ispForm.tipe" placeholder="Tipe koneksi" />
              <input v-model="ispForm.bandwidth" placeholder="Bandwidth" />
            </div>
            <input v-model="ispForm.kontak" placeholder="Kontak NOC / PIC" />
          </div>

          <div v-if="modal.module === 'applications'" class="modal-form">
            <input
              v-model="appForm.nama"
              required
              placeholder="Nama aplikasi"
            />
            <input
              v-model="appForm.url"
              type="text"
              inputmode="url"
              placeholder="URL aplikasi (opsional)"
            />
            <input
              v-model="appForm.tech_stack"
              placeholder="Tech Stack, contoh: Laravel, Vue, MySQL"
            />
            <select v-model="appForm.opd_id">
              <option value="">OPD pemilik</option>
              <option
                v-for="opd in references.opd"
                :key="opd.id"
                :value="opd.id"
              >
                {{ opd.nama }}
              </option>
            </select>
            <div class="three-col">
              <select v-model="appForm.jenis_aplikasi" required>
                <option value="">Jenis aplikasi</option>
                <option value="web">Web</option>
                <option value="mobile">Mobile</option>
                <option value="desktop">Desktop</option>
                <option value="service">Service</option>
                <option value="lainnya">Lainnya</option>
              </select>
              <select v-model="appForm.pengembang" required>
                <option value="">Pengembang</option>
                <option
                  v-for="option in developerOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <select v-model="appForm.status">
                <option value="">Status aplikasi</option>
                <option value="aktif">Aktif</option>
                <option value="maintenance">Maintenance</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model.number="appForm.sla_persen"
                type="number"
                min="0"
                max="100"
                step="0.01"
                placeholder="Target SLA %"
              />
              <select v-model="appForm.kategori_data">
                <option value="">Kategori data</option>
                <option value="publik">Publik</option>
                <option value="terbatas">Terbatas</option>
                <option value="rahasia">Rahasia</option>
              </select>
            </div>
            <div class="inline-picker">
              <strong>Klasifikasi Fungsi</strong>
              <button
                v-for="option in functionClassificationOptions"
                :key="option.value"
                class="chip"
                :class="{
                  selected: appForm.klasifikasi_fungsi.includes(option.value),
                }"
                type="button"
                @click="toggleInArray(appForm.klasifikasi_fungsi, option.value)"
              >
                {{ option.label }}
              </button>
            </div>
            <input v-model="appForm.pic_nama" placeholder="PIC" />
            <input v-model="appForm.pic_kontak" placeholder="Kontak PIC" />
            <div class="picker-grid">
              <div>
                <strong>VM</strong>
                <button
                  v-for="vm in references.vms"
                  :key="vm.id"
                  class="chip"
                  :class="{ selected: appForm.vm_ids.includes(vm.id) }"
                  type="button"
                  @click="toggleInArray(appForm.vm_ids, vm.id)"
                >
                  {{ vm.nama }}
                </button>
              </div>
              <div>
                <strong>Server</strong>
                <button
                  v-for="server in references.servers"
                  :key="server.id"
                  class="chip"
                  :class="{ selected: appForm.server_ids.includes(server.id) }"
                  type="button"
                  @click="toggleInArray(appForm.server_ids, server.id)"
                >
                  {{ server.nama }}
                </button>
              </div>
              <div>
                <strong>IP Address</strong>
                <button
                  v-for="ip in references.ips"
                  :key="ip.id"
                  class="chip"
                  :class="{ selected: appForm.ip_ids.includes(ip.id) }"
                  type="button"
                  @click="toggleInArray(appForm.ip_ids, ip.id)"
                >
                  {{ ip.ip }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="modal.module === 'data-assets'" class="modal-form">
            <div class="risk-result">
              <div>
                <span>Nilai total</span>
                <strong>{{ dataAssetRiskTotal }}</strong>
              </div>
              <div>
                <span>Klasifikasi otomatis</span>
                <strong>{{ dataAssetCalculatedClassification.name }}</strong>
                <small
                  >{{ dataAssetCalculatedClassification.risk }} Â·
                  {{ dataAssetCalculatedClassification.color }}</small
                >
              </div>
            </div>
            <div class="two-col">
              <select v-model="dataAssetForm.aplikasi_id" required>
                <option value="">Aplikasi</option>
                <option
                  v-for="app in applications"
                  :key="app.id"
                  :value="app.id"
                >
                  {{ app.nama }}
                </option>
              </select>
              <input
                v-model="dataAssetForm.owner_agency"
                placeholder="K/L/D pemilik data"
              />
            </div>
            <div class="two-col">
              <input
                v-model="dataAssetForm.name"
                required
                placeholder="Nama aset data, contoh: users.email"
              />
              <select v-model="dataAssetForm.type" required>
                <option value="">Tipe aset data</option>
                <option value="TABLE">Table</option>
                <option value="COLUMN">Column</option>
                <option value="API">API</option>
                <option value="FILE">File</option>
                <option value="FORM">Form</option>
                <option value="DATASET">Dataset</option>
              </select>
            </div>
            <textarea
              v-model="dataAssetForm.attributes"
              placeholder="Atribut data sebagai justifikasi, satu per baris"
            ></textarea>
            <div class="three-col">
              <select
                v-model.number="dataAssetForm.confidentiality_score"
                required
              >
                <option value="">Kerahasiaan</option>
                <option :value="1">Kerahasiaan: Rendah (1)</option>
                <option :value="3">Kerahasiaan: Sedang (3)</option>
                <option :value="5">Kerahasiaan: Tinggi (5)</option>
              </select>
              <select v-model.number="dataAssetForm.integrity_score" required>
                <option value="">Integritas</option>
                <option :value="1">Integritas: Rendah (1)</option>
                <option :value="3">Integritas: Sedang (3)</option>
                <option :value="5">Integritas: Tinggi (5)</option>
              </select>
              <select
                v-model.number="dataAssetForm.availability_score"
                required
              >
                <option value="">Ketersediaan</option>
                <option :value="1">Ketersediaan: Rendah (1)</option>
                <option :value="3">Ketersediaan: Sedang (3)</option>
                <option :value="5">Ketersediaan: Tinggi (5)</option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model="dataAssetForm.table_name"
                placeholder="Nama tabel"
              />
              <input
                v-model="dataAssetForm.column_name"
                placeholder="Nama kolom"
              />
            </div>
            <label class="toggle">
              <input
                v-model="dataAssetForm.contains_personal_data"
                type="checkbox"
              />
              <span>Mengandung data pribadi</span>
            </label>
            <div class="two-col">
              <input
                v-model="dataAssetForm.personal_data_type"
                placeholder="Jenis data pribadi"
              />
              <input
                v-model="dataAssetForm.processing_purpose"
                placeholder="Tujuan pemrosesan"
              />
            </div>
            <div class="two-col">
              <input
                v-model="dataAssetForm.retention_period"
                placeholder="Retensi data"
              />
              <input
                v-model="dataAssetForm.storage_location"
                placeholder="Lokasi penyimpanan"
              />
            </div>
            <input
              v-model="dataAssetForm.data_owner"
              placeholder="Pemilik data"
            />
            <textarea
              v-model="dataAssetForm.access_policy"
              placeholder="Kebijakan akses"
            ></textarea>
            <textarea
              v-model="dataAssetForm.description"
              placeholder="Deskripsi tambahan"
            ></textarea>
          </div>

          <div
            v-if="modal.module === 'application-documents'"
            class="modal-form"
          >
            <select v-model="applicationDocumentForm.aplikasi_id" required>
              <option value="">Aplikasi</option>
              <option v-for="app in applications" :key="app.id" :value="app.id">
                {{ app.nama }}
              </option>
            </select>
            <select
              v-model="applicationDocumentForm.document_category"
              required
            >
              <option value="">Kategori dokumen</option>
              <option value="petunjuk_teknis">Dok. Petunjuk Teknis</option>
              <option value="tata_kelola">Dok. Tatakelola</option>
              <option value="keamanan">Dok. Keamanan</option>
            </select>
            <input
              v-if="modal.mode === 'create'"
              type="file"
              multiple
              @change="setFiles(applicationDocumentForm, $event)"
            />
          </div>

          <div v-if="modal.module === 'app-integrations'" class="modal-form">
            <select v-model="appIntegrationForm.aplikasi_id" required>
              <option value="">Aplikasi</option>
              <option v-for="app in applications" :key="app.id" :value="app.id">
                {{ app.nama }}
              </option>
            </select>
            <textarea
              v-model="appIntegrationForm.deskripsi"
              placeholder="Deskripsi integrasi"
            ></textarea>
            <div class="two-col">
              <select v-model="appIntegrationForm.jenis_integrasi" required>
                <option value="">Jenis integrasi</option>
                <option value="proses_bisnis">Proses Bisnis</option>
                <option value="berbagi_data">Berbagi Data</option>
              </select>
              <select v-model="appIntegrationForm.metode_integrasi" required>
                <option value="">Metode integrasi</option>
                <option value="spl">SPL</option>
                <option value="host_to_host">Host-to-Host</option>
              </select>
            </div>
            <div class="inline-picker">
              <strong>Endpoint Target</strong>
              <button
                v-for="app in applications"
                :key="app.id"
                class="chip"
                :class="{
                  selected: appIntegrationForm.target_application_ids.includes(
                    app.id,
                  ),
                }"
                type="button"
                @click="
                  toggleInArray(
                    appIntegrationForm.target_application_ids,
                    app.id,
                  )
                "
              >
                {{ app.nama }}
              </button>
            </div>
            <textarea
              v-model="appIntegrationForm.external_endpoints"
              placeholder="Endpoint lainnya / eksternal"
            ></textarea>
            <div class="inline-picker">
              <strong>Data yang Dibagipakai</strong>
              <button
                v-for="asset in dataAssets"
                :key="asset.id"
                class="chip"
                :class="{
                  selected: appIntegrationForm.data_asset_ids.includes(
                    asset.id,
                  ),
                }"
                type="button"
                @click="
                  toggleInArray(appIntegrationForm.data_asset_ids, asset.id)
                "
              >
                {{ asset.name }}
              </button>
            </div>
            <input
              type="file"
              multiple
              @change="setFiles(appIntegrationForm, $event)"
            />
          </div>

          <div v-if="modal.module === 'app-database-docs'" class="modal-form">
            <select v-model="appDatabaseDocForm.aplikasi_id" required>
              <option value="">Aplikasi</option>
              <option v-for="app in applications" :key="app.id" :value="app.id">
                {{ app.nama }}
              </option>
            </select>
            <input
              v-model="appDatabaseDocForm.nama_database"
              required
              placeholder="Nama basis data"
            />
            <div class="two-col">
              <select v-model="appDatabaseDocForm.tipe_dbms" required>
                <option value="">Tipe DBMS</option>
                <option value="MySQL">MySQL</option>
                <option value="MariaDB">MariaDB</option>
                <option value="PostgreSQL">PostgreSQL</option>
                <option value="SQL Server">SQL Server</option>
                <option value="Oracle">Oracle</option>
                <option value="MongoDB">MongoDB</option>
                <option value="SQLite">SQLite</option>
                <option value="Firebird">Firebird</option>
              </select>
              <input v-model="appDatabaseDocForm.versi" placeholder="Versi" />
            </div>
            <div class="two-col">
              <input
                v-model="appDatabaseDocForm.host"
                placeholder="Host / Server"
              />
              <input
                v-model.number="appDatabaseDocForm.port"
                type="number"
                min="1"
                max="65535"
                placeholder="Port"
              />
            </div>
            <div class="two-col">
              <input
                v-model="appDatabaseDocForm.nama_db_asli"
                placeholder="Nama database sebenarnya"
              />
              <input
                v-model.number="appDatabaseDocForm.jumlah_tabel"
                type="number"
                min="0"
                placeholder="Jumlah tabel"
              />
            </div>
            <textarea
              v-model="appDatabaseDocForm.keterangan"
              placeholder="Keterangan"
            ></textarea>
            <div class="field-label">
              <span>Dokumen Rancangan Basis Data</span>
            </div>
            <input
              type="file"
              accept=".pdf,.doc,.docx,.drawio,.sql,.png,.jpg"
              @change="setFiles(appDatabaseDocForm, $event)"
            />
          </div>

          <div v-if="modal.module === 'backup-media'" class="modal-form">
            <input
              v-model="backupMediaForm.nama"
              required
              placeholder="Nama media"
            />
            <div class="two-col">
              <select v-model="backupMediaForm.location" required>
                <option value="">Lokasi media</option>
                <option value="local">Local</option>
                <option value="remote">Remote</option>
              </select>
              <select v-model="backupMediaForm.jenis_media" required>
                <option value="">Jenis media</option>
                <option value="NAS">NAS</option>
                <option value="Disk">Disk</option>
                <option value="Cloud">Cloud</option>
                <option value="Replication">Replication</option>
                <option value="Tape">Tape</option>
                <option value="Object Storage">Object Storage</option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model.number="backupMediaForm.kapasitas_gb"
                type="number"
                min="1"
                placeholder="Kapasitas GB"
              />
              <input
                v-model="backupMediaForm.address_url"
                placeholder="Address / URL"
              />
            </div>
          </div>

          <div v-if="modal.module === 'backup-jobs'" class="modal-form">
            <div class="two-col">
              <select v-model="backupJobForm.aplikasi_id" required>
                <option value="">Aplikasi</option>
                <option
                  v-for="app in applications"
                  :key="app.id"
                  :value="app.id"
                >
                  {{ app.nama }}
                </option>
              </select>
              <select v-model="backupJobForm.backup_media_id" required>
                <option value="">Media Pencadangan</option>
                <option
                  v-for="media in backupMedia"
                  :key="media.id"
                  :value="media.id"
                >
                  {{ media.nama }}
                </option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model.number="backupJobForm.retensi_n"
                type="number"
                min="1"
                placeholder="n Retensi"
              />
              <select v-model="backupJobForm.retensi_unit">
                <option value="">Jenis retensi</option>
                <option value="realtime">Realtime</option>
                <option value="menit">Menit</option>
                <option value="jam">Jam</option>
                <option value="hari">Hari</option>
                <option value="minggu">Minggu</option>
                <option value="bulan">Bulan</option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model.number="backupJobForm.repetisi_n"
                type="number"
                min="1"
                placeholder="n Repetisi"
              />
              <select v-model="backupJobForm.repetisi_unit">
                <option value="">Jenis repetisi</option>
                <option value="realtime">Realtime</option>
                <option value="menit">Menit</option>
                <option value="jam">Jam</option>
                <option value="hari">Hari</option>
                <option value="minggu">Minggu</option>
                <option value="bulan">Bulan</option>
              </select>
            </div>
          </div>

          <div v-if="modal.module === 'ups-devices'" class="modal-form">
            <input
              v-model="upsDeviceForm.nama"
              required
              placeholder="Nama UPS / Power Backup"
            />
            <div class="two-col">
              <input
                v-model.number="upsDeviceForm.kapasitas_va"
                required
                type="number"
                min="1"
                placeholder="Kapasitas VA"
              />
              <select v-model="upsDeviceForm.kondisi" required>
                <option value="">Kondisi UPS</option>
                <option value="baik">Baik</option>
                <option value="kurang_baik">Kurang Baik</option>
                <option value="rusak">Rusak</option>
              </select>
            </div>
            <select v-model="upsDeviceForm.dc_id">
              <option value="">Gedung / Ruang DC</option>
              <option
                v-for="dc in references.data_centers"
                :key="dc.id"
                :value="dc.id"
              >
                {{ dc.nama }}
              </option>
            </select>
          </div>

          <div v-if="modal.module === 'soc-tools'" class="modal-form">
            <input
              v-model="socToolForm.nama"
              required
              placeholder="Nama Platform / Device / Tools"
            />
            <textarea
              v-model="socToolForm.deskripsi_fungsi"
              placeholder="Deskripsi fungsi"
            ></textarea>
            <select v-model="socToolForm.jenis" required>
              <option value="">Jenis SOC tool</option>
              <option value="Firewall">Firewall</option>
              <option value="IDS">IDS</option>
              <option value="IPS">IPS</option>
              <option value="Antivirus">Antivirus</option>
              <option value="EDR">EDR</option>
              <option value="SIEM">SIEM</option>
              <option value="WAF">WAF</option>
              <option value="NDR">NDR</option>
              <option value="Vulnerability Scanner">
                Vulnerability Scanner
              </option>
              <option value="Log Management">Log Management</option>
            </select>
            <div class="inline-picker">
              <strong>Coverage Data Center</strong>
              <button
                v-for="dc in references.data_centers"
                :key="dc.id"
                class="chip"
                :class="{ selected: socToolForm.dc_ids.includes(dc.id) }"
                type="button"
                @click="toggleInArray(socToolForm.dc_ids, dc.id)"
              >
                {{ dc.nama }}
              </button>
            </div>
            <div class="inline-picker">
              <strong>Coverage Server</strong>
              <button
                v-for="server in references.servers"
                :key="server.id"
                class="chip"
                :class="{
                  selected: socToolForm.server_ids.includes(server.id),
                }"
                type="button"
                @click="toggleInArray(socToolForm.server_ids, server.id)"
              >
                {{ server.nama }}
              </button>
            </div>
            <div class="inline-picker">
              <strong>Coverage VM</strong>
              <button
                v-for="vm in references.vms"
                :key="vm.id"
                class="chip"
                :class="{ selected: socToolForm.vm_ids.includes(vm.id) }"
                type="button"
                @click="toggleInArray(socToolForm.vm_ids, vm.id)"
              >
                {{ vm.nama }}
              </button>
            </div>
            <div class="inline-picker">
              <strong>Coverage Aplikasi</strong>
              <button
                v-for="app in applications"
                :key="app.id"
                class="chip"
                :class="{
                  selected: socToolForm.application_ids.includes(app.id),
                }"
                type="button"
                @click="toggleInArray(socToolForm.application_ids, app.id)"
              >
                {{ app.nama }}
              </button>
            </div>
          </div>

          <div v-if="modal.module === 'network-sites'" class="modal-form">
            <div class="two-col">
              <input
                v-model="networkSiteForm.nama"
                required
                placeholder="Nama site / node"
              />
              <input
                v-model="networkSiteForm.kode"
                placeholder="Kode site, contoh: OPD-DISKOMINFO-LT2"
              />
            </div>
            <div class="three-col">
              <select v-model="networkSiteForm.jenis" required>
                <option value="">Jenis site</option>
                <option
                  v-for="option in networkSiteTypeOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <select v-model="networkSiteForm.status">
                <option value="">Status</option>
                <option value="aktif">Aktif</option>
                <option value="maintenance">Maintenance</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
              <select v-model="networkSiteForm.opd_id">
                <option value="">OPD / pemilik lokasi</option>
                <option
                  v-for="opd in references.opd"
                  :key="opd.id"
                  :value="opd.id"
                >
                  {{ opd.nama }}
                </option>
              </select>
            </div>
            <div class="two-col">
              <select v-model="networkSiteForm.dc_id">
                <option value="">Gedung / Ruang DC terkait</option>
                <option
                  v-for="dc in references.data_centers"
                  :key="dc.id"
                  :value="dc.id"
                >
                  {{ dc.nama }}
                </option>
              </select>
              <select v-model="networkSiteForm.rack_id">
                <option value="">Rack terkait</option>
                <option
                  v-for="rack in references.racks"
                  :key="rack.id"
                  :value="rack.id"
                >
                  {{ rack.nama }}
                </option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model="networkSiteForm.alamat"
                placeholder="Alamat / area pemasangan"
              />
              <input
                v-model="networkSiteForm.lokasi_detail"
                placeholder="Detail lokasi, contoh: Lantai 2 ruang command center"
              />
            </div>
            <div class="three-col">
              <input
                v-model="networkSiteForm.titik_koordinat"
                placeholder="Titik koordinat"
              />
              <input
                v-model="networkSiteForm.pic_nama"
                placeholder="Nama PIC lokasi"
              />
              <input
                v-model="networkSiteForm.pic_kontak"
                placeholder="Kontak PIC"
              />
            </div>
            <textarea
              v-model="networkSiteForm.catatan"
              placeholder="Catatan site / node"
            ></textarea>
          </div>

          <div
            v-if="modal.module === 'network-monitorings'"
            class="modal-form monitoring-form"
          >
            <div class="two-col">
              <select
                v-model="networkMonitoringForm.opd_id"
                @change="handleMonitoringOpdChange"
              >
                <option value="">OPD / pemilik</option>
                <option
                  v-for="opd in references.opd"
                  :key="opd.id"
                  :value="opd.id"
                >
                  {{ opd.nama }}
                </option>
              </select>
              <select
                v-model="networkMonitoringForm.site_id"
                required
                @change="handleMonitoringSiteChange"
              >
                <option :value="ALL_NETWORK_SITES_VALUE">
                  Semua Site/Node
                </option>
                <option
                  v-for="site in monitoringSiteOptions"
                  :key="site.id"
                  :value="site.id"
                >
                  {{ site.nama }} - {{ siteOwnerName(site) }}
                </option>
              </select>
            </div>
            <div class="two-col">
              <label class="field-label">
                <span>Tanggal & Jam Pemantauan</span>
                <input
                  v-model="networkMonitoringForm.monitoring_at"
                  required
                  type="datetime-local"
                />
              </label>
              <input
                v-model="networkMonitoringForm.period_month"
                type="month"
                placeholder="Periode bulan"
              />
            </div>
            <div class="two-col">
              <textarea
                v-model="networkMonitoringForm.officers_text"
                required
                placeholder="Nama petugas monitoring, pisahkan dengan koma atau baris baru"
              ></textarea>
            </div>

            <section class="monitoring-form-section">
              <div class="section-heading compact">
                <h4>Hasil Uji Koneksi</h4>
                <Activity :size="20" />
              </div>
              <div class="three-col">
                <input
                  v-model.number="networkMonitoringForm.speedtest_download_mbps"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="Download Mbps"
                />
                <input
                  v-model.number="networkMonitoringForm.speedtest_upload_mbps"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="Upload Mbps"
                />
                <input
                  v-model.number="networkMonitoringForm.speedtest_ping_ms"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="Ping ms"
                />
              </div>
            </section>

            <section class="monitoring-form-section">
              <label class="toggle">
                <input
                  v-model="networkMonitoringForm.tower_available"
                  type="checkbox"
                />
                <span>Site memiliki menara internet / tower</span>
              </label>
              <div
                v-if="networkMonitoringForm.tower_available"
                class="three-col"
              >
                <select v-model="networkMonitoringForm.tower_besi_condition">
                  <option value="">Kondisi besi</option>
                  <option
                    v-for="option in monitoringConditionOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <select v-model="networkMonitoringForm.tower_kawat_condition">
                  <option value="">Kondisi kawat</option>
                  <option
                    v-for="option in monitoringConditionOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <select v-model="networkMonitoringForm.tower_pondasi_condition">
                  <option value="">Kondisi pondasi</option>
                  <option
                    v-for="option in monitoringConditionOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <textarea
                v-if="networkMonitoringForm.tower_available"
                v-model="networkMonitoringForm.tower_notes"
                placeholder="Catatan kondisi menara"
              ></textarea>
            </section>

            <section class="monitoring-form-section">
              <div class="section-heading compact">
                <h4>Checklist Perangkat di Site</h4>
                <button
                  class="action-button ghost compact-button"
                  type="button"
                  :disabled="
                    networkMonitoringForm.site_id === ALL_NETWORK_SITES_VALUE &&
                    !networkMonitoringForm.opd_id
                  "
                  @click="populateMonitoringItemsFromSite(true)"
                >
                  Muat Ulang
                </button>
              </div>
              <div
                v-if="!networkMonitoringForm.items.length"
                class="empty-note"
              >
                Pilih OPD/Pemilik dan Site/Node yang sudah memiliki riwayat
                instalasi perangkat aktif.
              </div>
              <div v-else class="monitoring-checklist-table">
                <div class="monitoring-checklist-head">
                  <span>Perangkat</span>
                  <span>Kondisi</span>
                  <span>Keterangan</span>
                </div>
                <div
                  v-for="item in networkMonitoringForm.items"
                  :key="item.device_id"
                  class="monitoring-checklist-row"
                >
                  <div>
                    <strong>{{ item.device_name }}</strong>
                    <span>{{
                      [
                        item.site_name,
                        networkDeviceTypeLabel(item.device_type),
                        networkInstallationRoleLabel(item.role),
                      ]
                        .filter(Boolean)
                        .join(" / ")
                    }}</span>
                  </div>
                  <select v-model="item.condition" required>
                    <option value="">Pilih kondisi</option>
                    <option
                      v-for="option in monitoringConditionOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                  <textarea
                    v-model="item.note"
                    placeholder="Keterangan perangkat"
                  ></textarea>
                </div>
              </div>
            </section>

            <textarea
              v-model="networkMonitoringForm.notes"
              placeholder="Catatan umum pemantauan"
            ></textarea>

            <section class="monitoring-form-section">
              <label class="field-label">
                <span>Lampiran Monitoring</span>
                <input
                  multiple
                  type="file"
                  accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                  @change="setMonitoringAttachments"
                />
              </label>
              <small
                >Gunakan untuk foto petugas, foto speedtest, foto perangkat,
                foto menara, atau dokumen pendukung lainnya.</small
              >
              <div
                v-if="
                  modal.mode === 'edit' &&
                  (
                    networkMonitorings.find((row) => row.id === modal.id)
                      ?.attachments || []
                  ).length
                "
                class="monitoring-attachment-list"
              >
                <label
                  v-for="attachment in networkMonitorings.find(
                    (row) => row.id === modal.id,
                  )?.attachments || []"
                  :key="attachment.id"
                  class="toggle attachment-toggle"
                >
                  <input
                    :checked="
                      networkMonitoringForm.remove_attachment_ids.includes(
                        attachment.id,
                      )
                    "
                    type="checkbox"
                    @change="toggleRemoveMonitoringAttachment(attachment.id)"
                  />
                  <span
                    >Hapus {{ attachment.original_name }} ({{
                      formatFileSize(attachment.size_bytes)
                    }})</span
                  >
                </label>
              </div>
            </section>
          </div>

          <div v-if="modal.module === 'network-devices'" class="modal-form">
            <input
              v-model="networkDeviceForm.nama"
              required
              placeholder="Nama perangkat jaringan"
            />
            <select v-model="networkDeviceForm.site_id">
              <option value="">
                {{
                  modal.mode === "edit"
                    ? "Tidak mengubah site aktif"
                    : "Site / node pemasangan awal"
                }}
              </option>
              <option
                v-for="site in networkSites"
                :key="site.id"
                :value="site.id"
              >
                {{ networkSiteOptionLabel(site) }}
              </option>
            </select>
            <div class="three-col">
              <select v-model="networkDeviceForm.jenis" required>
                <option value="">Jenis perangkat</option>
                <option
                  v-for="option in networkDeviceTypeOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <select v-model="networkDeviceForm.status">
                <option value="">Status</option>
                <option value="aktif">Aktif</option>
                <option value="maintenance">Maintenance</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
              <select v-model="networkDeviceForm.kondisi">
                <option value="">Kondisi</option>
                <option value="baik">Baik</option>
                <option value="kurang_baik">Kurang Baik</option>
                <option value="rusak">Rusak</option>
              </select>
            </div>
            <div class="two-col">
              <input v-model="networkDeviceForm.merk" placeholder="Merk" />
              <input
                v-model="networkDeviceForm.model"
                placeholder="Model / tipe"
              />
            </div>
            <div class="two-col">
              <input
                v-model="networkDeviceForm.serial_number"
                placeholder="Serial number"
              />
              <input
                v-model="networkDeviceForm.mac_address"
                placeholder="MAC address"
              />
            </div>
            <div class="three-col">
              <input
                v-model="networkDeviceForm.os_firmware"
                placeholder="OS / firmware"
              />
              <input
                v-model.number="networkDeviceForm.kapasitas_port"
                type="number"
                min="1"
                placeholder="Jumlah port"
              />
              <input
                v-model="networkDeviceForm.bandwidth"
                placeholder="Bandwidth / throughput"
              />
            </div>
            <div class="three-col">
              <input
                v-model="networkDeviceForm.wireless_standard"
                placeholder="Wireless standard, contoh: Wi-Fi 6"
              />
              <input
                v-model="networkDeviceForm.frekuensi"
                placeholder="Frekuensi, contoh: 2.4/5 GHz"
              />
              <label class="toggle">
                <input
                  v-model="networkDeviceForm.poe_support"
                  type="checkbox"
                />
                <span>PoE support</span>
              </label>
            </div>
            <textarea
              v-model="networkDeviceForm.deskripsi"
              placeholder="Deskripsi perangkat / fungsi"
            ></textarea>
          </div>

          <div
            v-if="modal.module === 'network-installations'"
            class="modal-form"
          >
            <div class="two-col">
              <select v-model="networkInstallationForm.site_id" required>
                <option value="">Site / node</option>
                <option
                  v-for="site in networkSites"
                  :key="site.id"
                  :value="site.id"
                >
                  {{ site.nama }}
                </option>
              </select>
              <select v-model="networkInstallationForm.device_id" required>
                <option value="">Perangkat</option>
                <option
                  v-for="device in networkDevices"
                  :key="device.id"
                  :value="device.id"
                >
                  {{ networkDeviceOptionLabel(device) }}
                </option>
              </select>
            </div>
            <div class="three-col">
              <select v-model="networkInstallationForm.role">
                <option value="">Role perangkat</option>
                <option
                  v-for="option in networkInstallationRoleOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <select v-model="networkInstallationForm.status" required>
                <option value="">Status riwayat</option>
                <option value="aktif">Aktif</option>
                <option value="diganti">Diganti</option>
                <option value="dilepas">Dilepas</option>
                <option value="rusak">Rusak</option>
                <option value="maintenance">Maintenance</option>
              </select>
              <select v-model="networkInstallationForm.replaced_by_device_id">
                <option value="">Perangkat pengganti</option>
                <option
                  v-for="device in networkDevices.filter(
                    (item) => item.id !== networkInstallationForm.device_id,
                  )"
                  :key="device.id"
                  :value="device.id"
                >
                  {{ networkDeviceOptionLabel(device) }}
                </option>
              </select>
            </div>
            <div class="three-col">
              <label class="field-label">
                <span>Tanggal Pasang</span>
                <input
                  v-model="networkInstallationForm.installed_at"
                  type="date"
                />
              </label>
              <label class="field-label">
                <span>Tanggal Lepas / Diganti</span>
                <input
                  v-model="networkInstallationForm.removed_at"
                  type="date"
                />
              </label>
              <label class="field-label">
                <span>Dipasang / Dicatat Oleh</span>
                <input
                  v-model="networkInstallationForm.installed_by"
                  placeholder="Nama petugas"
                />
              </label>
            </div>
            <textarea
              v-model="networkInstallationForm.notes"
              placeholder="Catatan pemasangan, pergantian, atau pelepasan perangkat"
            ></textarea>
          </div>

          <div v-if="modal.module === 'network-ip-configs'" class="modal-form">
            <div class="two-col">
              <select
                v-model="networkIpConfigForm.site_id"
                required
                @change="handleNetworkIpConfigSiteChange"
              >
                <option value="">Site / node</option>
                <option
                  v-for="site in networkSites"
                  :key="site.id"
                  :value="site.id"
                >
                  {{ site.nama }}
                </option>
              </select>
              <select
                v-model="networkIpConfigForm.device_id"
                required
                :disabled="!networkIpConfigForm.site_id"
              >
                <option value="">
                  {{
                    networkIpConfigForm.site_id
                      ? "Perangkat pada site/node"
                      : "Pilih Site/Node terlebih dahulu"
                  }}
                </option>
                <option
                  v-for="device in networkIpConfigDeviceOptions"
                  :key="device.id"
                  :value="device.id"
                >
                  {{ networkDeviceOptionLabel(device) }}
                </option>
              </select>
            </div>
            <div class="three-col">
              <input
                v-model="networkIpConfigForm.interface_name"
                placeholder="Interface, contoh: ether1 / wlan1"
              />
              <select v-model="networkIpConfigForm.ip_type">
                <option value="">Tipe IP</option>
                <option
                  v-for="option in networkIpTypeOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <select v-model="networkIpConfigForm.status">
                <option value="">Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model="networkIpConfigForm.ip_address"
                placeholder="IP address manual"
              />
              <select v-model="networkIpConfigForm.ip_address_id">
                <option value="">Atau relasi IP Address CMDB</option>
                <option
                  v-for="ip in references.ips"
                  :key="ip.id"
                  :value="ip.id"
                >
                  {{ ip.ip }}
                </option>
              </select>
            </div>
            <div class="three-col">
              <input
                v-model="networkIpConfigForm.subnet_mask"
                placeholder="Subnet mask / CIDR"
              />
              <input
                v-model="networkIpConfigForm.gateway"
                placeholder="Gateway"
              />
              <input v-model="networkIpConfigForm.dns" placeholder="DNS" />
            </div>
            <div class="three-col">
              <input v-model="networkIpConfigForm.vlan" placeholder="VLAN" />
              <input v-model="networkIpConfigForm.ssid" placeholder="SSID" />
              <label class="toggle">
                <input
                  v-model="networkIpConfigForm.dhcp_enabled"
                  type="checkbox"
                />
                <span>DHCP aktif</span>
              </label>
            </div>
            <textarea
              v-model="networkIpConfigForm.notes"
              placeholder="Catatan konfigurasi IP"
            ></textarea>
          </div>

          <div v-if="modal.module === 'network-credentials'" class="modal-form">
            <div class="two-col">
              <select v-model="networkCredentialForm.device_id" required>
                <option value="">Perangkat</option>
                <option
                  v-for="device in networkDevices"
                  :key="device.id"
                  :value="device.id"
                >
                  {{ networkDeviceOptionLabel(device) }}
                </option>
              </select>
              <select v-model="networkCredentialForm.site_id">
                <option value="">Site / node terkait</option>
                <option
                  v-for="site in networkSites"
                  :key="site.id"
                  :value="site.id"
                >
                  {{ site.nama }}
                </option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model="networkCredentialForm.label"
                required
                placeholder="Label akses, contoh: Admin Web Router"
              />
              <select v-model="networkCredentialForm.access_method" required>
                <option value="">Metode akses</option>
                <option
                  v-for="option in networkAccessMethodOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div class="two-col">
              <input
                v-model="networkCredentialForm.management_url"
                placeholder="URL / address manajemen"
              />
              <input
                v-model="networkCredentialForm.username"
                placeholder="Username"
              />
            </div>
            <div class="two-col">
              <input
                v-model="networkCredentialForm.password"
                type="password"
                :placeholder="
                  modal.mode === 'edit'
                    ? 'Password baru (opsional)'
                    : 'Password'
                "
              />
              <input
                v-model="networkCredentialForm.last_rotated_at"
                type="date"
              />
            </div>
            <textarea
              v-model="networkCredentialForm.notes"
              placeholder="Catatan prosedur akses / rotasi"
            ></textarea>
          </div>

          <div v-if="modal.module === 'users'" class="modal-form">
            <input
              v-model="userForm.nama"
              required
              placeholder="Nama pengguna"
            />
            <input
              v-model="userForm.email"
              required
              type="email"
              placeholder="Email"
            />
            <input
              v-model="userForm.password"
              :required="modal.mode === 'create'"
              type="password"
              :placeholder="
                modal.mode === 'create'
                  ? 'Password'
                  : 'Password baru (opsional)'
              "
            />
            <select v-model="userForm.opd_id">
              <option value="">OPD</option>
              <option
                v-for="opd in references.opd"
                :key="opd.id"
                :value="opd.id"
              >
                {{ opd.nama }}
              </option>
            </select>
            <div class="two-col">
              <select v-model="userForm.role" required>
                <option value="">Role</option>
                <option value="full">Full</option>
                <option value="read_only">Read Only</option>
              </select>
              <select v-model="userForm.status" required>
                <option value="">Status pengguna</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
          </div>

          <footer class="modal-actions">
            <button
              class="action-button ghost"
              type="button"
              @click="closeModal"
            >
              Batal
            </button>
            <button class="action-button" type="submit" :disabled="saving">
              {{
                saving
                  ? "Menyimpan..."
                  : modal.mode === "edit"
                    ? "Simpan Perubahan"
                    : "Simpan Data"
              }}
            </button>
          </footer>
        </form>
      </div>

      <div
        v-if="revealPasswordModal.open"
        class="modal-backdrop alert-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeRevealPasswordModal)"
      >
        <form
          class="modal-card alert-modal-card"
          @submit.prevent="submitRevealPassword"
        >
          <header class="modal-header">
            <div>
              <p class="eyebrow">Kredensial</p>
              <h3>Reveal Password</h3>
            </div>
            <button
              class="icon-button"
              type="button"
              title="Tutup reveal password"
              @click="closeRevealPasswordModal"
            >
              <X :size="18" />
            </button>
          </header>

          <div class="modal-form">
            <div class="detail-highlight">
              <span>{{
                revealPasswordModal.credential?.device?.nama || "Perangkat"
              }}</span>
              <strong>{{
                revealPasswordModal.credential?.label || "-"
              }}</strong>
              <small>{{
                networkAccessMethodLabel(
                  revealPasswordModal.credential?.access_method,
                )
              }}</small>
            </div>
            <label class="field-label">
              <span>Password Akun</span>
              <input
                v-model="revealPasswordModal.account_password"
                required
                type="password"
                autocomplete="current-password"
                placeholder="Masukkan password akun Anda"
              />
            </label>
            <p v-if="revealPasswordModal.error" class="alert">
              {{ revealPasswordModal.error }}
            </p>
            <label
              v-if="revealPasswordModal.revealed_password"
              class="field-label"
            >
              <span>Password Kredensial</span>
              <div class="secret-output">
                <input
                  :value="revealPasswordModal.revealed_password"
                  readonly
                />
                <button
                  class="icon-button"
                  type="button"
                  title="Copy password"
                  @click="copyRevealedPassword"
                >
                  <Copy :size="16" />
                </button>
              </div>
              <small v-if="revealPasswordModal.copied">Password disalin</small>
            </label>
          </div>

          <footer class="modal-actions">
            <button
              class="action-button ghost"
              type="button"
              :disabled="revealPasswordModal.loading"
              @click="closeRevealPasswordModal"
            >
              Tutup
            </button>
            <button
              class="action-button"
              type="submit"
              :disabled="revealPasswordModal.loading"
            >
              {{
                revealPasswordModal.loading
                  ? "Memverifikasi..."
                  : "Reveal Password"
              }}
            </button>
          </footer>
        </form>
      </div>

      <div
        v-if="labelModal.open && labelModal.item"
        class="modal-backdrop label-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeLabelModal)"
      >
        <section class="modal-card label-modal-card">
          <header class="modal-header">
            <div>
              <p class="eyebrow">Label Inventaris</p>
              <h3>{{ assetName(labelModal.item, labelModal.module) }}</h3>
            </div>
            <button
              class="icon-button"
              type="button"
              title="Tutup label"
              @click="closeLabelModal"
            >
              <X :size="18" />
            </button>
          </header>

          <div class="label-toolbar">
            <select v-model="labelModal.size">
              <option
                v-for="option in labelSizeOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
            <button class="action-button" type="button" @click="printLabel">
              <Printer :size="17" /> Cetak Label
            </button>
          </div>

          <div class="label-preview-wrap">
            <article class="inventory-label-print" :style="labelPrintStyle">
              <div class="label-brand">
                <img :src="logoLangkat" alt="Logo Kabupaten Langkat" />
                <div>
                  <strong>PEMKAB LANGKAT</strong>
                  <span>IAMT CMDB</span>
                </div>
              </div>
              <div class="label-body">
                <div>
                  <small>Kode Aset</small>
                  <strong>{{ assetCode(labelModal.item) }}</strong>
                  <span>{{ moduleLabels[labelModal.module] || "Aset" }}</span>
                  <b>{{ assetName(labelModal.item, labelModal.module) }}</b>
                  <em>{{
                    assetLocation(labelModal.module, labelModal.item)
                  }}</em>
                </div>
                <img
                  v-if="labelQrDataUrl"
                  :src="labelQrDataUrl"
                  alt="QR detail aset"
                />
              </div>
              <footer>
                {{ assetDetailUrl(labelModal.module, labelModal.item) }}
              </footer>
            </article>
          </div>
        </section>
      </div>

      <div
        v-if="monitoringReportModal.open && monitoringReportModal.item"
        class="modal-backdrop label-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeMonitoringReport)"
      >
        <section class="modal-card monitoring-report-modal-card">
          <header class="modal-header">
            <div>
              <p class="eyebrow">Laporan Monitoring</p>
              <h3>{{ monitoringScopeLabel(monitoringReportModal.item) }}</h3>
            </div>
            <button
              class="icon-button"
              type="button"
              title="Tutup laporan"
              @click="closeMonitoringReport"
            >
              <X :size="18" />
            </button>
          </header>

          <div class="label-toolbar">
            <span>{{
              formatDateTime(monitoringReportModal.item.monitoring_at)
            }}</span>
            <button
              class="action-button"
              type="button"
              @click="printMonitoringReport"
            >
              <Printer :size="17" /> Cetak Laporan
            </button>
          </div>

          <article class="monitoring-report-print">
            <header class="report-header">
              <div class="report-brand">
                <img :src="logoLangkat" alt="Logo Kabupaten Langkat" />
                <div>
                  <p>PEMERINTAH KABUPATEN LANGKAT</p>
                  <h1>Laporan Monitoring Site Jaringan</h1>
                  <span>IAMT CMDB Langkat</span>
                </div>
              </div>
              <img
                v-if="monitoringReportQrDataUrl"
                class="report-qr"
                :src="monitoringReportQrDataUrl"
                alt="QR detail monitoring"
              />
            </header>

            <section class="report-title-block">
              <div>
                <span>Site / Node</span>
                <strong>{{
                  monitoringScopeLabel(monitoringReportModal.item)
                }}</strong>
                <small>{{
                  assetLocation(
                    "network-monitorings",
                    monitoringReportModal.item,
                  )
                }}</small>
              </div>
              <div>
                <span>Kode Laporan</span>
                <strong>{{ assetCode(monitoringReportModal.item) }}</strong>
                <small>QR publik menuju detail monitoring</small>
              </div>
            </section>

            <section class="report-summary-grid">
              <div>
                <span>Periode</span>
                <strong>{{
                  monitoringReportModal.item.period_month || "-"
                }}</strong>
              </div>
              <div>
                <span>Speedtest</span>
                <strong>{{
                  monitoringSpeedSummary(monitoringReportModal.item)
                }}</strong>
              </div>
            </section>

            <section class="report-section">
              <h2>Checklist Kondisi Perangkat</h2>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Perangkat</th>
                    <th>Jenis / Role</th>
                    <th>Kondisi</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, index) in monitoringReportModal.item.items ||
                    []"
                    :key="item.id || item.device_id"
                  >
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ item.device?.nama || "-" }}</strong
                      ><span>{{
                        [item.device?.asset_code, item.installation?.site?.nama]
                          .filter(Boolean)
                          .join(" / ")
                      }}</span>
                    </td>
                    <td>
                      {{ networkDeviceTypeLabel(item.device?.jenis) }} /
                      {{
                        networkInstallationRoleLabel(item.installation?.role)
                      }}
                    </td>
                    <td>
                      <span :class="statusClass(item.condition)">{{
                        monitoringConditionLabel(item.condition)
                      }}</span>
                    </td>
                    <td>{{ item.note || "-" }}</td>
                  </tr>
                </tbody>
              </table>
            </section>

            <section
              v-if="monitoringReportModal.item.tower_available"
              class="report-section"
            >
              <h2>Kondisi Menara Internet</h2>
              <div class="report-summary-grid compact-report-grid">
                <div>
                  <span>Besi</span
                  ><strong>{{
                    monitoringConditionLabel(
                      monitoringReportModal.item.tower_besi_condition,
                    )
                  }}</strong>
                </div>
                <div>
                  <span>Kawat</span
                  ><strong>{{
                    monitoringConditionLabel(
                      monitoringReportModal.item.tower_kawat_condition,
                    )
                  }}</strong>
                </div>
                <div>
                  <span>Pondasi</span
                  ><strong>{{
                    monitoringConditionLabel(
                      monitoringReportModal.item.tower_pondasi_condition,
                    )
                  }}</strong>
                </div>
              </div>
              <p
                v-if="monitoringReportModal.item.tower_notes"
                class="report-note"
              >
                {{ monitoringReportModal.item.tower_notes }}
              </p>
            </section>

            <section
              v-if="monitoringReportModal.item.notes"
              class="report-section"
            >
              <h2>Catatan Umum</h2>
              <p class="report-note">{{ monitoringReportModal.item.notes }}</p>
            </section>

            <section
              v-if="
                monitoringImageAttachments(monitoringReportModal.item).length
              "
              class="report-section"
            >
              <h2>Lampiran Foto</h2>
              <div class="report-attachment-grid">
                <figure
                  v-for="attachment in monitoringImageAttachments(
                    monitoringReportModal.item,
                  )"
                  :key="attachment.id"
                >
                  <img :src="attachment.url" :alt="attachment.original_name" />
                  <figcaption>{{ attachment.original_name }}</figcaption>
                </figure>
              </div>
            </section>

            <section
              v-if="
                monitoringDocumentAttachments(monitoringReportModal.item).length
              "
              class="report-section"
            >
              <h2>Lampiran Dokumen</h2>
              <ul class="report-document-list">
                <li
                  v-for="attachment in monitoringDocumentAttachments(
                    monitoringReportModal.item,
                  )"
                  :key="attachment.id"
                >
                  {{ attachment.original_name }} -
                  {{ formatFileSize(attachment.size_bytes) }}
                </li>
              </ul>
            </section>

            <footer class="report-footer">
              <div>
                <span class="report-label">Waktu Pemantauan</span>
                <strong>{{
                  formatDateTime(monitoringReportModal.item.monitoring_at)
                }}</strong>
              </div>
            </footer>

            <div class="report-signature">
              <div class="report-signature-block officers-signature-block">
                <span class="report-label">Petugas Monitoring:</span>
                <table class="officers-signature-table">
                  <tr
                    v-for="(officer, idx) in (
                      monitoringReportModal.item?.officers || []
                    ).filter(Boolean)"
                    :key="idx"
                  >
                    <td class="officer-num">{{ idx + 1 }}.</td>
                    <td class="officer-name">{{ officer }}</td>
                    <td class="officer-colon">:</td>
                    <td class="officer-dots">...................</td>
                  </tr>
                </table>
              </div>
              <div class="report-signature-block pimpinan-signature-block">
                <span class="report-label">Mengetahui,</span>
                <strong class="report-jabatan"
                  >Kepala Bidang Pengembangan<br />Teknologi dan Aplikasi
                  Informatika</strong
                >
                <div class="report-signature-space"></div>
                <strong>IBADI RAHMAN, S.Kom.</strong>
                <span>NIP. 19871125 201101 1 014</span>
              </div>
            </div>
          </article>
        </section>
      </div>

      <div
        v-if="detailModal.open"
        class="modal-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeDetailModal)"
      >
        <section class="modal-card detail-modal-card">
          <header class="modal-header">
            <div>
              <p class="eyebrow">Detail Aset</p>
              <h3>
                {{
                  detailModal.loading
                    ? "Memuat..."
                    : assetName(detailModal.item, detailModal.module)
                }}
              </h3>
            </div>
            <button
              class="icon-button"
              type="button"
              title="Tutup detail"
              @click="closeDetailModal"
            >
              <X :size="18" />
            </button>
          </header>

          <div v-if="detailModal.item" class="detail-grid">
            <div class="detail-highlight">
              <span>Kode Aset</span>
              <strong>{{ assetCode(detailModal.item) }}</strong>
              <small>{{
                moduleLabels[detailModal.module] || detailModal.module
              }}</small>
            </div>
            <div
              v-for="entry in detailEntries(detailModal.item)"
              :key="entry.key"
            >
              <span>{{ entry.key }}</span>
              <strong>{{ entry.value }}</strong>
            </div>
            <div
              v-for="entry in relationEntries(detailModal.item)"
              :key="entry.key"
            >
              <span>{{ entry.key }}</span>
              <strong>{{ entry.value }}</strong>
            </div>
          </div>
        </section>
      </div>

      <div
        v-if="deleteModal.open"
        class="modal-backdrop alert-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeDeleteModal)"
      >
        <section class="modal-card alert-modal-card">
          <header class="modal-header">
            <div>
              <p class="eyebrow">Konfirmasi Hapus</p>
              <h3>Hapus {{ deleteModal.label }}?</h3>
            </div>
            <AlertTriangle :size="28" />
          </header>
          <p class="alert-modal-message">
            Data akan dihapus permanen. Jika masih ada data turunan atau relasi
            yang memakai entitas ini, sistem akan membatalkan proses hapus dan
            menampilkan peringatan.
          </p>
          <footer class="modal-actions">
            <button
              class="action-button ghost"
              type="button"
              :disabled="deleting"
              @click="closeDeleteModal"
            >
              Batal
            </button>
            <button
              class="action-button danger"
              type="button"
              :disabled="deleting"
              @click="confirmDelete"
            >
              {{ deleting ? "Menghapus..." : "Ya, Hapus Data" }}
            </button>
          </footer>
        </section>
      </div>

      <div
        v-if="alertModal.open"
        class="modal-backdrop alert-backdrop"
        @pointerdown="handleBackdropPointerDown"
        @click="closeFromBackdrop($event, closeAlert)"
      >
        <section class="modal-card alert-modal-card">
          <header class="modal-header">
            <div>
              <p class="eyebrow">Perhatian</p>
              <h3>{{ alertModal.title }}</h3>
            </div>
            <AlertTriangle :size="28" />
          </header>
          <p class="alert-modal-message">{{ alertModal.message }}</p>
          <footer class="modal-actions">
            <button class="action-button" type="button" @click="closeAlert">
              Mengerti
            </button>
          </footer>
        </section>
      </div>
    </main>
  </div>
</template>
