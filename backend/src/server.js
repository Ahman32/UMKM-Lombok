const express = require('express');
const path = require('path');
const { initDatabase, cleanupExpiredSessions } = require('./db');
const { optionalAuth } = require('./middleware/auth');
const authRoutes = require('./routes/auth.routes');
const umkmRoutes = require('./routes/umkm.routes');
const adminRoutes = require('./routes/admin.routes');

const app = express();
const PORT = Number(process.env.PORT || 3000);
const HOST = process.env.HOST || '127.0.0.1';
const uploadDir = path.join(__dirname, '..', 'uploads');

initDatabase();

app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ extended: true }));
app.use(optionalAuth);
app.use('/uploads', express.static(uploadDir));

app.get('/health', (req, res) => {
  res.json({
    success: true,
    message: 'API UMKM Lombok Timur berjalan.',
    data: {
      service: 'webumkm-backend',
      status: 'ok',
      timestamp: new Date().toISOString(),
    },
  });
});

app.use('/auth', authRoutes);
app.use('/umkm', umkmRoutes);
app.use('/admin', adminRoutes);

app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint tidak ditemukan.',
  });
});

app.use((err, req, res, next) => {
  if (err && err.code === 'LIMIT_FILE_SIZE') {
    return res.status(413).json({
      success: false,
      message: 'Ukuran foto maksimal 5 MB.',
    });
  }

  const status = err.status || 500;
  res.status(status).json({
    success: false,
    message: err.message || 'Terjadi kesalahan pada server.',
    details: err.details || null,
  });
});

const server = app.listen(PORT, HOST, () => {
  console.log(`Backend UMKM Lombok Timur siap di http://${HOST}:${PORT}`);
});

const shutdown = () => {
  cleanupExpiredSessions();
  server.close(() => process.exit(0));
};

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
setInterval(cleanupExpiredSessions, 60 * 60 * 1000).unref();
