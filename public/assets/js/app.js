document.addEventListener('DOMContentLoaded', () => {
  // Auto dismiss flash alerts
  const alerts = document.querySelectorAll('.alert[data-auto-dismiss="1"]');
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  });
});
