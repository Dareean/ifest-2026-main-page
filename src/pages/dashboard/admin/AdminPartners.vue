<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import {
  Handshake, Plus, Edit, Trash2, Eye, EyeOff, Save, X, ExternalLink
} from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()

const partners = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalTitle = ref('Tambah Partner')

const form = ref({
  id: null,
  type: 'main_strategic',
  name: '',
  logo_url: '',
  instagram_url: '',
  description: '',
  tier_data: { tier: 'tungsten' },
  order: 0,
  is_active: true
})

const partnerTypes = [
  { value: 'main_strategic', label: 'Main Strategic Partner' },
  { value: 'strategic_partner', label: 'Strategic Partner' },
  { value: 'media_partner', label: 'Media Partner' },
  { value: 'organizer', label: 'Organizer' },
  { value: 'sponsorship_tier', label: 'Sponsorship Tier' }
]

const sponsorshipTiers = [
  { value: 'tungsten', label: 'Tungsten (~50% RAB)' },
  { value: 'maestro', label: 'Maestro (Rp 80 - 500 Jt)' },
  { value: 'diamond', label: 'Diamond (Rp 40 - 80 Jt)' },
  { value: 'gold', label: 'Gold (Rp 10 - 40 Jt)' },
  { value: 'silver', label: 'Silver' },
  { value: 'bronze', label: 'Bronze' }
]

