const sharp = require('sharp')
const path = require('path')
const fs = require('fs')

const folders = [
  'src/assets/foto_kepanitiaan_ifest2026',
  'src/assets/visual_assets',
  'src/assets/medpart',
  'src/assets/sponsor-strategic_partner',
  'src/assets/logo_utama',
  'src/assets/dokumentasi_ifest2025',
]

async function convertPngToWebP(filePath) {
  const webpPath = filePath.replace(/\.png$/i, '.webp')
  if (fs.existsSync(webpPath)) return false

  try {
    const img = sharp(filePath)
    const meta = await img.metadata()

    await img
      .resize({ width: Math.min(meta.width, 1920), withoutEnlargement: true })
      .webp({ quality: 80, effort: 4 })
      .toFile(webpPath)

    const oldSize = fs.statSync(filePath).size
    const newSize = fs.statSync(webpPath).size
    const pct = ((1 - newSize / oldSize) * 100).toFixed(1)
    console.log(`  ${path.basename(filePath)}: ${(oldSize / 1e6).toFixed(1)}MB → ${(newSize / 1e6).toFixed(1)}MB (${pct}% lebih kecil)`)

    fs.unlinkSync(filePath)
    return true
  } catch (err) {
    console.error(`  GAGAL ${filePath}: ${err.message}`)
    return false
  }
}

async function main() {
  let totalSaved = 0
  let totalOld = 0

  for (const folder of folders) {
    const fullPath = path.resolve(folder)
    if (!fs.existsSync(fullPath)) {
      console.log(`\n[${folder}] — tidak ditemukan, skip`)
      continue
    }

    const pngFiles = fs.readdirSync(fullPath, { recursive: true })
      .filter(f => f.toLowerCase().endsWith('.png'))
      .map(f => path.join(fullPath, f))

    if (pngFiles.length === 0) {
      console.log(`\n[${folder}] — tidak ada PNG`)
      continue
    }

    console.log(`\n[${folder}] — ${pngFiles.length} file PNG`)
    for (const file of pngFiles) {
      const oldSize = fs.statSync(file).size
      const converted = await convertPngToWebP(file)
      if (converted) {
        totalSaved += oldSize - fs.statSync(file.replace(/\.png$/i, '.webp')).size
        totalOld += oldSize
      }
    }
  }

  const totalPct = totalOld > 0 ? ((1 - (totalOld - totalSaved) / totalOld) * 100).toFixed(1) : 0
  console.log(`\n=== Selesai! ===`)
  console.log(`Total hemat: ${(totalSaved / 1e6).toFixed(1)}MB dari ${(totalOld / 1e6).toFixed(1)}MB (${totalPct}%)`)
}

main().catch(console.error)
