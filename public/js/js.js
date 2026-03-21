document.addEventListener('DOMContentLoaded',()=>{
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

});