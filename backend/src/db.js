const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const { DatabaseSync } = require('node:sqlite');

const ROOT_DIR = path.join(__dirname, '..');
const DATA_DIR = path.join(ROOT_DIR, 'data');
const UPLOAD_DIR = path.join(ROOT_DIR, 'uploads');
const DB_PATH = path.join(DATA_DIR, 'webumkm.sqlite');
const SCHEMA_PATH = path.join(DATA_DIR, 'schema.sql');
const API_BASE_URL = (process.env.API_BASE_URL || 'http://127.0.0.1:3000').replace(/\/$/, '');

let db = null;

function nowIso() {
  return new Date().toISOString();
}

function ensureStorage() {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
}

function getDb() {
  if (!db) {
    initDatabase();
  }

  return db;
}

function initDatabase() {
  if (db) {
    return db;
  }

  ensureStorage();
  db = new DatabaseSync(DB_PATH);
  db.exec('PRAGMA journal_mode = WAL;');
  db.exec('PRAGMA foreign_keys = ON;');

  const schema = fs.readFileSync(SCHEMA_PATH, 'utf8');
  db.exec(schema);
  seedData();

  return db;
}

function createError(status, message, details = null) {
  const error = new Error(message);
  error.status = status;
  error.details = details;
  return error;
}

function normalizeText(value) {
  return String(value ?? '').trim();
}

function normalizeUsername(value) {
  return normalizeText(value).toLowerCase();
}

function now() {
  return nowIso();
}

function hashPassword(password) {
  const iterations = 120000;
  const salt = crypto.randomBytes(16).toString('hex');
  const hash = crypto
    .pbkdf2Sync(String(password), salt, iterations, 64, 'sha512')
    .toString('hex');

  return `pbkdf2$${iterations}$${salt}$${hash}`;
}

function verifyPassword(password, stored) {
  if (!stored || typeof stored !== 'string') {
    return false;
  }

  const parts = stored.split('$');
  if (parts.length !== 4 || parts[0] !== 'pbkdf2') {
    return false;
  }

  const iterations = Number(parts[1]);
  const salt = parts[2];
  const expected = parts[3];

  if (!Number.isFinite(iterations) || !salt || !expected) {
    return false;
  }

  const actual = crypto
    .pbkdf2Sync(String(password), salt, iterations, 64, 'sha512')
    .toString('hex');

  const actualBuffer = Buffer.from(actual, 'hex');
  const expectedBuffer = Buffer.from(expected, 'hex');

  if (actualBuffer.length !== expectedBuffer.length) {
    return false;
  }

  return crypto.timingSafeEqual(actualBuffer, expectedBuffer);
}

function sanitizeUser(row) {
  if (!row) {
    return null;
  }

  return {
    id: Number(row.id),
    name: row.name,
    username: row.username,
    role: row.role,
    created_at: row.created_at,
    updated_at: row.updated_at,
  };
}

function uploadUrl(relativePath) {
  if (!relativePath) {
    return null;
  }

  const normalized = String(relativePath).replace(/^\/+/, '');
  return `${API_BASE_URL}/uploads/${normalized}`;
}

function sanitizeUmkm(row) {
  if (!row) {
    return null;
  }

  return {
    id: Number(row.id),
    owner_id: Number(row.owner_id),
    owner_name: row.owner_name || null,
    owner_username: row.owner_username || null,
    reviewer_name: row.reviewer_name || null,
    nama_umkm: row.nama_umkm,
    kategori: row.kategori,
    deskripsi: row.deskripsi,
    lokasi: row.lokasi,
    alamat_lengkap: row.alamat_lengkap,
    telepon: row.telepon || '',
    whatsapp: row.whatsapp || '',
    email: row.email || '',
    website: row.website || '',
    jam_operasional: row.jam_operasional || '',
    foto_path: row.foto_path || null,
    foto_url: row.foto_path ? uploadUrl(row.foto_path) : null,
    status: row.status,
    catatan_admin: row.catatan_admin || null,
    reviewed_by: row.reviewed_by ? Number(row.reviewed_by) : null,
    reviewed_at: row.reviewed_at || null,
    created_at: row.created_at,
    updated_at: row.updated_at,
  };
}

