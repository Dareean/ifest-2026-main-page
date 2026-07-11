<script setup>
import { computed } from 'vue'
import { CheckCircle, Circle, Loader } from 'lucide-vue-next'

const props = defineProps({
  reg: { type: Object, default: null },
  lomba: { type: Object, default: null },
  teamAcceptedCount: { type: Number, default: 0 },
})

const steps = computed(() => {
  const s = []
  const reg = props.reg
  const lomba = props.lomba

  if (!reg || !lomba) return s

  const getMaxMembers = (req) => {
    if (!req) return 3
    if (req.toLowerCase().includes('individu')) return 1
    const matches = req.match(/(\d+)\s*-\s*(\d+)/)
    if (matches) return parseInt(matches[2], 10)
    const matchesSingle = req.match(/(\d+)/)
    if (matchesSingle) return parseInt(matchesSingle[1], 10)
    return 3
  }
  const isTeam = (getMaxMembers(lomba.team_requirements) ?? 1) > 1
  const isFree = lomba.fee && lomba.fee.toLowerCase() === 'gratis'
  const hasSubmission = !!reg.submission
  const paymentVerified = reg.payment_status === 'verified'
  const statusVerified = reg.status === 'verified'
  const maxMembers = getMaxMembers(lomba.team_requirements)
  const teamFull = 1 + props.teamAcceptedCount >= maxMembers

  // Flag-based gate conditions
  const anggotaDone = !isTeam || teamFull
  const bayarDone = isFree || paymentVerified
  const verifDone = statusVerified

  // 1. Pendaftaran
  s.push({ key: 'daftar', label: 'Pendaftaran', desc: 'Registrasi lomba berhasil', state: 'completed' })

  // 2. Anggota Tim (only for team)
  if (isTeam) {
    s.push({
      key: 'anggota',
      label: 'Anggota Tim',
      desc: `${1 + props.teamAcceptedCount}/${maxMembers} anggota`,
      state: anggotaDone ? 'completed' : 'current',
    })
  }

  // 3. Pembayaran (skip if free)
  if (!isFree) {
    let payState = 'pending'
    if (bayarDone) {
      payState = 'completed'
    } else if (anggotaDone) {
      payState = 'current'
    }
    s.push({ key: 'bayar', label: 'Pembayaran', desc: 'Upload & verifikasi bukti bayar', state: payState })
  }

  // 4. Verifikasi
  let verifState = 'pending'
  if (verifDone) {
    verifState = 'completed'
  } else if (bayarDone) {
    verifState = 'current'
  }
  s.push({
    key: 'verif',
    label: isTeam ? 'Verifikasi Tim' : 'Verifikasi',
    desc: isTeam ? 'Tim diverifikasi admin' : 'Pendaftaran diverifikasi admin',
    state: verifState,
  })

  // 5. Kumpul Karya
  let karyaState = 'pending'
  if (hasSubmission) {
    karyaState = 'completed'
  } else if (verifDone) {
    karyaState = 'current'
  }
  s.push({ key: 'karya', label: 'Pengumpulan Karya', desc: hasSubmission ? 'Karya sudah dikumpulkan' : 'Kumpulkan link karya', state: karyaState })

  return s
})
</script>

<template>
  <div v-if="steps.length" class="space-y-1">
    <div
      v-for="(step, i) in steps"
      :key="step.key"
      class="flex items-start gap-3 py-2.5"
    >
      <div class="flex flex-col items-center">
        <div
          class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-all"
          :class="{
            'bg-[#DCEEB1] text-on-surface': step.state === 'completed',
            'bg-[#04000D] text-[#DCEEB1]': step.state === 'current',
            'bg-slate-100 text-on-surface-variant/40 border border-slate-200': step.state === 'pending',
          }"
        >
          <CheckCircle v-if="step.state === 'completed'" class="w-3.5 h-3.5" />
          <Loader v-else-if="step.state === 'current'" class="w-3 h-3 animate-spin" />
          <span v-else>{{ i + 1 }}</span>
        </div>
        <div
          v-if="i < steps.length - 1"
          class="w-0.5 h-6 mt-1 rounded-full"
          :class="{
            'bg-[#DCEEB1]': step.state === 'completed',
            'bg-slate-200': step.state !== 'completed',
          }"
        />
      </div>
      <div class="min-w-0 pt-0.5" :class="step.state === 'pending' ? 'opacity-40' : ''">
        <p
          class="text-xs font-bold"
          :class="{
            'text-on-surface': step.state === 'completed' || step.state === 'current',
            'text-on-surface-variant/60': step.state === 'pending',
          }"
        >
          {{ step.label }}
        </p>
        <p class="text-[10px] text-on-surface-variant/50 mt-0.5">{{ step.desc }}</p>
      </div>
    </div>
  </div>
</template>
