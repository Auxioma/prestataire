require('dotenv').config();

const express = require('express');
const http = require('http');
const cors = require('cors');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const PORT = process.env.PORT || 3001;
const FRONT_ORIGIN = process.env.FRONT_ORIGIN || 'http://127.0.0.1:8000';
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || 'change-me';

app.use(cors({
  origin: FRONT_ORIGIN,
  credentials: true,
}));

app.use(express.json());

app.get('/health', (req, res) => {
  res.json({ ok: true, service: 'realtime-server' });
});

const io = new Server(server, {
  cors: {
    origin: FRONT_ORIGIN,
    credentials: true,
  },
});

io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);

  socket.on('join_conversation', (conversationId) => {
    if (!conversationId) return;

    const roomName = `conversation:${conversationId}`;
    socket.join(roomName);

    console.log(`Socket ${socket.id} joined room ${roomName}`);
    socket.emit('joined_conversation', { room: roomName });
  });

  socket.on('join_user', (userId) => {
    if (!userId) return;

    const roomName = `user:${userId}`;
    socket.join(roomName);

    console.log(`Socket ${socket.id} joined room ${roomName}`);
    socket.emit('joined_user', { room: roomName });
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

app.post('/emit/message', (req, res) => {
  const token = req.headers['x-internal-token'];

  if (token !== INTERNAL_TOKEN) {
    return res.status(403).json({ ok: false, error: 'forbidden' });
  }

  const { conversationId, message } = req.body;

  if (!conversationId || !message) {
    return res.status(400).json({ ok: false, error: 'invalid_payload' });
  }

  const roomName = `conversation:${conversationId}`;

  io.to(roomName).emit('message_created', {
    conversationId,
    message,
  });

  return res.json({ ok: true, room: roomName });
});

app.post('/emit/notification', (req, res) => {
  const token = req.headers['x-internal-token'];

  if (token !== INTERNAL_TOKEN) {
    return res.status(403).json({ ok: false, error: 'forbidden' });
  }

  const { userId, notification } = req.body;

  if (!userId || !notification) {
    return res.status(400).json({ ok: false, error: 'invalid_payload' });
  }

  const roomName = `user:${userId}`;

  io.to(roomName).emit('notification_created', {
    userId,
    notification,
  });

  return res.json({ ok: true, room: roomName });
});

server.listen(PORT, () => {
  console.log(`Realtime server listening on http://localhost:${PORT}`);
});