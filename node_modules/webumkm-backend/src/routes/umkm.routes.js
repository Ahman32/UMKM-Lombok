const express = require('express');
const fs = require('fs');
const path = require('path');
const multer = require('multer');
const {
  createError,
  deleteFileIfExists,
  getOwnerUmkm,
  getPublicUmkmById,
  listPublicUmkm,
  upsertOwnerUmkm,
} = require('../db');
const { requireAuth, requireRole } = require('../middleware/auth');

const router = express.Router();
const uploadDir = path.join(__dirname, '..', '..', 'uploads');

fs.mkdirSync(uploadDir, { recursive: true });

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, uploadDir),
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname || '').toLowerCase() || '.jpg';
    const safeExt = ['.jpg', '.jpeg', '.png', '.webp', '.gif'].includes(ext) ? ext : '.jpg';
    const stamp = Date.now();
    const random = Math.random().toString(16).slice(2, 10);
    cb(null, `umkm-${stamp}-${random}${safeExt}`);
  },
});

const upload = multer({
  storage,
  limits: {
    fileSize: 5 * 1024 * 1024,
  },
  fileFilter: (req, file, cb) => {
    if (!file.mimetype || !file.mimetype.startsWith('image/')) {
      return cb(createError(422, 'Foto UMKM harus berupa gambar.'));
    }

    cb(null, true);
  },
});

function sendSuccess(res, data, message = 'OK', status = 200) {
  return res.status(status).json({
    success: true,
    message,
    data,
  });
}

function sendError(res, err) {
  const status = err.status || 500;
  return res.status(status).json({
    success: false,
    message: err.message || 'Terjadi kesalahan pada server.',
    details: err.details || null,
  });
}

function runUpload(req, res, next) {
  upload.single('foto_umkm')(req, res, (err) => {
    if (err) {
      return next(err);
    }

    next();
  });
}

router.get('/', (req, res) => {
  try {
    const filters = {
      q: req.query.q || '',
      lokasi: req.query.lokasi || '',
      kategori: req.query.kategori || '',
    };

    const items = listPublicUmkm(filters);
    return sendSuccess(
      res,
      {
        items,
        total: items.length,
        filters,
      },
      'Data UMKM berhasil dimuat.'
    );
  } catch (err) {
    return sendError(res, err);
  }
});

router.get('/me', requireAuth, (req, res) => {
  try {
    return sendSuccess(
      res,
      {
        umkm: getOwnerUmkm(req.auth.user.id),
      },
      'Data UMKM milik Anda berhasil dimuat.'
    );
  } catch (err) {
    return sendError(res, err);
  }
});

router.post('/me', requireRole('owner'), runUpload, (req, res) => {
  try {
    const previous = getOwnerUmkm(req.auth.user.id);
    const nextPhoto = req.file ? req.file.filename : null;
    const result = upsertOwnerUmkm(req.auth.user.id, req.body, nextPhoto);

    if (req.file && previous && previous.foto_path && previous.foto_path !== result.umkm.foto_path) {
      deleteFileIfExists(previous.foto_path);
    }

    return sendSuccess(
      res,
      {
        umkm: result.umkm,
        created: result.is_new,
      },
      result.is_new ? 'UMKM berhasil didaftarkan.' : 'UMKM berhasil diperbarui.'
    );
  } catch (err) {
    if (req.file) {
      deleteFileIfExists(req.file.filename);
    }
    return sendError(res, err);
  }
});

router.put('/me', requireRole('owner'), runUpload, (req, res) => {
  try {
    const previous = getOwnerUmkm(req.auth.user.id);
    const nextPhoto = req.file ? req.file.filename : null;
    const result = upsertOwnerUmkm(req.auth.user.id, req.body, nextPhoto);

    if (req.file && previous && previous.foto_path && previous.foto_path !== result.umkm.foto_path) {
      deleteFileIfExists(previous.foto_path);
    }

    return sendSuccess(
      res,
      {
        umkm: result.umkm,
        created: result.is_new,
      },
      result.is_new ? 'UMKM berhasil didaftarkan.' : 'UMKM berhasil diperbarui.'
    );
  } catch (err) {
    if (req.file) {
      deleteFileIfExists(req.file.filename);
    }
    return sendError(res, err);
  }
});

router.get('/:id(\\d+)', (req, res) => {
  try {
    const item = getPublicUmkmById(Number(req.params.id));
    if (!item) {
      return res.status(404).json({
        success: false,
        message: 'UMKM tidak ditemukan.',
      });
    }

    return sendSuccess(res, { item }, 'Detail UMKM berhasil dimuat.');
  } catch (err) {
    return sendError(res, err);
  }
});

module.exports = router;
