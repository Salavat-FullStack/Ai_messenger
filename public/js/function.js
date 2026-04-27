window.renderMessage = function(messageRole, date, user, question, block = '.Ai_message_storage'){
        const Ai_message_storage = document.querySelector(block);

        let messageContainer = document.createElement('div');

        let message = document.createElement('div');

        let timeContainer = document.createElement('div');
        // let user_name = document.createElement('div');
        let message_date = document.createElement('div');

        // user_name.textContent = user;
        message_date.textContent = date;

        // user_name.classList.add('message_user_name');
        message_date.classList.add('message_date');

        timeContainer.append(message_date);
        // timeContainer.append(user_name, message_date);
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
            credentials: "include",
            body: JSON.stringify({
                assistant: assistant
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);

            const Ai_message_storage_manager = document.querySelector('.Ai_message_storage_manager');

            Ai_message_storage_manager.innerHTML = "";

            console.log(Ai_message_storage_manager);

            data['response'].forEach(elem => {
                renderMessage("user", formatDateView(elem['date']), elem['user_name'],elem['messageUser'], ".Ai_message_storage_manager");
                if(elem['managerResponse'].length > 0){
                    messageStore = addTagA(elem['managerResponse']);
                    
                    renderMessage("Ai", formatDateView(elem['date']), "akuprof.ru", messageStore, ".Ai_message_storage_manager");
                }
            });

            return data;
        });
    }

    window.addTagA = function(message){
        return message.replace(
            /(https?:\/\/[^\s)]+)/g,
            (url) => {
                let cleanUrl = url.replace(/(\.html)+$/g, '');
                return `<a href="${cleanUrl}" target="_blank">${cleanUrl}</a>`;
            }
        );
    }