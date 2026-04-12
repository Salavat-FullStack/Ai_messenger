window.renderMessage = function(messageRole, date, user, question, block = '.Ai_message_storage'){
        const Ai_message_storage = document.querySelector(block);
        console.log(Ai_message_storage);

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

    window.renderMessageManager = function(assistant){
        fetch('https://chat-progress.ru/app/get_message.php',{
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userData: window.USER_DATA,
                assistant: assistant
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);

            // data['response'].forEach(elem =>{
            //     messageStore = addTagA(elem['messageAi']);

            //     renderMessage('user', formatDateView(elem['date']), elem['user_name'], elem['messageUser']);
            //     renderMessage('Ai', formatDateView(elem['date']), "akuprof.ru", messageStore);
            // });

            // let lastMessage = data['response'].slice(-2);

            // console.log(lastMessage);

            // lastMessage.forEach(element => {
            //     console.log(element);
            //     store_messages += `вопрос пользователя: "${element['messageUser']}" \n ответ асистента: "${element['messageAi']}" \n`;
            // });
            // console.log(store_messages);
        });
    }