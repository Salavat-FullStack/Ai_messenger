document.addEventListener("DOMContentLoaded",()=>{
    console.log("Загруска send_message.js");

        const SendBtn = document.getElementById('Ai_send_message');

        SendBtn.addEventListener('click',()=>{

            if(window.selectedAssistant == 'Менеджер'){

                console.log(window.messageUser);
                console.log(window.selectedAssistant);
                console.log(window.USER_DATA);

                fetch('https://chat-progress.ru/app/save_message.php',{
                    method: 'POST',
                    headers: {
                        "Content-Type" : "application/json"
                    },
                    body: JSON.stringify({
                        userData: window.USER_DATA,
                        messageUser: window.messageUser,
                        managerResponse: '',
                        date: formatDate(),
                        selectedAssistant: window.selectedAssistant
                })
                })
                .then(response => response.json())
                .then(data =>{
                    console.log(data);
                    fetch('https://chat-progress.ru/app/bot_max.php',{
                        method: 'POST',
                        headers:{
                            "Content-Type" : "application/json"
                        },
                        body: JSON.stringify({
                            userData: window.USER_DATA,
                            messageUser: window.messageUser,
                            managerResponse: '',
                            date: formatDateView(formatDate()),
                            selectedAssistant: window.selectedAssistant
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        console.log(data.status);
                    })
                    .catch(err => console.error(err));
                })
                .catch(error =>{
                    console.error('Ошибка:', error);
                });
            }
        });
});