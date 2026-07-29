document.addEventListener('DOMContentLoaded',()=>{
    document.body.insertAdjacentHTML('beforeend', `
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Pacifico&family=Roboto:ital,wght@0,100..900;1,100..900&family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
    
    <div id="Ai_modal_open_btn">
        <div class="open_btn_message_notification display_none"></div>
        <img id="chat_ai_logo_btn" src="https://chat-progress.ru/image/icons/Chat_open.svg" alt="open_modal">
    </div>

    <div class="reminder_message_ai">
        <div class="reminder_message_btn"><img src="https://chat-progress.ru/image/icons/close_icon_black.png" alt="close"></div>
        <p>Здравствуйте, задайте пожалуйста вопрос. Обычно мы отвечаем в течении пары минут (за исключением выходных и ночных часов)</p>
    </div>

    <div class="Ai_modal">
        <div class="Ai_nav">
            <div class="Ai_manager">
                <img src="https://chat-progress.ru/image/icons/Ai_akuprof_logo.png" alt="manager" class="Ai_manager_logo">
                <div class="assistant_penel">
                    <div class="assistant_btn assistant_btn_active" id="assist_Ai">ИИ ассистент</div>
                    <div class="assistant_btn" id="assist_manager">Менеджер</div>
                </div>
                <img src="https://chat-progress.ru/image/icons/question_icon.png" id="instructions_icon_Ai" alt="instructions">
            </div>
            <div class="Ai_manager_call">
                <img src="https://chat-progress.ru/image/icons/roll_up_icon.svg" alt="call" class="Ai_call_logo" id="Ai_modal_close_img">
            </div>
        </div>
        <div class="Ai_message_storage">
            <div class="Ai_akuprof_message Ai_message">
                <div class="Ai_message_date">
                    <div class="message_user_name">akuprof.ru</div>
                    <div class="message_date">27 февр. 11:27</div>
                </div>
                <div class="Ai_message_value">Ai ассистент, приветствует Вас! Задавайте вопросы, а мы с радостью ответим и поможем Вам!</div>
            </div>
        </div>

        <div class="Ai_message_storage_manager display_none">
            <div class="Ai_akuprof_message Ai_message">
                <div class="Ai_message_date">
                    <div class="message_user_name">akuprof.ru</div>
                    <div class="message_date">27 февр. 11:27</div>
                </div>
                <div class="Ai_message_value">Менеждер akuprof, приветствует Вас! Задавайте вопросы, а мы с радостью ответим и поможем Вам!</div>
            </div>
        </div>

        <div class="Ai_panel">
            <textarea id="Ai_request_input" placeholder="Введите сообщение..."></textarea>

        <div id="ai_chat_preview_box">
            <div id="close_ai_chat_preview" class="display_none"></div>
            <img id="ai_chat_preview">
            <div id="ai_chat_file_preview" class="display_none"></div>
        </div>


            <div class="Ai_file_btn display_none">
                <img src="https://chat-progress.ru/image/icons/clip.svg" alt="file">
                <input 
                    type="file" 
                    id="fileInputAiModal" 
                        accept="
                            image/png,
                            image/jpeg,
                            image/webp,
                            application/pdf,
                            text/plain,
                            .doc,
                            .docx,
                            .xls,
                            .xlsx
                        "
                    style="display: none;"
                >
            </div>
            
            <div id="Ai_send_btn">
                <img src="https://chat-progress.ru/image/icons/send.svg" id="Ai_send_message" alt="send">
                <div class="loader display_none" id="Ai_load">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
        
        <form id="Ai_form" class="display_none">
            <h2>Представьтесь пожалуйста</h2>
            
            <div class="Ai_form_input">
                <input type="text" id="AiInputUserName" placeholder="Имя">
                <div id="AiInputUserNameError" style="color:red;"></div>
            </div>
            <div class="Ai_form_input">
                <input type="text" id="AiInputUserEmail" placeholder="Email">
                <div id="AiInputUserEmailError" style="color:red;"></div>
            </div>
            <div class="Ai_form_input">
                <input type="text" id="AiInputUserPhone" placeholder="Телефон">
                <div id="AiInputUserPhoneError" style="color:red;"></div>
            </div>

            <p>
                Отправляя свои данные, вы соглашаетесь с <a target="_blank" id="userAgreementChatAi" href="https://akuprof.ru/terms.html">пользовательским соглашением</a>!
            </p>
        </form>
    </div>

    <div class="Ai_modal_instructions display_none">
        <div class="instructions_container">
            <div class="instructions_nav"></div>
            <img src="https://chat-progress.ru/image/icons/close_icon.svg" id="icon_close_modal_instructions" alt="close">

            <div class="instructions_block assist_Ai_instruc" id="instructions_Ai">
                <h2>ИИ ассистент</h2>
                <p>ИИ-ассистент — это искусственный интеллект, который в любое время готов ответить на любой ваш вопрос, связанный с деятельностью итернет-магазина и не только, порекомендовать подходящие товары и помочь с выбором. Ассистент работает быстро, доступен 24/7 и помогает сэкономить ваше время.</p>
                <p> * ИИ-ассистент — не знает о наличии товара. Если вам нужно уточнить наличие, пожалуйста, обратитесь к менеджерам, по номеру +7 (495) 970 82 03</p>
                <p> * Если вас не устраивает ответ ИИ-ассистента или вы хотите обратиться напрямую к менеджеру, выберите в панели чата опцию «Менеджер» и отправьте сообщение. Ваш вопрос будет отправлен и рассмотрен менеджерами в ближайшее время.</p>
                </div>

            <div class="instructions_block assist_manager_instruc display_none" id="instructions_manager">
                <h2>Менеджер akuprof</h2>
                <p>Если вас не устраивает ответ ИИ-ассистента или ваш вопрос требует консультации менеджера, вы всегда можете отправить сообщение, выбрав в панели чата опцию «Менеджер», и в ближайшее время менеджер свяжется с вами.</p>
            </div>

            <div class="instructions_block assist_about_instruc display_none" id="instructions_about">
                <h2>Общее</h2>
                <p>ИИ-ассистент — это искусственный интеллект, который в любое время готов ответить на любой ваш вопрос, связанный с сайтом и не только, порекомендовать подходящие товары и помочь с выбором. Ассистент работает быстро, доступен 24/7 и помогает сэкономить ваше время.</p>
                <p> * ИИ-ассистент — не знает о наличии товара. Если вам нужно уточнить наличие, пожалуйста, обратитесь к менеджерам.</p>
            </div>
        </div>
    </div>
    `);

    const instructions_btn = document.querySelectorAll('.instructions_btn');
    const instructions_block_all = document.querySelectorAll('.instructions_block');
    
    instructions_btn.forEach(elem =>{
        elem.addEventListener('click',()=>{
            instructions_btn.forEach(elem =>{
                elem.classList.remove('instructions_btn_active');  
            });
            elem.classList.add('instructions_btn_active');
            let elemId = elem.id.slice(0, -4);
            // console.log(elemId);

            instructions_block_all.forEach(elem =>{
                elem.classList.add('display_none');
            });
            const instructions_block = document.getElementById(elemId);
            // console.log(instructions_block);
            instructions_block.classList.remove('display_none');
        });
    });

    const close_modal_instructions = document.getElementById('icon_close_modal_instructions');
    const Ai_modal_instructions = document.querySelector('.Ai_modal_instructions');


    close_modal_instructions.addEventListener('click',()=>{
        Ai_modal_instructions.classList.add('display_none');
    });

    const instructions_open_icon = document.getElementById('instructions_icon_Ai');

    instructions_open_icon.addEventListener('click',()=>{
        Ai_modal_instructions.classList.remove('display_none');
    });

    const Ai_modal = document.querySelector('.Ai_modal');
    const closeBtn = document.querySelector('#Ai_modal_close_img');
    const Ai_modal_open_btn = document.querySelector("#Ai_modal_open_btn");

    Ai_modal_open_btn.addEventListener('click', () => {
        Ai_modal.classList.add('Ai_modal_active'); // открыть

        const open_btn = document.querySelector('.open_btn_message_notification');
        open_btn.classList.add('display_none');
    });

    closeBtn.addEventListener('click', () => {
        Ai_modal.classList.remove('Ai_modal_active'); // закрыть
    });

    const Ai_file_btn = document.querySelector('.Ai_file_btn');

    Ai_file_btn.addEventListener('click',()=>{
        document.getElementById('fileInputAiModal').click();
    });


const input = document.getElementById('fileInputAiModal');

const preview = document.getElementById('ai_chat_preview');

const filePreview = document.getElementById('ai_chat_file_preview');

const close = document.getElementById('close_ai_chat_preview');

input.addEventListener('change', () => {

    const file = input.files[0];

    if (!file) return;

    const url = URL.createObjectURL(file);

    console.log(url);

    close.classList.remove('display_none');

    // =========================
    // ЕСЛИ КАРТИНКА
    // =========================
    if (file.type.startsWith('image/')) {

        preview.src = url;

        preview.style.display = 'block';

        filePreview.classList.add('display_none');

        // открытие картинки
        preview.onclick = () => {
            window.open(url, '_blank');
        };
    }

    // =========================
    // ЕСЛИ ФАЙЛ
    // =========================
    else {

        preview.src = '';

        preview.style.display = 'none';

        filePreview.classList.remove('display_none');

        filePreview.innerHTML = `
            <div class="ai_chat_file_item">
                📄 ${file.name}
            </div>
        `;

        // открытие файла
        filePreview.onclick = () => {
            window.open(url, '_blank');
        };
    }
});



// закрытие
close.addEventListener('click', () => {

    input.value = '';

    preview.src = '';

    preview.style.display = 'block';

    filePreview.innerHTML = '';

    filePreview.classList.add('display_none');

    close.classList.add('display_none');
});

const reminder_message_ai = document.querySelector('.reminder_message_ai');

setTimeout(() => {
    if(!document.cookie.includes('UserDataAi=')){
        reminder_message_ai.classList.add('reminder_message_ai_active');
    }
}, 5000);

if(document.cookie.includes('UserDataAi=')){
    reminder_message_ai.style.display = "none";
}

const reminder_message_btn = document.querySelector('.reminder_message_btn');

reminder_message_btn.addEventListener('click', ()=>{
    reminder_message_ai.style.display = "none";
});

const logo = document.querySelector('.Ai_manager_logo');
console.log(logo);

if(AiChatDomain == "zvukoizolyatsiya.com"){
    console.log("zvukoizolyatsiya");
    logo.src = "https://chat-progress.ru/image/icons/zvukpizol_logo.svg";
    
    const a = document.getElementById('userAgreementChatAi');
    a.href = "https://zvukoizolyatsiya.com/polzovatelskoe-soglashenie.html";

    const Ai_modal_open_btn = document.getElementById("Ai_modal_open_btn");

    Ai_modal_open_btn.style.cssText += `
        background: rgb(51, 51, 51);
        width: 40px;
        height: 40px;
        bottom: 50px;
        right: 28px;
    `;

    Ai_modal_open_btn.classList.add('no-after-bg');

    const chat_ai_logo_btn = document.getElementById("chat_ai_logo_btn");

    chat_ai_logo_btn.style.cssText += `
        width: 30px;
    `;

    chat_ai_logo_btn.src = "https://chat-progress.ru/image/icons/icon_support.svg";

    document.querySelector('.Ai_nav').style.background = "#333333";
}

});