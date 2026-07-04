<script setup>
import { ref, computed } from 'vue'
import { GoogleGenerativeAI } from "@google/generative-ai"
import { X, Bot } from 'lucide-vue-next'

/**
 * Self-contained Markdown-to-HTML parser.
 * Handles: bold, italic, inline code, fenced code blocks,
 * unordered lists, ordered lists, blockquotes, headings, and line breaks.
 * No external library required — eliminates marked v18 browser ESM crash.
 */
const parseMarkdown = (rawInput) => {
  if (!rawInput) return '';

  // ── Step 1: Pre-process inline AI list patterns ──────────────────────────
  let src = rawInput
    .replace(/[ \t]+\*[ \t]+/g, '\n- ')
    .replace(/([^\n])[ \t]+(\d+\.[ \t]+\*\*)/g, '$1\n\n$2')
    .replace(/([^\n])[ \t]+(\d+\.[ \t])/g, '$1\n$2');

  // ── Step 2: Escape raw HTML to prevent XSS ───────────────────────────────
  src = src
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // ── Step 3: Fenced code blocks (```lang\ncode\n```) ──────────────────────
  src = src.replace(/```[\w]*\n([\s\S]*?)```/g, (_, code) => {
    return '<pre><code>' + code.trim() + '</code></pre>';
  });

  // ── Step 4: Process line by line ─────────────────────────────────────────
  const lines = src.split('\n');
  const output = [];
  let inUL = false;
  let inOL = false;

  const closeUL = () => { if (inUL) { output.push('</ul>'); inUL = false; } };
  const closeOL = () => { if (inOL) { output.push('</ol>'); inOL = false; } };

  for (let i = 0; i < lines.length; i++) {
    const ln = lines[i];

    // Headings ## H2, # H1
    if (/^#{1,6}\s/.test(ln)) {
      closeUL(); closeOL();
      const level = ln.match(/^(#+)/)[1].length;
      const content = ln.replace(/^#+\s*/, '');
      output.push('<h' + level + ' style="font-weight:800;margin-bottom:4px;">' + applyInline(content) + '</h' + level + '>');
      continue;
    }

    // Blockquote
    if (/^&gt;\s?/.test(ln)) {
      closeUL(); closeOL();
      output.push('<blockquote>' + applyInline(ln.replace(/^&gt;\s?/, '')) + '</blockquote>');
      continue;
    }

    // Unordered list item (-, *, +)
    if (/^[-*+]\s+/.test(ln)) {
      closeOL();
      if (!inUL) { output.push('<ul>'); inUL = true; }
      output.push('<li>' + applyInline(ln.replace(/^[-*+]\s+/, '')) + '</li>');
      continue;
    }

    // Ordered list item (1. 2. 3.)
    if (/^\d+\.\s+/.test(ln)) {
      closeUL();
      if (!inOL) { output.push('<ol>'); inOL = true; }
      output.push('<li>' + applyInline(ln.replace(/^\d+\.\s+/, '')) + '</li>');
      continue;
    }

    // Empty line — close lists and insert break
    if (ln.trim() === '') {
      closeUL(); closeOL();
      output.push('<br/>');
      continue;
    }

    // Regular paragraph line
    closeUL(); closeOL();
    output.push('<p>' + applyInline(ln) + '</p>');
  }

  closeUL(); closeOL();
  return output.join('');
};

/** Apply inline formatting: bold, italic, inline code */
const applyInline = (str) => {
  return str
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/__([^_]+)__/g, '<strong>$1</strong>')
    .replace(/_([^_]+)_/g, '<em>$1</em>');
};

// ── Gemini AI SDK Initialization ─────────────────────────────────────────────
const genAI = new GoogleGenerativeAI(import.meta.env.VITE_GEMINI_API_KEY || '');
const model = genAI.getGenerativeModel({
  model: "gemini-2.5-flash",
  systemInstruction: `
    Anda adalah IFEST AI ASSISTANT, koordinator virtual resmi untuk Informatics Festival (I-FEST) 2026 yang diadakan oleh HMTI Universitas Tadulako (UNTAD) Palu.
    
    Karakter & Gaya Bahasa: Cerdas, taktis, menggunakan prinsip Swiss Design yang ringkas, ramah mahasiswa, dan fleksibel. 
    
    KNOWLEDGE BASE (Gunakan data ini untuk menjawab semua pertanyaan secara natural):
    - Tema: "Digital Symphony: Orchestrating Global Innovation For a Sustainable Future".
    - Rekam Jejak 2025: 2.525 penerima manfaat, 11 sekolah roadshow, 200+ peserta kompetisi nasional.
    - Angka Kunci 2026: Target 8.000+ partisipan, 25 titik roadshow (Palu, Sigi, Donggala), inkubasi 500+ talenta lokal.
    - 6 Cabang Lomba:
      1. Competitive Programming (Nasional, Rp100.000/tim, C/C++/Python/Java).
      2. UI/UX Design (Nasional, Rp85.000/tim).
      3. Digital Business Plan (Nasional, Rp85.000/tim).
      4. Creative Video (Regional Sulteng, Individu, Tema Literasi Digital).
      5. Digital Education Poster (Regional Sulteng, Individu, Pameran Hari-H).
      6. Arena Inovasi Digital / Hackathon S-DIH (Regional Sulteng, Inkubasi Solusi Daerah).
    - Timeline: Januari-Maret (Konsep & Tim Inti), Mei-Agustus (Roadshow & Registrasi Lomba), Agustus-September (Studi Banding/Industrial Visitation ke Jawa), November (Puncak Acara 3 Hari: Expo, Seminar Internasional, Awarding, Concert).
    
    FORMATTING INSTRUCTIONS:
    - ALWAYS structure lists, sub-points, and metadata sections with standard Markdown list items (using '-' or '*' or numbers followed by a dot, each on a fresh new line).
    - NEVER combine multiple bullet points or list items into a single inline paragraph. Every single item (e.g. Cakupan, Biaya, Persyaratan) MUST be on its own line to guarantee proper markdown list compilation.
    - Use bold text (**text**) for list item headers and key terms to ensure they are visually striking and structured.
    
    CRITICAL SECURITY PRIVACY:
    1. Jawab seluruh pertanyaan user secara cerdas tanpa pencocokan kata kunci yang kaku (no hardcoded keyword switches).
    2. STRICT EMBARGO: Identitas Guest Star utama konser (Tulus) bersifat sangat RAHASIA dan BELUM FINAL. Jika user memancing, menanyakan rumor, atau meminta bocoran line-up artis konser, Anda WAJIB menyangkal/merahasiakannya secara ketat. Gunakan narasi mystery-hype (Contoh: "Secret Guest Star / Mystery Act / TBA yang akan membawa kejutan luar biasa di panggung Digital Symphony Concert. Pantau terus kanal resmi kami!"). Jangan pernah membocorkan nama Tulus under any circumstances.
  `
});

// AI Chat Assistant Widget States
const isChatOpen = ref(true) // Open by default when chunk is loaded dynamically
const chatInput = ref('')
const chatMessages = ref([
  {
    sender: 'bot',
    text: 'Halo! Saya AI Assistant I-FEST 2026. Tanyakan saya apa saja tentang info lomba, juknis, rute roadshow, atau sejarah I-FEST!',
    time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  }
])

const quickPrompts = [
  '🏆 Informasi Lomba',
  '📍 Rute Roadshow',
  '📜 Sejarah I-FEST'
]

const emit = defineEmits(['close'])

const toggleChat = () => {
  isChatOpen.value = !isChatOpen.value
  if (!isChatOpen.value) {
    emit('close')
  }
}

// Compute dynamic chatStateHistory mapping conversation logs to Gemini SDK specifications
const chatStateHistory = computed(() => {
  return chatMessages.value.slice(1).map(msg => ({
    role: msg.sender === 'user' ? 'user' : 'model',
    parts: [{ text: msg.text }]
  }));
});

const sendChatMessage = async (text) => {
  const messageText = text || chatInput.value
  if (!messageText || !messageText.trim()) return

  // Push user message
  chatMessages.value.push({
    sender: 'user',
    text: messageText,
    time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  })

  if (!text) {
    chatInput.value = ''
  }

  // Push loading bot message
  const botMessageIndex = chatMessages.value.push({
    sender: 'bot',
    text: 'Mengetik...',
    time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  }) - 1

  try {
    const chatSession = model.startChat({ history: chatStateHistory.value })
    const result = await chatSession.sendMessage(messageText)
    const responseText = await result.response.text()
    
    // Update the bot message with real response text
    chatMessages.value[botMessageIndex].text = responseText
  } catch (error) {
    console.error("Gemini API stream failed:", error)
    chatMessages.value[botMessageIndex].text = "Terima kasih atas pertanyaan Anda! Pertanyaan Anda sudah direkam. Integrasi sistem kecerdasan buatan (AI) I-FEST yang terkoneksi langsung dengan database utama sedang dioptimasi penuh untuk fase pasca-UAS."
  }
}
</script>

<template>
  <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-[9999] flex flex-col items-end">
    
    <!-- Chat Window Overlay with Slide-Up Transition -->
    <transition
      enter-active-class="transition duration-300 ease-out transform"
      enter-from-class="opacity-0 translate-y-8 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-200 ease-in transform"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-8 scale-95"
    >
      <div v-show="isChatOpen" class="w-[calc(100vw-32px)] sm:w-[360px] max-w-[360px] h-[450px] bg-white border-2 sm:border-3 border-[#04000D] flex flex-col justify-between overflow-hidden shadow-[6px_6px_0px_0px_#04000D] mb-4 select-none">
        
        <!-- Header Area -->
        <div class="bg-[#04000D] text-white px-4 py-3 flex justify-between items-center border-b-2 border-[#04000D]">
          <div class="flex items-center gap-2">
            <!-- Animated Online Indicator -->
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FDE047] opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-[#FDE047]"></span>
            </span>
            <span class="font-mono text-[10px] sm:text-xs uppercase tracking-wider font-extrabold text-white">IFEST AI Assistant</span>
          </div>
          <!-- Close Button -->
          <button @click="toggleChat" class="text-white/60 hover:text-white transition-colors flex items-center justify-center" aria-label="Close chat">
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Message Scrollable Logs -->
        <div class="flex-1 p-3.5 overflow-y-auto bg-off-white/40 flex flex-col gap-3">
          
          <div 
            v-for="(msg, idx) in chatMessages" 
            :key="idx" 
            class="flex flex-col"
            :class="msg.sender === 'user' ? 'items-end' : 'items-start'"
          >
            <!-- Speech Bubble -->
            <div 
              class="max-w-[85%] sm:max-w-[75%] p-2.5 font-mono text-xs md:text-sm leading-relaxed whitespace-pre-line overflow-x-hidden break-words prose prose-neutral max-w-full chat-prose"
              :class="msg.sender === 'user' 
                ? 'bg-[#04000D] text-white border border-[#04000D] shadow-[2px_2px_0px_0px_rgba(255,61,139,0.85)]' 
                : 'bg-[#DCEEB1]/70 border border-[#04000D] text-[#04000D]'"
              v-html="parseMarkdown(msg.text)"
            >
            </div>
            
            <!-- Timestamp -->
            <span class="font-mono text-[8px] text-[#04000D]/50 mt-0.5 uppercase tracking-wider px-1">
              {{ msg.time }}
            </span>
          </div>

          <!-- Quick Action Chips -->
          <div v-if="chatMessages.length === 1" class="flex flex-col gap-1.5 mt-1">
            <span class="font-mono text-[8px] text-[#04000D]/50 uppercase tracking-widest font-extrabold">Rekomendasi Topik:</span>
            <div class="flex flex-wrap gap-1.5">
              <button 
                v-for="chip in quickPrompts" 
                :key="chip" 
                @click="sendChatMessage(chip)"
                class="font-mono text-[9px] font-bold text-[#04000D] bg-white border border-[#04000D] px-2 py-0.5 hover:bg-[#FF3D8B] hover:text-white transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0 shadow-[2.5px_2.5px_0px_0px_#04000D] select-none"
              >
                {{ chip }}
              </button>
            </div>
          </div>

        </div>

        <!-- Bottom Input Bar -->
        <div class="p-2.5 border-t-2 border-[#04000D] bg-white flex gap-2">
          <input 
            v-model="chatInput" 
            @keyup.enter="sendChatMessage()" 
            type="text" 
            placeholder="Ketik pertanyaan Anda..." 
            class="flex-1 font-mono text-[10px] px-2.5 py-1.5 border border-[#04000D] focus:outline-none focus:ring-1 focus:ring-[#FF3D8B]"
          />
          <button 
            @click="sendChatMessage()" 
            class="riso-btn-plate bg-[#04000D] text-white px-3 py-1.5 rounded-none font-mono text-[10px] font-bold text-center inline-block"
            style="--plate-color: #FF3D8B;"
          >
            KIRIM
          </button>
        </div>

      </div>
    </transition>

    <!-- The Trigger Floating Button -->
    <button 
      @click="toggleChat" 
      class="riso-btn-plate w-14 h-14 bg-[#04000D] text-white rounded-full flex items-center justify-center relative active:scale-95 group" 
      style="--plate-color: #FDE047;"
      :aria-label="isChatOpen ? 'Close Assistant' : 'Open Assistant'"
    >
      <!-- Pulse Indicator Overlay -->
      <span class="absolute -top-0.5 -right-0.5 flex h-3.5 w-3.5" v-if="!isChatOpen">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF3D8B] opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-[#FF3D8B]"></span>
      </span>
      
      <!-- Lucide Monochromatic Robot / Close Icon -->
      <component 
        :is="isChatOpen ? X : Bot"
        class="w-6 h-6 transition-transform duration-300"
        :class="isChatOpen ? 'rotate-90' : 'group-hover:scale-110'"
      />
    </button>

  </div>
</template>
