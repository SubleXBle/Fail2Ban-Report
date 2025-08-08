// assets/js/notifications.js

function showNotification(message, type = "info", duration = 5000) {
  const container = document.getElementById('notification-container');
  if (!container) return;

  const note = document.createElement('div');
  note.className = 'notification ' + type;

  note.innerText = message;
  container.appendChild(note);

//  console.log(`Notification shown: "${message}" for ${duration}ms`);

  setTimeout(() => {
    note.remove();
  }, duration);
}
