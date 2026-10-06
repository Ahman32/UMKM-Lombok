const { getSessionByToken } = require('../db');

function extractToken(req) {
  const authHeader = req.headers.authorization || req.headers.Authorization || '';
  if (typeof authHeader === 'string' && authHeader.toLowerCase().startsWith('bearer ')) {
    return authHeader.slice(7).trim();
  }

  const altToken = req.headers['x-auth-token'] || req.headers['x-session-token'];
  if (typeof altToken === 'string' && altToken.trim()) {
    return altToken.trim();
  }

  return '';
}

function attachAuth(req) {
  const token = extractToken(req);
  if (!token) {
    return null;
  }

  const session = getSessionByToken(token);
  if (!session) {
    return null;
  }

  return {
    token,
    session,
    user: session.user,
  };
}

function optionalAuth(req, res, next) {
  req.auth = attachAuth(req);
  next();
}

function requireAuth(req, res, next) {
  const auth = req.auth || attachAuth(req);
  if (!auth) {
    return res.status(401).json({
      success: false,
      message: 'Silakan login terlebih dahulu.',
    });
  }

  req.auth = auth;
  next();
}

function requireRole(...roles) {
  return (req, res, next) => {
    const auth = req.auth || attachAuth(req);
    if (!auth) {
      return res.status(401).json({
        success: false,
        message: 'Silakan login terlebih dahulu.',
      });
    }

    if (roles.length && !roles.includes(auth.user.role)) {
      return res.status(403).json({
        success: false,
        message: 'Anda tidak memiliki akses ke halaman ini.',
      });
    }

    req.auth = auth;
    next();
  };
}

module.exports = {
  extractToken,
  optionalAuth,
  requireAuth,
  requireRole,
};
