#!/usr/bin/env python3
"""
Tiny SMTP server for tests/email_invoice/email_test.php (local development only).
  python3 tests/email_invoice/fake_smtp.py <port> <out_dir> [user] [password]
Plain connection (no TLS), AUTH PLAIN and LOGIN. Every accepted message is written to
<out_dir>/<n>.eml. A wrong login answers 535, like Gmail does.
"""
import base64
import os
import socketserver
import sys

PORT = int(sys.argv[1])
OUT = sys.argv[2]
USER = sys.argv[3] if len(sys.argv) > 3 else "billing@test.local"
PASSWORD = sys.argv[4] if len(sys.argv) > 4 else "secret-pass"
os.makedirs(OUT, exist_ok=True)


class Handler(socketserver.StreamRequestHandler):
    def send(self, line):
        self.wfile.write((line + "\r\n").encode())
        self.wfile.flush()

    def readline(self):
        raw = self.rfile.readline()
        if not raw:
            raise ConnectionError()
        return raw.decode(errors="replace").rstrip("\r\n")

    def handle(self):
        authed = False
        rcpt = []
        try:
            self.send("220 fake.smtp ESMTP ready")
            while True:
                line = self.readline()
                cmd = line.upper()
                if cmd.startswith("EHLO") or cmd.startswith("HELO"):
                    self.send("250-fake.smtp")
                    self.send("250-AUTH PLAIN LOGIN")
                    self.send("250 8BITMIME")
                elif cmd.startswith("AUTH PLAIN"):
                    parts = line.split(" ", 2)
                    blob = parts[2] if len(parts) > 2 else None
                    if blob is None:
                        self.send("334 ")
                        blob = self.readline()
                    _, user, pw = base64.b64decode(blob).decode().split("\0")
                    authed = user == USER and pw == PASSWORD
                    self.send("235 ok" if authed else "535 5.7.8 Username and Password not accepted")
                elif cmd.startswith("AUTH LOGIN"):
                    self.send("334 VXNlcm5hbWU6")
                    user = base64.b64decode(self.readline()).decode()
                    self.send("334 UGFzc3dvcmQ6")
                    pw = base64.b64decode(self.readline()).decode()
                    authed = user == USER and pw == PASSWORD
                    self.send("235 ok" if authed else "535 5.7.8 Username and Password not accepted")
                elif cmd.startswith("MAIL FROM"):
                    if not authed:
                        self.send("530 authentication required")
                        continue
                    rcpt = []
                    self.send("250 ok")
                elif cmd.startswith("RCPT TO"):
                    rcpt.append(line[8:].strip())
                    self.send("250 ok")
                elif cmd == "DATA":
                    self.send("354 go")
                    lines = []
                    while True:
                        l = self.readline()
                        if l == ".":
                            break
                        lines.append(l[1:] if l.startswith("..") else l)
                    n = len([f for f in os.listdir(OUT) if f.endswith(".eml")]) + 1
                    with open(os.path.join(OUT, "%d.eml" % n), "w") as f:
                        f.write("X-Rcpt: " + ", ".join(rcpt) + "\n" + "\n".join(lines))
                    self.send("250 queued as %d" % n)
                elif cmd in ("RSET", "NOOP"):
                    self.send("250 ok")
                elif cmd == "QUIT":
                    self.send("221 bye")
                    return
                else:
                    self.send("502 not implemented")
        except ConnectionError:
            return


class Server(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    daemon_threads = True


with Server(("127.0.0.1", PORT), Handler) as srv:
    srv.serve_forever()
