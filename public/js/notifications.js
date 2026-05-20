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

    startPolling(messageQuantityGlobal, "meneger"); // 👈 запускаем только после загрузки
})();


let AiArrayGlobal = {
    response: []
};

(async () => {
    AiArrayGlobal = await renderAi();

    startPolling(AiArrayGlobal, "ai_and_meneger");
})();

const open_btn = document.querySelector('.open_btn_message_notification');

function startPolling(messageQuantityGlobal, type) {

    setInterval(async () => {

        if(type == "meneger"){
            const data = await renderMessageManager("Менеджер");
        }else if(type == "ai_and_meneger"){
            const data = await renderAi();
        }

        console.log(messageQuantityGlobal);
        console.log(data);

        console.log(messageQuantityGlobal.response.length);
        console.log(data.response.length);

        const changes = detectChanges(
            messageQuantityGlobal.response,
            data.response
        );

        changes.forEach(item => {

            if (item.type === 'new') {
                if(item.new.managerResponse.length > 1){
                    console.log("новое сообщение");
                    onNewMessage({
                        text: "Новое сообщение от менеджера!"
                    });
                    const btn = document.querySelector('#assist_manager');
                    btn.classList.add('message_notification');

                    open_btn.classList.remove('display_none');
                }

                return;
            }

            if (item.type === 'updated') {
                console.log("менеджер ответил:", item.new.managerResponse);
                onNewMessage({
                    text: "Новое сообщение от менеджера!"
                });
                const btn = document.querySelector('#assist_manager');
                btn.classList.add('message_notification');

                open_btn.classList.remove('display_none');
            }
        });

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

        if (!oldItem) {
            changes.push({
                type: 'new',
                new: item
            });
        }

        else if (oldItem.managerResponse !== item.managerResponse) {
            changes.push({
                type: 'updated',
                old: oldItem,
                new: item
            });
        }

    });
    console.log(changes);

    return changes;
}

async function renderAi(){
    const response = await fetch('https://chat-progress.ru/app/get_message.php',{
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: "include",
        body: JSON.stringify({
            assistant: window.selectedAssistant
        })
    });

    const data = response.json();

    // .then(response => response.json())
    // .then(data => {
        AiArrayGlobal = data

        data['response'].forEach(elem =>{
            messageStore = addTagA(elem['messageAi']);

            renderMessage('user', formatDateView(elem['date']), elem['user_name'], elem['messageUser']);
            renderMessage('Ai', formatDateView(elem['date']), "akuprof.ru", messageStore);

            if(elem['managerResponse']){
                renderMessage('Ai', formatDateView(elem['date']), "Менеджер akuprof", addTagA(elem['managerResponse']));
            }
        });
    // });

    return data;
}

});