function findUserRowByUsername(username) {
  return getDb().prepare('SELECT * FROM users WHERE username = ?').get(normalizeUsername(username));
}

function findUserRowById(id) {
  return getDb().prepare('SELECT * FROM users WHERE id = ?').get(id);
}

function findUserByUsername(username) {
  return sanitizeUser(findUserRowByUsername(username));
}

function findUserById(id) {
  return sanitizeUser(findUserRowById(id));
}

function authenticateUser(username, password) {
  const row = findUserRowByUsername(username);
  if (!row || !verifyPassword(password, row.password_hash)) {
    return null;
  }

  return sanitizeUser(row);
}

function createUser({ name, username, password, role = 'owner' }) {
  const cleanName = normalizeText(name);
  const cleanUsername = normalizeUsername(username);
  const cleanPassword = String(password ?? '');
  const cleanRole = role === 'admin' ? 'admin' : 'owner';

  if (!cleanName) {
    throw createError(422, 'Nama wajib diisi.', { field: 'name' });
  }

  if (!cleanUsername) {
    throw createError(422, 'Username wajib diisi.', { field: 'username' });
  }

  if (cleanPassword.length < 8) {
    throw createError(422, 'Password minimal 8 karakter.', { field: 'password' });
  }

  const exists = findUserRowByUsername(cleanUsername);
  if (exists) {
    throw createError(409, 'Username sudah digunakan.', { field: 'username' });
  }

  const timestamp = now();
  const info = getDb()
    .prepare(
      `INSERT INTO users (name, username, password_hash, role, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?)`
    )
    .run(cleanName, cleanUsername, hashPassword(cleanPassword), cleanRole, timestamp, timestamp);

  return findUserById(info.lastInsertRowid);
}

function updateUserAccount(userId, payload = {}, keepToken = null) {
  const row = findUserRowById(userId);
  if (!row) {
    throw createError(404, 'Akun tidak ditemukan.');
  }

  const updates = [];
  const params = [];
  const cleanName = normalizeText(payload.name);
  const cleanCurrentPassword = String(payload.current_password ?? '');
  const cleanNewPassword = String(payload.new_password ?? '');

  if (cleanName) {
    updates.push('name = ?');
    params.push(cleanName);
  }

  if (cleanNewPassword) {
    if (cleanNewPassword.length < 8) {
      throw createError(422, 'Password baru minimal 8 karakter.', { field: 'new_password' });
    }

    if (!cleanCurrentPassword) {
      throw createError(422, 'Password saat ini wajib diisi untuk mengganti password.', { field: 'current_password' });
    }

    if (!verifyPassword(cleanCurrentPassword, row.password_hash)) {
      throw createError(422, 'Password saat ini salah.', { field: 'current_password' });
    }

    updates.push('password_hash = ?');
    params.push(hashPassword(cleanNewPassword));
  }

  if (!updates.length) {
    return sanitizeUser(row);
  }

  updates.push('updated_at = ?');
  params.push(now());
  params.push(userId);

  getDb()
    .prepare(`UPDATE users SET ${updates.join(', ')} WHERE id = ?`)
    .run(...params);

  if (cleanNewPassword) {
    if (keepToken) {
      getDb()
        .prepare('DELETE FROM sessions WHERE user_id = ? AND token <> ?')
        .run(userId, keepToken);
    } else {
      getDb()
        .prepare('DELETE FROM sessions WHERE user_id = ?')
        .run(userId);
    }
  }

  return findUserById(userId);
}

function createSessionForUser(userId, ttlHours = 24 * 7) {
  const token = crypto.randomBytes(32).toString('hex');
  const createdAt = now();
  const expiresAt = new Date(Date.now() + ttlHours * 60 * 60 * 1000).toISOString();

  getDb()
    .prepare(
      `INSERT INTO sessions (token, user_id, created_at, expires_at)
       VALUES (?, ?, ?, ?)`
    )
    .run(token, userId, createdAt, expiresAt);

  return {
    token,
    created_at: createdAt,
    expires_at: expiresAt,
  };
}

function revokeSession(token) {
  if (!token) {
    return;
  }

  getDb().prepare('DELETE FROM sessions WHERE token = ?').run(token);
}

