import { Server } from "socket.io"; // Import Server from socket.io

export function init(server) {
    const io = new Server(server); // Initialize a new Socket.io server
    return io;
}
