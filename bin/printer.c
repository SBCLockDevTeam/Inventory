/*
 * printer — binary helper for sending ESC/P label jobs to a network printer.
 *
 * Usage: printer <host> <port>
 *
 * Reads the raw ESC/P payload from stdin and sends it over a TCP connection
 * to <host>:<port>.  <host> may be a hostname (e.g. pierround.com) or an
 * IP address; getaddrinfo() resolves either form so that domain-based
 * print servers work without requiring a numeric IP.
 *
 * Exit codes: 0 = success, 1 = error (message written to stderr).
 *
 * PHP's fsockopen() has had intermittent failures resolving hostnames in
 * some server configurations; this compiled helper avoids that issue.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/socket.h>
#include <netdb.h>
#include <netinet/in.h>
#include <arpa/inet.h>

#define CONNECT_TIMEOUT_SECS 5
#define READ_CHUNK           4096

int main(int argc, char* argv[]) {
    if (argc < 3) {
        fprintf(stderr, "Usage: %s <host> <port>\n", argv[0]);
        return 1;
    }

    const char* host     = argv[1];
    const char* port_str = argv[2];

    /* Resolve the host — accepts both hostnames and IP addresses */
    struct addrinfo hints, *res, *rp;
    memset(&hints, 0, sizeof(hints));
    hints.ai_family   = AF_UNSPEC;   /* IPv4 or IPv6 */
    hints.ai_socktype = SOCK_STREAM;

    int gai = getaddrinfo(host, port_str, &hints, &res);
    if (gai != 0) {
        fprintf(stderr, "Error: cannot resolve %s – %s\n", host, gai_strerror(gai));
        return 1;
    }

    /* Try each address returned until one connects */
    int sock = -1;
    for (rp = res; rp != NULL; rp = rp->ai_next) {
        sock = socket(rp->ai_family, rp->ai_socktype, rp->ai_protocol);
        if (sock < 0) {
            continue;
        }

        /* Apply send/receive timeout */
        struct timeval tv;
        tv.tv_sec  = CONNECT_TIMEOUT_SECS;
        tv.tv_usec = 0;
        setsockopt(sock, SOL_SOCKET, SO_RCVTIMEO, &tv, sizeof(tv));
        setsockopt(sock, SOL_SOCKET, SO_SNDTIMEO, &tv, sizeof(tv));

        if (connect(sock, rp->ai_addr, rp->ai_addrlen) == 0) {
            break; /* Connected successfully */
        }

        close(sock);
        sock = -1;
    }

    freeaddrinfo(res);

    if (sock < 0) {
        fprintf(stderr, "Error: could not connect to %s:%s\n", host, port_str);
        return 1;
    }

    /* Delay after connect — give the printer time to be ready */
    usleep(300000); /* 300 ms */

    /* Read the ESC/P payload from stdin and forward it to the printer */
    char buf[READ_CHUNK];
    ssize_t n;
    while ((n = read(STDIN_FILENO, buf, sizeof(buf))) > 0) {
        ssize_t sent = 0;
        while (sent < n) {
            ssize_t s = send(sock, buf + sent, (size_t)(n - sent), 0);
            if (s < 0) {
                fprintf(stderr, "Error: send() failed\n");
                close(sock);
                return 1;
            }
            sent += s;
        }
    }

    /* Delay before close — let the printer finish receiving */
    usleep(300000); /* 300 ms */

    close(sock);
    return 0;
}