function revokeAllUserSessions(userId, keepToken = null) {
  if (!keepToken) {
    getDb().prepare('DELETE FROM sessions WHERE user_id = ?').run(userId);
    return;
  }

  getDb()
    .prepare('DELETE FROM sessions WHERE user_id = ? AND token <> ?')
    .run(userId, keepToken);
}

function getSessionByToken(token) {
  if (!token) {
    return null;
  }

  const row = getDb()
    .prepare(
      `SELECT
        s.token,
        s.created_at,
        s.expires_at,
        s.revoked_at,
        u.id,
        u.name,
        u.username,
        u.role,
        u.created_at AS user_created_at,
        u.updated_at AS user_updated_at
      FROM sessions s
      INNER JOIN users u ON u.id = s.user_id
      WHERE s.token = ?`
    )
    .get(token);

  if (!row || row.revoked_at) {
    return null;
  }

  if (row.expires_at && new Date(row.expires_at).getTime() <= Date.now()) {
    revokeSession(token);
    return null;
  }

  return {
    token: row.token,
    created_at: row.created_at,
    expires_at: row.expires_at,
    user: sanitizeUser({
      id: row.id,
      name: row.name,
      username: row.username,
      role: row.role,
      created_at: row.user_created_at,
      updated_at: row.user_updated_at,
    }),
  };
}

function countUmkmByOwner(ownerId) {
  const row = getDb()
    .prepare('SELECT COUNT(*) AS total FROM umkm WHERE owner_id = ?')
    .get(ownerId);

  return Number(row?.total || 0);
}

function getOwnerUmkm(ownerId) {
  const row = getDb()
    .prepare(
      `SELECT
        m.*,
        owner.name AS owner_name,
        owner.username AS owner_username,
        reviewer.name AS reviewer_name
      FROM umkm m
      INNER JOIN users owner ON owner.id = m.owner_id
      LEFT JOIN users reviewer ON reviewer.id = m.reviewed_by
      WHERE m.owner_id = ?`
    )
    .get(ownerId);

  return sanitizeUmkm(row);
}

function listUmkmBase({ approvedOnly = false, status, q, lokasi, kategori } = {}) {
  const where = [];
  const params = [];

  if (approvedOnly) {
    where.push('m.status = ?');
    params.push('approved');
  } else if (status) {
    where.push('m.status = ?');
    params.push(String(status));
  }

  const query = normalizeText(q);
  if (query) {
    where.push(
      '(LOWER(m.nama_umkm) LIKE ? OR LOWER(m.lokasi) LIKE ? OR LOWER(m.kategori) LIKE ? OR LOWER(m.deskripsi) LIKE ?)'
    );
    const needle = `%${query.toLowerCase()}%`;
    params.push(needle, needle, needle, needle);
  }

  const place = normalizeText(lokasi);
  if (place) {
    where.push('LOWER(m.lokasi) LIKE ?');
    params.push(`%${place.toLowerCase()}%`);
  }

  const type = normalizeText(kategori);
  if (type) {
    where.push('LOWER(m.kategori) LIKE ?');
    params.push(`%${type.toLowerCase()}%`);
  }

  const sql = `
    SELECT
      m.*,
      owner.name AS owner_name,
      owner.username AS owner_username,
      reviewer.name AS reviewer_name
    FROM umkm m
    INNER JOIN users owner ON owner.id = m.owner_id
    LEFT JOIN users reviewer ON reviewer.id = m.reviewed_by
    ${where.length ? `WHERE ${where.join(' AND ')}` : ''}
    ORDER BY m.updated_at DESC, m.created_at DESC, m.id DESC
  `;

  return getDb()
    .prepare(sql)
    .all(...params)
    .map(sanitizeUmkm);
}

function listPublicUmkm(filters = {}) {
  return listUmkmBase({ ...filters, approvedOnly: true });
}

function getPublicUmkmById(id) {
  const row = getDb()
    .prepare(
      `SELECT
        m.*,
        owner.name AS owner_name,
        owner.username AS owner_username,
        reviewer.name AS reviewer_name
      FROM umkm m
      INNER JOIN users owner ON owner.id = m.owner_id
      LEFT JOIN users reviewer ON reviewer.id = m.reviewed_by
      WHERE m.id = ? AND m.status = 'approved'`
    )
    .get(id);

  return sanitizeUmkm(row);
}

