document.addEventListener("DOMContentLoaded",()=>{
    console.log("Загруска send_message.js");

        const SendBtn = document.getElementById('Ai_send_message');

        SendBtn.addEventListener('click',()=>{

            if(selectedAssistant == 'Менеджер'){

                console.log(messageUser);
                console.log(selectedAssistant);
                console.log(USER_DATA);

                fetch('https://chat-progress.ru/app/save_message.php',{
                    method: 'POST',
                    headers: {
                        "Content-Type" : "application/json"
                    },
                    body: JSON.stringify({
                        userData: USER_DATA,
                        messageUser: messageUser,
                        managerResponse: '',
                        date: formatDate(),
                        selectedAssistant: selectedAssistant
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
                            userData: USER_DATA,
                            messageUser: messageUser,
                            managerResponse: '',
                            date: formatDateView(formatDate()),
                            selectedAssistant: selectedAssistant
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