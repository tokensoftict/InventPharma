#!/usr/bin/env python3
"""
Fake OPay POS terminal for testing the print agent on macOS/Linux without hardware.

Usage:
    python3 tools/pos_simulator.py [--outcome success|cancel|fail|pending] [--delay 3]

It creates a virtual serial port and prints its path (e.g. /dev/ttys005).
Put that path in config.yaml -> pos.port, then call the agent's /pos/* endpoints.
"""
import argparse
import fcntl
import json
import os
import pty
import time
import tty

CODES = {"success": ("202", "Transaction Approved"), "cancel": ("201", "Transaction Cancelled"),
         "pending": ("203", "Transaction Pending"), "fail": ("205", "Transaction Failed")}
orders = {}


def extract(buf):
    start, depth, in_str, esc = -1, 0, False, False
    for i, c in enumerate(buf):
        if start < 0:
            if c == "{":
                start, depth = i, 1
            continue
        if in_str:
            if esc: esc = False
            elif c == "\\": esc = True
            elif c == '"': in_str = False
            continue
        if c == '"': in_str = True
        elif c == "{": depth += 1
        elif c == "}":
            depth -= 1
            if depth == 0:
                return buf[start:i + 1], buf[i + 1:]
    return None, buf


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--outcome", default="success", choices=CODES.keys())
    ap.add_argument("--delay", type=float, default=3.0, help="seconds to 'wait for customer'")
    a = ap.parse_args()

    master, slave = pty.openpty()
    tty.setraw(slave)
    try:
        name = os.ttyname(slave)
    except OSError:  # macOS can fail here; ask the master via TIOCPTYGNAME
        name = fcntl.ioctl(master, 0x40807453, bytes(128)).split(b"\0")[0].decode()
    print(f"Simulated OPay POS listening on: {name}")
    print("Set this as pos.port in config.yaml. Ctrl+C to stop.\n", flush=True)

    buf = ""
    while True:
        buf += os.read(master, 1024).decode(errors="ignore")
        while True:
            obj, buf = extract(buf)
            if obj is None:
                break
            print("<-", obj, flush=True)
            req = json.loads(obj)
            t = req.get("type", "").lower()
            if t == "echo":
                resp = {"type": "echo", "response": "OK"}
            elif t == "purchase":
                time.sleep(a.delay)
                code, msg = CODES[a.outcome]
                resp = {"SN": "82240118790001", "amount": req["amount"], "cardNumber": "507872******4794",
                        "cardType": "VERVE", "cashierMethod": "SMART_POS", "orderNo": req["orderNo"],
                        "orderNumber": "2026" + str(int(time.time() * 1000)), "response": msg,
                        "responseCode": code, "transactionNumber": "26" + str(int(time.time()))}
                orders[req["orderNo"]] = resp
            elif t == "inquiry":
                resp = orders.get(req.get("orderNo"), {"orderNo": req.get("orderNo"), "response": "Order not found",
                                                       "responseCode": "205"})
            else:
                resp = {"response": "Unknown request", "responseCode": "205"}
            out = json.dumps(resp, separators=(",", ":"))
            print("->", out, flush=True)
            os.write(master, out.encode())


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        pass

