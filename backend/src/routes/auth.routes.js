const express = require('express');
const {
  authenticateUser,
  createUser,
  createSessionForUser,
  getOwnerUmkm,
  updateUserAccount,
  revokeSession,
} = require('../db');
const { requireAuth } = require('../middleware/auth');

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

function authPayload(user, session = null) {
  return {
    user,
    umkm: user.role === 'owner' ? getOwnerUmkm(user.id) : null,
    session: session
      ? {
          token: session.token,
          expires_at: session.expires_at,
          created_at: session.created_at,
        }
      : null,
  };
}

router.post('/register', (req, res) => {
  try {
    const user = createUser({
      name: req.body.name,
      username: req.body.username,
      password: req.body.password,
      role: 'owner',
    });

    return sendSuccess(res, { user }, 'Registrasi berhasil. Silakan login.', 201);
  } catch (err) {
    return sendError(res, err);
  }
});

router.post('/login', (req, res) => {
  try {
    const user = authenticateUser(req.body.username, req.body.password);
    if (!user) {
      return res.status(401).json({
        success: false,
        message: 'Username atau password salah.',
      });
    }

    const session = createSessionForUser(user.id);
    return sendSuccess(res, authPayload(user, session), 'Login berhasil.');
  } catch (err) {
    return sendError(res, err);
  }
});

router.post('/logout', (req, res) => {
  try {
    const token = (req.headers.authorization || '').toLowerCase().startsWith('bearer ')
      ? req.headers.authorization.slice(7).trim()
      : req.headers['x-auth-token'] || req.headers['x-session-token'] || '';

    if (token) {
      revokeSession(token);
    }

    return sendSuccess(res, null, 'Logout berhasil.');
  } catch (err) {
    return sendError(res, err);
  }
});

router.get('/me', requireAuth, (req, res) => {
  try {
    return sendSuccess(res, authPayload(req.auth.user), 'Data akun berhasil dimuat.');
  } catch (err) {
    return sendError(res, err);
  }
});

router.put('/me', requireAuth, (req, res) => {
  try {
    const updated = updateUserAccount(
      req.auth.user.id,
      {
        name: req.body.name,
        current_password: req.body.current_password,
        new_password: req.body.new_password,
      },
      req.auth.token
    );

    return sendSuccess(
      res,
      authPayload(updated, { token: req.auth.token, expires_at: req.auth.session.expires_at, created_at: req.auth.session.created_at }),
      'Profil akun berhasil diperbarui.'
    );
  } catch (err) {
    return sendError(res, err);
  }
});

module.exports = router;
