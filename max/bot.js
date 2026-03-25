// import 'dotenv/config';
// import dotenv from 'dotenv';
// import path from 'path';
// import { Bot } from '@maxhub/max-bot-api';
// import express from 'express';
// import cors from 'cors';

// console.log('cors импортирован успешно:', cors);

// dotenv.config({ path: './.env' }); 

// const app = express();

// app.use(cors({
//   origin: 'http://localhost:8888'  // фронт, откуда идут запросы
// }));

// app.use(express.json());

// const bot = new Bot(process.env.BOT_TOKEN);

// bot.on('message', (ctx) => {
//     console.log('Пришло сообщение от пользователя:', ctx.message.text);
//     ctx.reply(`Вы написали: ${ctx.message.text}`);
// });

// bot.start();

// app.post('/send', async (req, res) => {
//     res.json({ status: 'ok'});
// });

// console.log('test-max.js');

// app.listen(3000, () => console.log('Server running on port 3000'));

import express from 'express';
import { Bot } from '@maxhub/max-bot-api';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
app.use(express.json());

// создаём бота
const bot = new Bot(process.env.BOT_TOKEN);

// endpoint, куда MAX будет слать события
app.post('/webhook', async (req, res) => {
    console.log('🔥 WEBHOOK HIT:', req.body);
    await bot.handleUpdate(req.body);
    res.sendStatus(200);
});

// обработка сообщений
bot.on('message', async (ctx) => {
    const text = ctx.message?.text;

    console.log('📩 Сообщение:', text);

    if (text) {
        await ctx.reply(`Ты написал: ${text}`);
    }
});

// запуск сервера
app.listen(3000, () => {
    console.log('🚀 Server running on port 3000');
});