function listOwnerUmkm(ownerId) {
  const umkm = getOwnerUmkm(ownerId);
  return umkm ? [umkm] : [];
}

function upsertOwnerUmkm(ownerId, payload = {}, photoPath = null) {
  const existing = getOwnerUmkm(ownerId);
  const clean = {
    nama_umkm: normalizeText(payload.nama_umkm),
    kategori: normalizeText(payload.kategori),
    deskripsi: normalizeText(payload.deskripsi),
    lokasi: normalizeText(payload.lokasi),
    alamat_lengkap: normalizeText(payload.alamat_lengkap),
    telepon: normalizeText(payload.telepon),
    whatsapp: normalizeText(payload.whatsapp),
    email: normalizeText(payload.email),
    website: normalizeText(payload.website),
    jam_operasional: normalizeText(payload.jam_operasional),
  };

  const requiredFields = [
    ['nama_umkm', 'Nama UMKM wajib diisi.'],
    ['kategori', 'Kategori wajib diisi.'],
    ['deskripsi', 'Deskripsi wajib diisi.'],
    ['lokasi', 'Lokasi wajib diisi.'],
    ['alamat_lengkap', 'Alamat lengkap wajib diisi.'],
  ];

  for (const [field, message] of requiredFields) {
    if (!clean[field]) {
      throw createError(422, message, { field });
    }
  }

  const timestamp = now();
  const nextPhotoPath = photoPath || (existing ? existing.foto_path : null);

  if (!existing) {
    getDb()
      .prepare(
        `INSERT INTO umkm (
          owner_id,
          nama_umkm,
          kategori,
          deskripsi,
          lokasi,
          alamat_lengkap,
          telepon,
          whatsapp,
          email,
          website,
          jam_operasional,
          foto_path,
          status,
          catatan_admin,
          reviewed_by,
          reviewed_at,
          created_at,
          updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, NULL, NULL, ?, ?)`
      )
      .run(
        ownerId,
        clean.nama_umkm,
        clean.kategori,
        clean.deskripsi,
        clean.lokasi,
        clean.alamat_lengkap,
        clean.telepon,
        clean.whatsapp,
        clean.email,
        clean.website,
        clean.jam_operasional,
        nextPhotoPath,
        timestamp,
        timestamp
      );

    return {
      is_new: true,
      umkm: getOwnerUmkm(ownerId),
    };
  }

  const nextStatus = 'pending';
  const reviewReset = null;

  getDb()
    .prepare(
      `UPDATE umkm SET
        nama_umkm = ?,
        kategori = ?,
        deskripsi = ?,
        lokasi = ?,
        alamat_lengkap = ?,
        telepon = ?,
        whatsapp = ?,
        email = ?,
        website = ?,
        jam_operasional = ?,
        foto_path = ?,
        status = ?,
        catatan_admin = ?,
        reviewed_by = ?,
        reviewed_at = ?,
        updated_at = ?
      WHERE owner_id = ?`
    )
    .run(
      clean.nama_umkm,
      clean.kategori,
      clean.deskripsi,
      clean.lokasi,
      clean.alamat_lengkap,
      clean.telepon,
      clean.whatsapp,
      clean.email,
      clean.website,
      clean.jam_operasional,
      nextPhotoPath,
      nextStatus,
      reviewReset,
      reviewReset,
      reviewReset,
      timestamp,
      ownerId
    );

  return {
    is_new: false,
    umkm: getOwnerUmkm(ownerId),
  };
}

function deleteFileIfExists(relativePath) {
  if (!relativePath) {
    return;
  }

  const absolutePath = path.join(UPLOAD_DIR, relativePath);
  if (fs.existsSync(absolutePath)) {
    fs.unlinkSync(absolutePath);
  }
}

