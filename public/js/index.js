document.addEventListener('DOMContentLoaded',()=>{
    const SendBtn = document.getElementById('Ai_send_message');
    // const TextContainer = document.querySelector('.Ai_response_cont');
    const Ai_request_input = document.getElementById('Ai_request_input');

    const Ai_send_message = document.getElementById('Ai_send_message');
    const Ai_load = document.getElementById('Ai_load');

    let store_messages = '';
    let question = '';

    window.messageUser = '';
    window.messageReview = '';

    window.selectedAssistant = 'ИИ ассистент';

    let responseAi = '';

    window.USER_DATA = {
        'name' : '',
        'phone' : '',
        'email' : ''
    };

    fetch("https://chat-progress.ru/app/cookie.php",{
        method: "GET",
        credentials: "include",
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);

        fetch('https://chat-progress.ru/app/get_message.php',{
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: "include",
            body: JSON.stringify({
                assistant: window.selectedAssistant
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);

            data['response'].forEach(elem =>{
                messageStore = addTagA(elem['messageAi']);

                renderMessage('user', formatDateView(elem['date']), elem['user_name'], elem['messageUser']);
                renderMessage('Ai', formatDateView(elem['date']), "ИИ ассистент", messageStore);

                if(elem['managerResponse']){
                    renderMessage('Ai', formatDateView(elem['date']), "Менеджер akuprof", addTagA(elem['managerResponse']));
                    
                }
            });

            let lastMessage = data['response'].slice(-2);

            console.log(lastMessage);

            lastMessage.forEach(element => {
                console.log(element);
                store_messages += `вопрос пользователя: "${element['messageUser']}" \n ответ асистента: "${element['messageAi']}" \n`;
            });
            console.log(store_messages);
        });

    });

    function sendAi(){
        question = Ai_request_input.value;
        console.log(question);

        if(question.length < 1){
            const input = document.querySelector('#Ai_request_input');
            input.placeholder = "введите сообщение!";
            return;
        }

        window.messageUser = question;
        console.log('click');

    if(window.selectedAssistant == 'ИИ ассистент'){


        Ai_request_input.value = '';

        Ai_send_message.classList.add('display_none');
        Ai_load.classList.remove('display_none');

        renderMessage('user', formatDateView(formatDate()), "", question);

        fetch('https://chat-progress.ru/app/review.php',{
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                story: store_messages,
                request: question
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);
            console.log(data['response']);
            messageReview = data['response'];
            fetch('https://chat-progress.ru/app/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    question: data['response']
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Ответ сервера:', data['responseAi']);
                console.log('контекст', data['docs']);

                // AiResponse = data['responseAi'].replace(
                //     /(https?:\/\/[^\s)]+)/g, 
                //     '<a href="$1" target="_blank">$1</a>' 
                // );

                AiResponse = addTagA(data['responseAi']);

                loadingGenerate('delete');

                Ai_send_message.classList.remove('display_none');
                Ai_load.classList.add('display_none');

                renderMessage('Ai', formatDateView(formatDate()), "", AiResponse);

                console.log(window.USER_DATA);
                console.log(window.messageUser);
                console.log(window.messageReview);
                console.log(data['responseAi']);
                console.log(formatDate());
                console.log(window.selectedAssistant);

                const formData = new FormData();

                formData.append('messageUser', window.messageUser);
                formData.append('messageReview', window.messageReview);
                formData.append('messageAi', data['responseAi']);
                formData.append('date', formatDate());
                formData.append('selectedAssistant', window.selectedAssistant);
                formData.append('USER_DATA', JSON.stringify(USER_DATA));

                    responseAi = data['responseAi'];

                    fetch('https://chat-progress.ru/app/save_message.php',{
                        method: 'POST',
                        credentials: "include",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data =>{
                        console.log(data);
                        console.log(data['response']);

                        const formDataMaxBot = new FormData();

                        formDataMaxBot.append('messageUser', window.messageUser);
                        formDataMaxBot.append('messageReview', window.messageReview);
                        formDataMaxBot.append('messageAi', responseAi);
                        formDataMaxBot.append('date', formatDateView(formatDate()));
                        formDataMaxBot.append('selectedAssistant', window.selectedAssistant);
                        formDataMaxBot.append('UserId', data['response']);
                        formDataMaxBot.append('USER_DATA', JSON.stringify(USER_DATA));
                        formDataMaxBot.append("url", window.location.href);

                        fetch('https://chat-progress.ru/app/bot_max.php',{
                            method: 'POST',
                            credentials: "include",
                            body: formDataMaxBot
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
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    loadingGenerate('delete');
                    Ai_send_message.classList.remove('display_none');
                    Ai_load.classList.add('display_none');
                    alert('Что-то пошло не так :(');
            });
        })
        .catch(error => {
            console.error(error);
            loadingGenerate('delete');
            Ai_send_message.classList.remove('display_none');
            Ai_load.classList.add('display_none');
            alert('Что-то пошло не так :(');
        })
        }
    }

    
    let AiResponse; 

    SendBtn.addEventListener('click',()=>{

        const Ai_form = document.getElementById('Ai_form');

        console.log(Ai_form.classList.contains('display_none'));

        if(Ai_form.classList.contains('display_none') === false){
            const result = AiForm();
            if(!result){
                Ai_form.classList.add('display_none');
                sendAi();
            }
        }
        // Ai_form.classList.remove('display_none');
        // const result = AiForm();
        // if(!result){
        //     sendAi();
        // }
    });

    Ai_request_input.addEventListener('keydown', function(event) {

        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();

            const Ai_form = document.getElementById('Ai_form');

            // console.log(Ai_form.classList.contains('display_none'));

            // if(Ai_form.classList.contains('display_none')){

            if(Ai_form.classList.contains('display_none')){
                Ai_form.classList.remove('display_none');
                return;
            }

            // if(Ai_form.classList.contains('display_none') === false){
            // if(USER_DATA.name == "" && USER_DATA.email == "" && USER_DATA.phone == ""){
                
            // }
                const result = AiForm();
                if(!result){
                    // console.log('add display_none');
                    Ai_form.classList.add('display_none');
                    sendAi();
                }
            // }
        }
    });

    function loadingGenerate(action){
        const Ai_message_storage = document.querySelector('.Ai_message_storage');
        if(action == "delete"){

            document.getElementById('Ai_loading')?.remove();

            Ai_message_storage.scrollTo({
                top: Ai_message_storage.scrollHeight,
                behavior: 'smooth'
            });

        }else if(action == "create"){

            const chat = document.querySelector('.Ai_message_storage');

            chat.insertAdjacentHTML('beforeend', `
                <div class="Ai_typing Ai_message_loading" id="Ai_loading">
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

    const assistant_btn = document.querySelectorAll('.assistant_btn');
    const Ai_message_storage_manager = document.querySelector('.Ai_message_storage_manager');
    const Ai_message_storage = document.querySelector('.Ai_message_storage');

    assistant_btn.forEach(elem =>{

        elem.addEventListener('click',()=>{
            assistant_btn.forEach(elem =>{
                elem.classList.remove('assistant_btn_active');  
            });
            elem.classList.add('assistant_btn_active');
            window.selectedAssistant = elem.textContent;
            console.log(window.selectedAssistant);

            const Ai_file_btn = document.querySelector('.Ai_file_btn');

            if(selectedAssistant == 'Менеджер'){
                Ai_message_storage_manager.classList.remove('display_none');
                Ai_message_storage.classList.add('display_none');   
                
                const btn = document.querySelector('#assist_manager');
                const btn2 = document.querySelector('#assist_Ai');
                btn.classList.remove('message_notification');
                btn2.classList.remove('message_notification');

                const open_btn = document.querySelector('.open_btn_message_notification');
                open_btn.classList.add('display_none');

                Ai_file_btn.classList.remove('display_none');

                renderMessageManager("Менеджер");
            }else if(selectedAssistant == 'ИИ ассистент'){
                Ai_message_storage_manager.classList.add('display_none');
                Ai_message_storage.classList.remove('display_none');
                Ai_file_btn.classList.add('display_none');

                const open_btn = document.querySelector('.open_btn_message_notification');
                open_btn.classList.add('display_none');

                const btn2 = document.querySelector('#assist_Ai');
                btn2.classList.remove('message_notification');
            }
        });
    });
});