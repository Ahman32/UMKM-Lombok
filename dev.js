const { spawn } = require('child_process');
const path = require('path');

const rootDir = __dirname;
const processes = [];
let shuttingDown = false;

function start(command, args, name, options = {}) {
  const child = spawn(command, args, {
    cwd: rootDir,
    stdio: 'inherit',
    shell: false,
    env: {
      ...process.env,
      ...options.env,
    },
  });

  child.on('error', (error) => {
    console.error(`[${name}] gagal dijalankan:`, error.message);
    shutdown(1);
  });

  child.on('exit', (code, signal) => {
    if (shuttingDown) {
      return;
    }

    if (code !== 0) {
      console.error(`[${name}] berhenti dengan kode ${code ?? 'null'}${signal ? ` (${signal})` : ''}.`);
      shutdown(code ?? 1);
    }
  });

  processes.push(child);
  return child;
}

function shutdown(exitCode = 0) {
  if (shuttingDown) {
    return;
  }

  shuttingDown = true;

  for (const child of processes) {
    if (!child.killed) {
      try {
        child.kill();
      } catch (error) {
        // Abaikan error saat menghentikan proses.
      }
    }
  }

  process.exit(exitCode);
}

process.on('SIGINT', () => shutdown(0));
process.on('SIGTERM', () => shutdown(0));

const nodeExecutable = process.execPath;
start(nodeExecutable, [path.join('backend', 'src', 'server.js')], 'backend');
start('php', ['-S', '127.0.0.1:8000', '-t', 'public'], 'php');

console.log('Frontend tersedia di http://127.0.0.1:8000');
console.log('Backend tersedia di http://127.0.0.1:3000');