function updateOwnerUmkmPhoto(ownerId, newPhotoPath) {
  const existing = getOwnerUmkm(ownerId);
  if (!existing || !newPhotoPath) {
    return;
  }

  if (existing.foto_path && existing.foto_path !== newPhotoPath) {
    deleteFileIfExists(existing.foto_path);
  }

  getDb()
    .prepare('UPDATE umkm SET foto_path = ?, updated_at = ? WHERE owner_id = ?')
    .run(newPhotoPath, now(), ownerId);
}

function updateUmkmStatus(umkmId, status, { adminId, catatan_admin = '' } = {}) {
  const cleanStatus = normalizeText(status).toLowerCase();
  if (!['approved', 'rejected', 'pending'].includes(cleanStatus)) {
    throw createError(422, 'Status UMKM tidak valid.');
  }

  const row = getDb().prepare('SELECT * FROM umkm WHERE id = ?').get(umkmId);
  if (!row) {
    throw createError(404, 'UMKM tidak ditemukan.');
  }

  const timestamp = now();

  getDb()
    .prepare(
      `UPDATE umkm SET
        status = ?,
        catatan_admin = ?,
        reviewed_by = ?,
        reviewed_at = ?,
        updated_at = ?
      WHERE id = ?`
    )
    .run(
      cleanStatus,
      normalizeText(catatan_admin) || null,
      adminId,
      timestamp,
      timestamp,
      umkmId
    );

  const updated = getDb()
    .prepare(
      `SELECT
        m.*,
        owner.name AS owner_name,
        owner.username AS owner_username,
        reviewer.name AS reviewer_name
      FROM umkm m
      INNER JOIN users owner ON owner.id = m.owner_id
      LEFT JOIN users reviewer ON reviewer.id = m.reviewed_by
      WHERE m.id = ?`
    )
    .get(umkmId);

  return sanitizeUmkm(updated);
}

function listAdminUmkm(filters = {}) {
  return listUmkmBase(filters);
}

function getDashboardStats() {
  const dbInstance = getDb();
  const users = dbInstance.prepare('SELECT COUNT(*) AS total FROM users').get();
  const owners = dbInstance.prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'owner'").get();
  const admins = dbInstance.prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'").get();
  const umkm = dbInstance.prepare('SELECT COUNT(*) AS total FROM umkm').get();
  const pending = dbInstance.prepare("SELECT COUNT(*) AS total FROM umkm WHERE status = 'pending'").get();
  const approved = dbInstance.prepare("SELECT COUNT(*) AS total FROM umkm WHERE status = 'approved'").get();
  const rejected = dbInstance.prepare("SELECT COUNT(*) AS total FROM umkm WHERE status = 'rejected'").get();

  return {
    total_users: Number(users.total || 0),
    total_owners: Number(owners.total || 0),
    total_admins: Number(admins.total || 0),
    total_umkm: Number(umkm.total || 0),
    pending_umkm: Number(pending.total || 0),
    approved_umkm: Number(approved.total || 0),
    rejected_umkm: Number(rejected.total || 0),
  };
}

function listUsersWithCounts() {
  return getDb()
    .prepare(
      `SELECT
        u.*,
        COUNT(m.id) AS umkm_count
      FROM users u
      LEFT JOIN umkm m ON m.owner_id = u.id
      GROUP BY u.id
      ORDER BY CASE u.role WHEN 'admin' THEN 0 ELSE 1 END, u.name ASC, u.id ASC`
    )
    .all()
    .map((row) => ({
      ...sanitizeUser(row),
      umkm_count: Number(row.umkm_count || 0),
    }));
}

function cleanupExpiredSessions() {
  getDb().prepare('DELETE FROM sessions WHERE expires_at <= ?').run(now());
}

