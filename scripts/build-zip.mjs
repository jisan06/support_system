import { createWriteStream, readFileSync, mkdirSync, existsSync, readdirSync, rmSync } from 'fs';
import { resolve, join, relative, dirname, basename } from 'path';
import { fileURLToPath } from 'url';
import archiver from 'archiver';

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, '..');
const packageRoot = resolve(projectRoot, '..');
const config = JSON.parse(readFileSync(resolve(projectRoot, 'zip.config.json'), 'utf-8'));

const outputDir = resolve(packageRoot, 'build');
const zipName = `${config.name}.zip`;
const outputPath = resolve(outputDir, zipName);

// Clean and recreate build directory
if (existsSync(outputDir)) {
    rmSync(outputDir, { recursive: true, force: true });
}
mkdirSync(outputDir, { recursive: true });

// ── Filtering logic ──────────────────────────────────────────────

function shouldExclude(relPath, isDir) {
    // Directory excludes — skip entire tree
    if (config.excludeDirs) {
        for (const dir of config.excludeDirs) {
            if (relPath === dir || relPath.startsWith(dir + '/')) return true;
        }
    }

    // File excludes — exact match
    if (!isDir && config.excludeFiles) {
        if (config.excludeFiles.includes(relPath)) return true;
    }

    // Clean dirs — keep only .gitignore inside these directories
    if (!isDir && config.cleanDirs) {
        for (const dir of config.cleanDirs) {
            if (relPath.startsWith(dir + '/') && basename(relPath) !== '.gitignore') {
                return true;
            }
        }
    }

    return false;
}

// ── Recursive file walker ────────────────────────────────────────

let fileCount = 0;

function addDirToArchive(archive, dir, prefix) {
    let entries;
    try {
        entries = readdirSync(dir, { withFileTypes: true });
    } catch {
        return;
    }

    for (const entry of entries) {
        const fullPath = join(dir, entry.name);
        const relPath = relative(projectRoot, fullPath).replace(/\\/g, '/');

        if (entry.isSymbolicLink()) continue;

        if (entry.isDirectory()) {
            if (!shouldExclude(relPath, true)) {
                addDirToArchive(archive, fullPath, prefix);
            }
        } else if (entry.isFile()) {
            if (!shouldExclude(relPath, false)) {
                archive.file(fullPath, { name: `${prefix}/${relPath}` });
                fileCount++;
            }
        }
    }
}

// ── Build zip ────────────────────────────────────────────────────

console.log(`\nPackaging ${config.name}...\n`);

const output = createWriteStream(outputPath);
const archive = archiver('zip', { zlib: { level: 9 } });

archive.on('warning', (err) => {
    if (err.code !== 'ENOENT') throw err;
});
archive.on('error', (err) => { throw err; });

output.on('close', () => {
    const sizeMB = (archive.pointer() / 1024 / 1024).toFixed(2);
    console.log(`  Files:  ${fileCount}`);
    console.log(`  Output: build/${zipName} (${sizeMB} MB)\n`);
});

archive.pipe(output);

// 1. Add documentation folder
const docsDir = resolve(packageRoot, 'documentation');
if (existsSync(docsDir)) {
    archive.directory(docsDir, `${config.name}/documentation`);
    console.log('  + documentation/');
}

// 2. Add changelog
const changelogPath = resolve(packageRoot, 'changelog.txt');
if (existsSync(changelogPath)) {
    archive.file(changelogPath, { name: `${config.name}/changelog.txt` });
    console.log('  + changelog.txt');
}

// 3. Add project files with filtering
console.log('  + project/ (filtered)');
addDirToArchive(archive, projectRoot, `${config.name}/project`);

await archive.finalize();