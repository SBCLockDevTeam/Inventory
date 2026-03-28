#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

int main(int argc, char* argv[]) {
    if (argc < 3) {
        fprintf(stderr, "Usage: %s <ip> <port>\n", argv[0]);
        return 1;
    }

    const char* IP = argv[1];
    int PORT = atoi(argv[2]);
    int TIMEOUT = 5;

    printf("Testing printer at %s:%d\n", IP, PORT);

    int sock = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
    if (sock < 0) {
        fprintf(stderr, "Error: socket() failed\n");
        return 1;
    }

    struct timeval tv;
    tv.tv_sec = TIMEOUT;
    tv.tv_usec = 0;
    setsockopt(sock, SOL_SOCKET, SO_RCVTIMEO, (const char*)&tv, sizeof(tv));
    setsockopt(sock, SOL_SOCKET, SO_SNDTIMEO, (const char*)&tv, sizeof(tv));

    struct sockaddr_in addr;
    memset(&addr, 0, sizeof(addr));
    addr.sin_family = AF_INET;
    addr.sin_port = htons(PORT);
    addr.sin_addr.s_addr = inet_addr(IP);

    printf("Connecting...\n");

    if (connect(sock, (struct sockaddr*)&addr, sizeof(addr)) < 0) {
        fprintf(stderr, "Connection failed\n");
        close(sock);
        return 1;
    }

    printf("Connected successfully\n");

    // Delay after connect - printer needs time to be ready
    usleep(300000); // 300ms

    const char print_cmd[] = {
        0x1b, 0x69, 0x61, 0x00,  // ESC ia NUL - switch to ESC/P mode
        0x1b, 0x40,               // ESC @ - initialize
        0x1b, 0x6b, 0x0b,        // ESC k - select font
        'H', 'e', 'l', 'l', 'o', ' ', 'f', 'r', 'o', 'm', ' ', 'L', 'i', 'n', 'u', 'x', ' ', 'T', 'e', 's', 't',
        0x0c                      // FF - eject
    };

    int cmd_len = sizeof(print_cmd);
    printf("Sending %d bytes...\n", cmd_len);

    int sent = send(sock, print_cmd, cmd_len, 0);

    if (sent < 0) {
        fprintf(stderr, "Send failed\n");
        close(sock);
        return 1;
    }

    printf("Sent %d bytes successfully\n", sent);

    // Delay before close - let printer finish receiving
    usleep(300000); // 300ms

    close(sock);
    return 0;
}
