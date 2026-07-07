window.renderMessage = function(messageRole, date, user, question, block = '.Ai_message_storage', file = false){
    const Ai_message_storage = document.querySelector(block);

    let messageMainContainer = document.createElement('div');

    messageMainContainer.classList.add('messageMainContainer');

    if (file && file !== "не передан!") {

        const fileUrl = "https://chat-progress.ru/app" + file;

        // расширение файла
        const extension = file.split('.').pop().toLowerCase();

        // типы изображений
        const imageExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'bmp'
        ];

        // =========================
        // ЕСЛИ КАРТИНКА
        // =========================
        if (imageExtensions.includes(extension)) {

            let userImageChatContainer = document.createElement('a');

            userImageChatContainer.classList.add('userImageChatContainer');

            userImageChatContainer.href = fileUrl;

            userImageChatContainer.target = '_blank';

            userImageChatContainer.rel = 'noopener noreferrer';

            let img = document.createElement('img');

            img.src = fileUrl;

            userImageChatContainer.append(img);

            messageMainContainer.append(userImageChatContainer);
        }

        // =========================
        // ЕСЛИ ОБЫЧНЫЙ ФАЙЛ
        // =========================
        else {

            let fileContainer = document.createElement('a');

            fileContainer.classList.add('userFileChatContainer');

            fileContainer.href = fileUrl;

            fileContainer.target = '_blank';

            fileContainer.rel = 'noopener noreferrer';

            // имя файла
            const fileName = file.split('/').pop();

            fileContainer.innerHTML = `
                <div class="userFileChatItem">
                    📄 ${fileName}
                </div>
            `;

            messageMainContainer.append(fileContainer);
        }
    }

        let messageContainer = document.createElement('div');

        let message = document.createElement('div');

        let timeContainer = document.createElement('div');
        let user_name = document.createElement('div');
        let message_date = document.createElement('div');

        user_name.textContent = user;
        message_date.textContent = date;

        user_name.classList.add('message_user_name');
        message_date.classList.add('message_date');

        if(user){
            timeContainer.append(user_name, message_date);
        }else{
            timeContainer.append(message_date);
        }
        
        timeContainer.classList.add('Ai_message_date');

        message.innerHTML = question;   
        message.classList.add('Ai_message_value');

        if(messageRole == 'user'){
            messageContainer.classList.add('Ai_user_message','Ai_message');

            if(question.length < 1 || !question){
                messageContainer.classList.remove('Ai_user_message');
                messageContainer.classList.add('Ai_message_img_data');
            }
        }else if(messageRole == 'Ai'){
            messageContainer.classList.add('Ai_akuprof_message','Ai_message');
            if(user == "Менеджер akuprof"){
                user_name.classList.add('Ai_meneger_response');
            }
        }

        messageContainer.append(timeContainer,message);
        messageMainContainer.append(messageContainer);

        Ai_message_storage.append(messageMainContainer);

        // Ai_message_storage.scrollTop = Ai_message_storage.scrollHeight;
        Ai_message_storage.scrollTo({
            top: Ai_message_storage.scrollHeight
        });
    }


    window.formatDate = function() {
        const now = new Date();

        const pad = (n) => n.toString().padStart(2, '0');

        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ` +
                `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }

    window.formatDateView = function (input) {
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

    window.renderMessageManager = async function(assistant) {
        let token = localStorage.getItem('ai_chat_token');
        const authHeaders = {};

        if (token) {
            authHeaders['Authorization'] = 'Bearer ' + token;
        }

        try {
            // 1. Первый запрос (cookie.php)
            const cookieResponse = await fetch("https://chat-progress.ru/app/cookie.php", {
                method: "GET",
                headers: authHeaders
            });
            const cookieData = await cookieResponse.json();
            console.log(cookieData);

            if (cookieData.token) {
                localStorage.setItem('ai_chat_token', cookieData.token);
                token = cookieData.token;
            }

            window.AiUserToken = token;
            console.log(window.AiUserToken);

            // 2. Второй запрос (get_message.php)
            const messageResponse = await fetch('https://chat-progress.ru/app/get_message.php', {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + window.AiUserToken
                },
                credentials: "include",
                body: JSON.stringify({ assistant: assistant })
            });
            
            const data = await messageResponse.json();
            console.log(data);

            // 3. Рендеринг интерфейса
            const container = document.querySelector('.Ai_message_storage_manager');
            if (container) {
                container.innerHTML = "";
            }

            if (data['response']) {
                data['response'].forEach(elem => {
                    renderMessage("user", formatDateView(elem['date']), false, elem['messageUser'], ".Ai_message_storage_manager", elem["file"]);

                    if (elem['managerResponse'] && elem['managerResponse'].length > 0) {
                        const messageStore = addTagA(elem['managerResponse']);
                        renderMessage("Ai", formatDateView(elem['date']), "Менеджер akuprof", messageStore, ".Ai_message_storage_manager");
                    }
                });
            }

            // Теперь данные гарантированно вернутся!
            return data; 

        } catch (error) {
            console.error("Ошибка при получении сообщений:", error);
            // Можно вернуть null или пробросить ошибку дальше
            return null; 
        }
    }

    window.addTagA = function(message){
        if(!message || message.length < 1){
            return message;
        }
        return message.replace(
            /(https?:\/\/[^\s)]+)/g,
            (url) => {
                let cleanUrl = url.replace(/(\.html)+$/g, '');
                return `<a href="${cleanUrl}" target="_blank">${cleanUrl}</a>`;
            }
        );
    }

    window.AiForm = function(){
        document.getElementById('AiInputUserNameError').textContent = '';
        document.getElementById('AiInputUserEmailError').textContent = '';
        document.getElementById('AiInputUserPhoneError').textContent = '';

        const name = document.getElementById('AiInputUserName').value.trim();
        const email = document.getElementById('AiInputUserEmail').value.trim();
        let phone = document.getElementById('AiInputUserPhone').value.trim();

        let hasError = false;

        // EMAIL

        if(email !== ''){

            const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if(!emailValid.test(email)){
                document.getElementById('AiInputUserEmailError').textContent = 'Некорректный email';
                hasError = true;
            }
        }

        // NAME

        if(name !== ''){

            if(name.length < 2 || name.length > 30){
                document.getElementById('AiInputUserNameError').textContent = 'Имя должно быть от 2 до 30 символов';
                hasError = true;
            }

            else if(!/^[a-zA-Zа-яА-ЯёЁ]+$/u.test(name)){
                document.getElementById('AiInputUserNameError').textContent = 'Имя может содержать только буквы';
                hasError = true;
            }
        }

        // PHONE

        phone = phone.replace(/[^0-9+]/g, '');

        if(phone !== ''){

            if(phone.length < 10){
                document.getElementById('AiInputUserPhoneError').textContent = 'Короткий номер';
                hasError = true;
            }

            // const phoneValid = /^(\+7|8)\d{10}$/;

            // if(!phoneValid.test(phone)){
            //     document.getElementById('AiInputUserPhoneError').textContent = 'Введите корректный российский номер';
            //     hasError = true;
            // }
        }

        if(!hasError){
            USER_DATA.name = name;
            USER_DATA.email = email;
            USER_DATA.phone = phone;
        }
        
        return hasError;
    }