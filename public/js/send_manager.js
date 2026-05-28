document.addEventListener("DOMContentLoaded",()=>{
    console.log("Загруска send_message.js");

    const Ai_request_input = document.getElementById('Ai_request_input');

    const Ai_send_message = document.getElementById('Ai_send_message');
    const Ai_load = document.getElementById('Ai_load');

    const SendBtn = document.getElementById('Ai_send_message');

    function sendManager(){

        if(window.selectedAssistant == 'Менеджер'){

            const input = document.getElementById('fileInputAiModal');

            let question = Ai_request_input.value;
            console.log(question);

            if(question.length < 1 && !input.files.length){
                const input = document.querySelector('#Ai_request_input');
                input.placeholder = "введите сообщение!";
                return;
            }

            window.messageUser = question;
            console.log('click');

            Ai_request_input.value = '';

            Ai_send_message.classList.add('display_none');
            Ai_load.classList.remove('display_none');

            renderMessage('user', formatDateView(formatDate()), '', question, '.Ai_message_storage_manager');

            console.log(window.messageUser);
            console.log(window.selectedAssistant);
            console.log(window.USER_DATA);

            fetch("https://chat-progress.ru/app/cookie.php", {
                method: "GET",
                credentials: "include",
            })
            .then(res => res.json())
            .then(data => {
                console.log("login ok");
                console.log(data);
            });

            const file = input.files[0];

            const formData = new FormData();

            formData.append('messageUser', window.messageUser);
            formData.append('managerResponse', '');
            formData.append('date', formatDate());
            formData.append('selectedAssistant', window.selectedAssistant);
            formData.append('USER_DATA', JSON.stringify(USER_DATA));

            if (file) {
                formData.append('image', file);
            }

            console.log(formData);

            const preview = document.getElementById('ai_chat_preview');
            const close = document.getElementById('close_ai_chat_preview');

            preview.src = '';
            close.classList.add('display_none');

            fetch('https://chat-progress.ru/app/save_message.php',{
                method: 'POST',
                credentials: "include",
                body: formData
            })
            .then(response => response.json())
            .then(data =>{
                console.log(data);

                const formDataMaxBot = new FormData();

                formDataMaxBot.append('messageUser', window.messageUser);
                formDataMaxBot.append('managerResponse', '');
                formDataMaxBot.append('date', formatDateView(formatDate()));
                formDataMaxBot.append('selectedAssistant', window.selectedAssistant);
                formDataMaxBot.append('UserId', data['response']);
                formDataMaxBot.append('image', file);
                formDataMaxBot.append('USER_DATA', JSON.stringify(USER_DATA));
                formDataMaxBot.append("url", window.location.href);

                console.log(file);
                console.log(formDataMaxBot);

                fetch('https://chat-progress.ru/app/bot_max.php',{
                    method: 'POST',
                    credentials: "include",
                    body: formDataMaxBot
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    console.log(data.status);
                    loadingGenerateManager('delete');
                    Ai_send_message.classList.remove('display_none');
                    Ai_load.classList.add('display_none');
                    input.value = '';

                    renderMessageManager("Менеджер");
                })
                .catch(err => {
                    console.error(err);
                    input.value = '';
                });

            })
            .catch(error =>{
                console.error('Ошибка:', error);
                input.value = '';
            });
        }
    }

    SendBtn.addEventListener('click',()=>{

        const result = AiForm();
        if(!result){
            sendManager();
        }

    });

    Ai_request_input.addEventListener('keydown', function(event) {

        if (event.key === 'Enter' && !event.shiftKey) {

            event.preventDefault();

            const result = AiForm();
            if(!result){
                sendManager();
            }
        }
    });

    function loadingGenerateManager(action){
        const Ai_message_storage = document.querySelector('.Ai_message_storage_manager');
        if(action == "delete"){

            document.getElementById('Ai_loading_manager')?.remove();

            Ai_message_storage.scrollTo({
                top: Ai_message_storage.scrollHeight,
                behavior: 'smooth'
            });

        }else if(action == "create"){

            const chat = document.querySelector('.Ai_message_storage_manager');

            chat.insertAdjacentHTML('beforeend', `
                <div class="Ai_typing Ai_message_loading" id="Ai_loading_manager">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `);

            Ai_message_storage.scrollTo({
                top: Ai_message_storage.scrollHeight,
                behavior: 'smooth'
            });
        }
    };
});