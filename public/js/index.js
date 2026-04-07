document.addEventListener('DOMContentLoaded',()=>{
    const SendBtn = document.getElementById('Ai_send_message');
    // const TextContainer = document.querySelector('.Ai_response_cont');
    const Ai_request_input = document.getElementById('Ai_request_input');

    const Ai_send_message = document.getElementById('Ai_send_message');
    const Ai_load = document.getElementById('Ai_load');

    let store_messages = '';
    let question = '';

    let messageUser = '';
    let messageReview = '';

    let selectedAssistant = '';

    let responseAi = '';

    const USER_DATA = {
        'name' : 'Salavat',
        'surname' : 'Axmetgareev',
        'email' : 'salavat@gmail.com'
    };

    fetch('https://chat-progress.ru/app/get_message.php',{
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            userData: USER_DATA,
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

        messageUser = question;
        console.log('click');

        Ai_request_input.value = '';

        Ai_send_message.classList.add('display_none');
        Ai_load.classList.remove('display_none');

        renderMessage('user', formatDateView(formatDate()), USER_DATA['name'], question);

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
            fetch('https://chat-progress.ru', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user: USER_DATA,
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

                renderMessage('Ai', formatDateView(formatDate()), USER_DATA['name'], AiResponse);

                console.log(USER_DATA);
                console.log(messageUser);
                console.log(messageReview);
                console.log(data['responseAi']);
                console.log(formatDate());

                responseAi = data['responseAi'];

                fetch('https://chat-progress.ru/app/save_message.php',{
                    method: 'POST',
                    headers: {
                        "Content-Type" : "application/json"
                    },
                    body: JSON.stringify({
                        userData: USER_DATA,
                        messageUser: messageUser,
                        messageReview: messageReview,
                        messageAi: data['responseAi'],
                        date: formatDate()
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
                                messageReview: messageReview,
                                messageAi: responseAi,
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

    });


    function renderMessage(messageRole, date, user, question){
        const Ai_message_storage = document.querySelector('.Ai_message_storage');

        let messageContainer = document.createElement('div');

        let message = document.createElement('div');

        let timeContainer = document.createElement('div');
        let user_name = document.createElement('div');
        let message_date = document.createElement('div');

        user_name.textContent = user;
        message_date.textContent = date;

        user_name.classList.add('message_user_name');
        message_date.classList.add('message_date');

        timeContainer.append(user_name, message_date);
        timeContainer.classList.add('Ai_message_date');

        message.innerHTML = question;   
        message.classList.add('Ai_message_value');

        if(messageRole == 'user'){
            messageContainer.classList.add('Ai_user_message','Ai_message');
        }else if(messageRole == 'Ai'){
            messageContainer.classList.add('Ai_akuprof_message','Ai_message');
        }
        
        messageContainer.append(timeContainer,message);
        Ai_message_storage.append(messageContainer);

        // Ai_message_storage.scrollTop = Ai_message_storage.scrollHeight;
        Ai_message_storage.scrollTo({
            top: Ai_message_storage.scrollHeight,
            behavior: 'smooth'
        });
    }

    function addTagA(message){
        return message.replace(
            /(https?:\/\/[^\s)]+)/g,
            (url) => {
                let cleanUrl = url.replace(/(\.html)+$/g, '');
                return `<a href="${cleanUrl}" target="_blank">${cleanUrl}</a>`;
            }
        );
    }


    function formatDate() {
        const now = new Date();

        const pad = (n) => n.toString().padStart(2, '0');

        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ` +
                `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }

    function formatDateView(input) {
        const date = new Date(input.replace(' ', 'T'));

        return new Intl.DateTimeFormat('ru-RU', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        })
        .format(date)
        .replace(',', '');
    }

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

    assistant_btn.forEach(elem =>{

        elem.addEventListener('click',()=>{
            assistant_btn.forEach(elem =>{
                elem.classList.remove('assistant_btn_active');  
            });
            elem.classList.add('assistant_btn_active');
            selectedAssistant = elem.textContent;
            console.log(selectedAssistant);
        });
    });
});