function seedData() {
  const seeds = [
    {
      name: 'Administrator',
      username: 'admin',
      password: 'Admin123!',
      role: 'admin',
    },
    {
      name: 'Sari Lombok',
      username: 'sarilombok',
      password: 'Owner123!',
      role: 'owner',
      umkm: {
        nama_umkm: 'Sari Lombok Food',
        kategori: 'Kuliner',
        deskripsi: 'Pusat kuliner rumahan dengan rasa khas Lombok Timur dan menu harian untuk keluarga.',
        lokasi: 'Selong, Lombok Timur',
        alamat_lengkap: 'Jalan Raya Selong No. 12, Kecamatan Selong, Kabupaten Lombok Timur',
        telepon: '081234567801',
        whatsapp: '081234567801',
        email: 'sari@example.com',
        website: 'https://example.com/sari-lombok-food',
        jam_operasional: '08.00 - 21.00 WITA',
      },
    },
    {
      name: 'Aikmel Craft',
      username: 'aikmelcraft',
      password: 'Owner123!',
      role: 'owner',
      umkm: {
        nama_umkm: 'Aikmel Craft Center',
        kategori: 'Kerajinan',
        deskripsi: 'Pengrajin lokal yang menghadirkan produk anyaman dan souvenir khas Lombok Timur.',
        lokasi: 'Aikmel, Lombok Timur',
        alamat_lengkap: 'Dusun Timur, Kecamatan Aikmel, Kabupaten Lombok Timur',
        telepon: '081234567802',
        whatsapp: '081234567802',
        email: 'craft@example.com',
        website: 'https://example.com/aikmel-craft',
        jam_operasional: '09.00 - 17.00 WITA',
      },
    },
    {
      name: 'Kopi Tetebatu',
      username: 'kopitetebatu',
      password: 'Owner123!',
      role: 'owner',
      umkm: {
        nama_umkm: 'Kopi Tetebatu',
        kategori: 'Minuman',
        deskripsi: 'Kopi dan minuman lokal dengan suasana santai serta spot foto yang nyaman.',
        lokasi: 'Tetebatu, Lombok Timur',
        alamat_lengkap: 'Desa Tetebatu, Kecamatan Sikur, Kabupaten Lombok Timur',
        telepon: '081234567803',
        whatsapp: '081234567803',
        email: 'kopi@example.com',
        website: 'https://example.com/kopi-tetebatu',
        jam_operasional: '07.00 - 22.00 WITA',
      },
    },
  ];

  for (const seed of seeds) {
    const exists = findUserRowByUsername(seed.username);
    if (!exists) {
      getDb()
        .prepare(
          `INSERT INTO users (name, username, password_hash, role, created_at, updated_at)
           VALUES (?, ?, ?, ?, ?, ?)`
        )
        .run(seed.name, seed.username, hashPassword(seed.password), seed.role, now(), now());
    }

    if (seed.role !== 'owner' || !seed.umkm) {
      continue;
    }

    const owner = findUserRowByUsername(seed.username);
    if (!owner) {
      continue;
    }

    const umkmExists = getOwnerUmkm(owner.id);
    if (!umkmExists) {
      getDb()
        .prepare(
          `INSERT INTO umkm (
            owner_id,
            nama_umkm,
            kategori,
            deskripsi,
            lokasi,
            alamat_lengkap,
            telepon,
            whatsapp,
            email,
            website,
            jam_operasional,
            foto_path,
            status,
            catatan_admin,
            reviewed_by,
            reviewed_at,
            created_at,
            updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'approved', NULL, NULL, NULL, ?, ?)`
        )
        .run(
          owner.id,
          seed.umkm.nama_umkm,
          seed.umkm.kategori,
          seed.umkm.deskripsi,
          seed.umkm.lokasi,
          seed.umkm.alamat_lengkap,
          seed.umkm.telepon,
          seed.umkm.whatsapp,
          seed.umkm.email,
          seed.umkm.website,
          seed.umkm.jam_operasional,
          now(),
          now()
        );
    }
  }

  cleanupExpiredSessions();
}

module.exports = {
  initDatabase,
  getDb,
  createError,
  now,
  hashPassword,
  verifyPassword,
  sanitizeUser,
  sanitizeUmkm,
  findUserByUsername,
  findUserById,
  authenticateUser,
  createUser,
  updateUserAccount,
  createSessionForUser,
  revokeSession,
  revokeAllUserSessions,
  getSessionByToken,
  countUmkmByOwner,
  getOwnerUmkm,
  listOwnerUmkm,
  listPublicUmkm,
  getPublicUmkmById,
  upsertOwnerUmkm,
  deleteFileIfExists,
  updateOwnerUmkmPhoto,
  updateUmkmStatus,
  listAdminUmkm,
  getDashboardStats,
  listUsersWithCounts,
  cleanupExpiredSessions,
  uploadUrl,
};
