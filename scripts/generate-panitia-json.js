import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Target directory to scan (relative to project root)
const targetDir = path.resolve(__dirname, '../public/foto_kepanitiaan_ifest2026');
const outputFile = path.resolve(__dirname, '../src/data/panitiaFiles.json');

// Ensure target directory exists
if (!fs.existsSync(targetDir)) {
  console.error(`Error: Directory not found: ${targetDir}`);
  process.exit(1);
}

function getFilesRecursively(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat && stat.isDirectory()) {
      results = results.concat(getFilesRecursively(filePath));
    } else {
      // Only include images
      const ext = path.extname(file).toLowerCase();
      if (['.webp', '.jpg', '.jpeg', '.png'].includes(ext)) {
        results.push(filePath);
      }
    }
  });
  return results;
}

try {
  const absolutePaths = getFilesRecursively(targetDir);
  
  // Convert absolute paths to relative paths starting with 'foto_kepanitiaan_ifest2026'
  const relativePaths = absolutePaths.map(absPath => {
    // Relative to the 'public' directory
    const rel = path.relative(path.resolve(__dirname, '../public'), absPath);
    // Replace backslashes with forward slashes for URL compatibility
    return rel.replace(/\\/g, '/');
  });

  // Sort paths to keep the JSON output stable
  relativePaths.sort();

  // Ensure output folder exists
  const outputDir = path.dirname(outputFile);
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  // Write JSON
  fs.writeFileSync(outputFile, JSON.stringify(relativePaths, null, 2), 'utf-8');
  console.log(`Successfully generated ${outputFile} with ${relativePaths.length} image paths.`);
} catch (error) {
  console.error('Failed to generate panitia JSON:', error);
  process.exit(1);
}
