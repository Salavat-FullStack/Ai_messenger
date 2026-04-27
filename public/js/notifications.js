document.addEventListener("DOMContentLoaded", ()=>{
let soundEnabled = false;
let notificationPermission = false;

// 1. Разблокируем звук (обязательно!)
document.addEventListener('click', () => {
    soundEnabled = true;
});

// 2. Запрашиваем разрешение на уведомления
if ('Notification' in window) {
    Notification.requestPermission().then(permission => {
        notificationPermission = (permission === 'granted');
    });
}

// 3. Функция звука
window.playNotificationSound = function() {
    if (!soundEnabled) return;

    const audio = new Audio('/sounds/notification.mp3');
    audio.volume = 0.5;
    audio.play().catch(() => {});
}

// 4. Функция уведомления
window.showNotification = function(message) {
    if (!notificationPermission) return;

    new Notification('Новое сообщение', {
        body: message,
        icon: '/icon.png'
    });
}

// 5. ГЛАВНАЯ функция (вызываешь при новом сообщении)
window.onNewMessage = function({ text, fromUserId, myId }) {

    // ❗ не уведомляем если это наше сообщение
    if (fromUserId === myId) return;

    // ❗ если вкладка активна — можно не шуметь
    if (!document.hidden) return;

    playNotificationSound();
    showNotification(text);
}
});