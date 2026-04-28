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
window.showNotification = function() {
    if (!notificationPermission) return;

    new Notification('Новое сообщение от менеджера!');
}

// 5. ГЛАВНАЯ функция (вызываешь при новом сообщении)
window.onNewMessage = function({ text, fromUserId, myId }) {

    // ❗ не уведомляем если это наше сообщение
    // if (fromUserId === myId) return;

    // // ❗ если вкладка активна — можно не шуметь
    // if (!document.hidden) return;

    console.log('click onNewMessage');
    playNotificationSound();
    showNotification();
}


let messageQuantityGlobal = {
    response: []
};

(async () => {
    messageQuantityGlobal = await renderMessageManager("Менеджер");

    startPolling(); // 👈 запускаем только после загрузки
})();

function startPolling() {

    setInterval(async () => {

        const data = await renderMessageManager("Менеджер");

        console.log(messageQuantityGlobal);
        console.log(data);

        console.log(messageQuantityGlobal.response.length);
        console.log(data.response.length);

        const changes = detectChanges(
            messageQuantityGlobal.response,
            data.response
        );

        if (changes.length > 0 || messageQuantityGlobal.response.length < data.response.length) {
            console.log("новые изменения:", changes);

            onNewMessage({
                text: "Новое сообщение от менеджера!",
                fromUserId: "test",
                myId: "test2"
            });
        }

        messageQuantityGlobal = data;

    }, 60000);
}

function detectChanges(oldArr, newArr) {

    const changes = [];

    const oldMap = new Map();

    oldArr.forEach(item => {
        const key = item.user_token + "_" + item.date;
        oldMap.set(key, item);
    });

    newArr.forEach(item => {

        const key = item.user_token + "_" + item.date;
        const oldItem = oldMap.get(key);

        if (oldItem && oldItem.managerResponse !== item.managerResponse) {
            console.log('if old and new == true');
            changes.push({
                old: oldItem,
                new: item
            });
        }
    });
    console.log(changes);

    return changes;
}

});