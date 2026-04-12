document.addEventListener("DOMContentLoaded",()=>{
    console.log("Загруска send_message.js");

    const Ai_request_input = document.getElementById('Ai_request_input');

    const Ai_send_message = document.getElementById('Ai_send_message');
    const Ai_load = document.getElementById('Ai_load');

    const SendBtn = document.getElementById('Ai_send_message');

    SendBtn.addEventListener('click',()=>{

        if(window.selectedAssistant == 'Менеджер'){

            let question = Ai_request_input.value;
            console.log(question);

            if(question.length < 3){
                console.log('сообщение слишком короткое!');
                return;
            }

            window.messageUser = question;
            console.log('click');

            Ai_request_input.value = '';

            Ai_send_message.classList.add('display_none');
            Ai_load.classList.remove('display_none');

            renderMessage('user', formatDateView(formatDate()), window.USER_DATA['name'], question, '.Ai_message_storage_manager');

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
                    loadingGenerate('delete', '.Ai_message_storage_manager');
                })
                .catch(err => console.error(err));
            })
            .catch(error =>{
                console.error('Ошибка:', error);
            });
        }
    });
});