async function fetchPartners() {
  loading.value = true
  try {
    const res = await api.get('/admin/partners')
    partners.value = res.data.data
  } catch (e) {
    showToast('Gagal memuat data partner', 'error')
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  modalTitle.value = 'Tambah Partner'
  form.value = {
    id: null,
    type: 'main_strategic',
    name: '',
    logo_url: '',
    instagram_url: '',
    description: '',
    tier_data: { tier: 'tungsten' },
    order: 0,
    is_active: true
  }
  showModal.value = true
}

function openEditModal(partner) {
  modalTitle.value = 'Edit Partner'
  form.value = {
    id: partner.id,
    type: partner.type,
    name: partner.name,
    logo_url: partner.logo_url,
    instagram_url: partner.instagram_url || '',
    description: partner.description || '',
    tier_data: partner.tier_data || { tier: 'tungsten' },
    order: partner.order || 0,
    is_active: partner.is_active
  }
  showModal.value = true
}

async function handleSave() {
  if (!form.value.name || !form.value.logo_url) {
    showToast('Nama dan URL Logo wajib diisi', 'warning')
    return
  }

  saving.value = true
  try {
    const payload = {
      type: form.value.type,
      name: form.value.name,
      logo_url: form.value.logo_url,
      instagram_url: form.value.instagram_url || null,
      description: form.value.description || null,
      tier_data: form.value.type === 'sponsorship_tier' ? form.value.tier_data : null,
      order: parseInt(form.value.order) || 0,
      is_active: form.value.is_active
    }

    if (form.value.id) {
      await api.put(`/admin/partners/${form.value.id}`, payload)
      showToast('Partner berhasil diperbarui', 'success')
    } else {
      await api.post('/admin/partners', payload)
      showToast('Partner berhasil ditambahkan', 'success')
    }
    showModal.value = false
    await fetchPartners()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan partner', 'error')
  } finally {
    saving.value = false
  }
}

async function handleDelete(partner) {
  if (!await confirmModal.confirm(
    `Apakah Anda yakin ingin menghapus partner "${partner.name}"?`,
    'Hapus Partner'
  )) return

  try {
    await api.delete(`/admin/partners/${partner.id}`)
    showToast('Partner berhasil dihapus', 'success')
    await fetchPartners()
  } catch (e) {
    showToast('Gagal menghapus partner', 'error')
  }
}

function getTypeName(typeValue) {
  return partnerTypes.find(t => t.value === typeValue)?.label || typeValue
}

onMounted(fetchPartners)
</script>

<template>
  <div class="max-w-6xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
          <Handshake class="w-5 h-5 text-[#DCEEB1]" />
        </div>
        <div>
          <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola Partner</h1>
          <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Kelola sponsor, media partner, organizer, dan tiering kemitraan</p>
        </div>
      </div>
      <button
        @click="openAddModal"
        class="flex items-center justify-center gap-2 px-4 py-2 bg-[#04000D] hover:bg-[#04000D]/90 text-[#DCEEB1] font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-[2px_2px_0px_0px_#FF3D8B]"
      >
        <Plus class="w-4 h-4" /> Tambah Partner
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-pulse text-xs font-semibold text-on-surface-variant/40">Memuat data...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="partners.length === 0" class="text-center py-16 bg-white border border-dashed border-slate-200/80 rounded-2xl">
      <p class="text-sm text-on-surface-variant/60 font-semibold">Belum ada partner yang ditambahkan.</p>
    </div>

    <!-- Partners Grid/List -->
    <div v-else class="bg-white border border-slate-200/60 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100 text-on-surface-variant font-bold">
              <th class="p-4 w-12">Order</th>
              <th class="p-4 w-20">Logo</th>
              <th class="p-4">Nama</th>
              <th class="p-4">Tipe</th>
              <th class="p-4">Status</th>
              <th class="p-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="partner in partners" :key="partner.id" class="hover:bg-slate-50/30 transition-colors">
              <td class="p-4 font-mono font-bold text-on-surface-variant">{{ partner.order }}</td>
              <td class="p-4">
                <div class="w-10 h-10 border border-slate-100 bg-slate-50/50 rounded flex items-center justify-center p-1.5 overflow-hidden">
                  <img :src="partner.logo_url" :alt="partner.name" class="max-w-full max-h-full object-contain" />
                </div>
              </td>
              <td class="p-4">
                <div class="font-bold text-on-surface text-sm">{{ partner.name }}</div>
                <div class="text-[10px] text-on-surface-variant/75 mt-0.5 flex items-center gap-1.5" v-if="partner.instagram_url">
                  <a :href="partner.instagram_url" target="_blank" class="hover:text-primary flex items-center gap-0.5">
                    @{{ partner.instagram_url.split('instagram.com/')[1]?.replace('/', '') || 'Instagram' }}
                    <ExternalLink class="w-2.5 h-2.5" />
                  </a>
                </div>
              </td>
              <td class="p-4">
                <span class="px-2 py-0.5 rounded-full font-bold uppercase text-[9px] tracking-wider border"
                  :class="partner.type === 'main_strategic' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                           partner.type === 'strategic_partner' ? 'bg-sky-50 text-sky-700 border-sky-200' :
                           partner.type === 'media_partner' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                           partner.type === 'organizer' ? 'bg-slate-100 text-slate-700 border-slate-300' :
                           'bg-emerald-50 text-emerald-700 border-emerald-200'"
                >
                  {{ getTypeName(partner.type) }}
                  <span v-if="partner.type === 'sponsorship_tier' && partner.tier_data?.tier" class="normal-case font-mono font-semibold">
                    ({{ partner.tier_data.tier }})
                  </span>
                </span>
              </td>
              <td class="p-4">
                <span
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="partner.is_active
                    ? 'bg-sky-50 text-sky-700 border border-sky-200'
                    : 'bg-slate-100 text-slate-500 border border-slate-200'"
                >
                  <Eye class="w-3 h-3" v-if="partner.is_active" />
                  <EyeOff class="w-3 h-3" v-else />
                  {{ partner.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openEditModal(partner)"
                    class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors border border-slate-200"
                    title="Edit Partner"
                  >
                    <Edit class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="handleDelete(partner)"
                    class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition-colors border border-rose-200"
                    title="Hapus Partner"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form (Tambah/Edit) -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-[#04000D]/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white border-3 border-[#04000D] rounded-2xl w-full max-w-lg overflow-hidden shadow-[6px_6px_0px_0px_#04000D] flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b-2 border-[#04000D] bg-slate-50 flex items-center justify-between">
          <h3 class="font-extrabold text-base uppercase tracking-wider text-[#04000D]">{{ modalTitle }}</h3>
          <button @click="showModal = false" class="text-slate-500 hover:text-slate-800 transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Body (Scrollable if content overflows) -->
        <div class="p-6 overflow-y-auto space-y-4 text-xs font-semibold text-on-surface">
          <!-- Type -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Tipe Partner</label>
            <select
              v-model="form.type"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            >
              <option v-for="type in partnerTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
          </div>

          <!-- Sponsorship Tier Select (Conditional) -->
          <div v-if="form.type === 'sponsorship_tier'">
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Pilih Tier</label>
            <select
              v-model="form.tier_data.tier"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            >
              <option v-for="tier in sponsorshipTiers" :key="tier.value" :value="tier.value">{{ tier.label }}</option>
            </select>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Nama Partner</label>
            <input
              type="text"
              v-model="form.name"
              placeholder="Contoh: Hannah Asa Indonesia"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            />
          </div>

          <!-- Logo URL -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">URL Logo (Image URL)</label>
            <input
              type="text"
              v-model="form.logo_url"
              placeholder="Contoh: https://example.com/logo.webp"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            />
          </div>

          <!-- Instagram URL -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">URL Instagram (Opsional)</label>
            <input
              type="text"
              v-model="form.instagram_url"
              placeholder="Contoh: https://www.instagram.com/hannahasaindonesia/"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Deskripsi (Opsional)</label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Tambahkan penjelasan singkat kemitraan..."
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all resize-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Order -->
            <div>
              <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Order Tampil</label>
              <input
                type="number"
                v-model="form.order"
                min="0"
                class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
              />
            </div>

            <!-- Active Status -->
            <div class="flex flex-col justify-end">
              <label class="flex items-center gap-2 cursor-pointer py-2">
                <input
                  type="checkbox"
                  v-model="form.is_active"
                  class="w-4 h-4 rounded border-2 border-[#04000D] text-[#04000D] focus:ring-0 cursor-pointer"
                />
                <span class="text-xs font-bold text-on-surface">Tampilkan di Landing Page</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t-2 border-[#04000D] bg-slate-50 flex items-center justify-end gap-2">
          <button
            @click="showModal = false"
            class="px-4 py-2 border-2 border-slate-300 font-bold hover:bg-slate-100 rounded-xl transition-all"
          >
            Batal
          </button>
          <button
            @click="handleSave"
            :disabled="saving"
            class="flex items-center gap-1.5 px-4 py-2 bg-[#04000D] hover:bg-[#04000D]/90 text-[#DCEEB1] font-bold rounded-xl transition-all shadow-[2px_2px_0px_0px_#FF3D8B]"
          >
            <span v-if="saving" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
            <Save class="w-4 h-4" v-else />
            Simpan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
