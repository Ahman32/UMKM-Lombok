const express = require('express');
const {
  getDashboardStats,
  listAdminUmkm,
  listUsersWithCounts,
  updateUmkmStatus,
} = require('../db');
const { requireRole } = require('../middleware/auth');

const router = express.Router();

function sendSuccess(res, data, message = 'OK', status = 200) {
  return res.status(status).json({
    success: true,
    message,
    data,
  });
}

function sendError(res, err) {
  return res.status(err.status || 500).json({
    success: false,
    message: err.message || 'Terjadi kesalahan pada server.',
    details: err.details || null,
  });
}

router.get('/stats', requireRole('admin'), (req, res) => {
  try {
    return sendSuccess(res, getDashboardStats(), 'Statistik dashboard berhasil dimuat.');
  } catch (err) {
    return sendError(res, err);
  }
});

router.get('/umkm', requireRole('admin'), (req, res) => {
  try {
    const items = listAdminUmkm({
      status: req.query.status || '',
      q: req.query.q || '',
      lokasi: req.query.lokasi || '',
      kategori: req.query.kategori || '',
    });

    return sendSuccess(
      res,
      {
        items,
        total: items.length,
        filters: {
          status: req.query.status || '',
          q: req.query.q || '',
          lokasi: req.query.lokasi || '',
          kategori: req.query.kategori || '',
        },
      },
      'Data UMKM berhasil dimuat.'
    );
  } catch (err) {
    return sendError(res, err);
  }
});

router.patch('/umkm/:id/status', requireRole('admin'), (req, res) => {
  try {
    const updated = updateUmkmStatus(Number(req.params.id), req.body.status, {
      adminId: req.auth.user.id,
      catatan_admin: req.body.catatan_admin || '',
    });

    return sendSuccess(res, { umkm: updated }, 'Status UMKM berhasil diperbarui.');
  } catch (err) {
    return sendError(res, err);
  }
});

router.get('/users', requireRole('admin'), (req, res) => {
  try {
    return sendSuccess(
      res,
      {
        items: listUsersWithCounts(),
      },
      'Daftar pengguna berhasil dimuat.'
    );
  } catch (err) {
    return sendError(res, err);
  }
});

module.exports = router;
