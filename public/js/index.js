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
        'name' : 'Salavat',
        'surname' : 'Axmetgareev',
        'email' : 'salavat@gmail.com'
    };

    fetch('https://chat-progress.ru/app/get_message.php',{
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: "include",
        body: JSON.stringify({
            userData: window.USER_DATA,
            assistant: window.selectedAssistant
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log(data);

        data['response'].forEach(elem =>{
            messageStore = addTagA(elem['messageAi']);

            renderMessage('user', formatDateView(elem['date']), elem['user_name'], elem['messageUser']);
            renderMessage('Ai', formatDateView(elem['date']), "akuprof.ru", messageStore);
        });

        let lastMessage = data['response'].slice(-2);

        console.log(lastMessage);

        lastMessage.forEach(element => {
            console.log(element);
            store_messages += `вопрос пользователя: "${element['messageUser']}" \n ответ асистента: "${element['messageAi']}" \n`;
        });
        console.log(store_messages);
    });

    let AiResponse; 

    SendBtn.addEventListener('click',()=>{

        question = Ai_request_input.value;
        console.log(question);

        if(question.length < 3){
            console.log('сообщение слишком короткое!');
            return;
        }

        window.messageUser = question;
        console.log('click');

    if(window.selectedAssistant == 'ИИ ассистент'){


        Ai_request_input.value = '';

        Ai_send_message.classList.add('display_none');
        Ai_load.classList.remove('display_none');

        renderMessage('user', formatDateView(formatDate()), window.USER_DATA['name'], question);

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
                    user: window.USER_DATA,
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

                renderMessage('Ai', formatDateView(formatDate()), window.USER_DATA['name'], AiResponse);

                console.log(window.USER_DATA);
                console.log(window.messageUser);
                console.log(window.messageReview);
                console.log(data['responseAi']);
                console.log(formatDate());

                responseAi = data['responseAi'];

                fetch('https://chat-progress.ru/app/save_message.php',{
                    method: 'POST',
                    headers: {
                        "Content-Type" : "application/json"
                    },
                    credentials: "include",
                    body: JSON.stringify({
                        userData: window.USER_DATA,
                        messageUser: window.messageUser,
                        messageReview: window.messageReview,
                        messageAi: data['responseAi'],
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
                                messageReview: window.messageReview,
                                messageAi: responseAi,
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

            if(selectedAssistant == 'Менеджер'){
                Ai_message_storage_manager.classList.remove('display_none');
                Ai_message_storage.classList.add('display_none');   

                renderMessageManager("Менеджер");
            }else if(selectedAssistant == 'ИИ ассистент'){
                Ai_message_storage_manager.classList.add('display_none');
                Ai_message_storage.classList.remove('display_none');
            }
        });
    });
});