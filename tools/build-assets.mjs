import { copyFile, mkdir, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const addonRoot = path.resolve(__dirname, '..');

async function resolveExistingPath(candidates) {
  for (const candidate of candidates) {
    try {
      await stat(candidate);
      return candidate;
    } catch {
      // ignore missing file and continue
    }
  }

  throw new Error(`Missing source file. Checked: ${candidates.join(', ')}`);
}

async function main() {
  const distRoot = path.join(addonRoot, 'node_modules', 'cropperjs', 'dist');
  const vendorTargetDir = path.join(addonRoot, 'assets', 'vendor', 'cropper');

  await mkdir(vendorTargetDir, { recursive: true });

  const jsSource = await resolveExistingPath([
    path.join(distRoot, 'cropper.min.js'),
    path.join(distRoot, 'cropper.js'),
  ]);

  await copyFile(jsSource, path.join(vendorTargetDir, 'cropper.min.js'));
  await writeFile(
    path.join(vendorTargetDir, 'cropper.css'),
    '/* Cropper.js 2 injects its component styles at runtime. */\n',
  );

  process.stdout.write('Cropper vendor assets updated in assets/vendor/cropper.\n');
}

main().catch((error) => {
  process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
  process.exitCode = 1;
});